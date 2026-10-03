<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Application\ProductCatalog\UseCases\AdoptTransferredProductMergeHandler;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ProductCatalog\ProductIdentityMergePort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsTransferredProductMergeFixture;
use Tests\TestCase;

final class ProductIdentityMergeSafetyFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsTransferredProductMergeFixture;

    public function test_relation_is_never_inferred_from_prose_similarity_or_movements(): void
    {
        $this->seedTransferredMerge();
        DB::table('products')->where('id', 'product-2')->update(['nama_barang' => 'Ban Luar']);
        $this->assertNull(app(ProductIdentityMergePort::class)->findBySource('product-1'));
        $this->assertDatabaseCount('product_identity_merges', 0);
        $this->assertDatabaseHas('supplier_invoice_lines', ['id' => 'invoice-line-1', 'is_current' => true, 'product_id' => 'product-1']);
    }

    public function test_same_identity_is_rejected_without_writes(): void
    {
        $actor = $this->seedTransferredMerge();
        $this->assertRejected(fn () => app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-1', $actor, 'Explicit duplicate', 'prior-transfer'));
    }

    public function test_missing_target_and_missing_or_wrong_prior_transfer_are_rejected(): void
    {
        $actor = $this->seedTransferredMerge();
        foreach ([['missing', 'prior-transfer'], ['product-2', 'missing']] as [$target, $transfer]) {
            $this->assertRejected(fn () => app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', $target, $actor, 'Explicit duplicate', $transfer));
        }
        DB::table('inventory_movements')->where('id', 'merge-product-2')->update(['total_cost_rupiah' => 19000]);
        $this->assertRejected(fn () => app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate', 'prior-transfer'));
    }

    public function test_conflicting_source_target_and_reused_operation_are_rejected(): void
    {
        $actor = $this->seedTransferredMerge();
        $handler = app(AdoptTransferredProductMergeHandler::class);
        $handler->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate', 'prior-transfer');
        DB::table('products')->insert(['id' => 'product-3', 'nama_barang' => 'Unrelated', 'merek' => 'Other', 'harga_jual' => 10000]);
        try {
            $handler->handle('merge-2', 'product-1', 'product-3', $actor, 'Conflicting duplicate', 'prior-transfer');
            $this->fail('Conflicting canonical ownership must be rejected.');
        } catch (DomainException) {
            $this->assertSame('product-2', app(ProductIdentityMergePort::class)->findBySource('product-1')?->canonicalProductId);
            $this->assertDatabaseCount('product_identity_merges', 1);
            $this->assertDatabaseCount('supplier_invoice_versions', 2);
        }
    }

    public function test_existing_duplicate_line_constraint_rolls_back_entire_adoption(): void
    {
        $actor = $this->seedTransferredMerge();
        $line = (array) DB::table('supplier_invoice_lines')->where('id', 'invoice-line-1')->sole();
        $line['id'] = 'line-already-b';
        $line['line_no'] = 2;
        $line['product_id'] = 'product-2';
        DB::table('supplier_invoice_lines')->insert($line);
        DB::table('supplier_invoices')->where('id', 'invoice-1')->update(['grand_total_rupiah' => 40000]);
        $this->assertRejected(fn () => app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate', 'prior-transfer'));
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_dry_run_validates_and_rolls_back_relation_versions_and_audit(): void
    {
        $actor = $this->seedTransferredMerge();
        $this->assertSame(1, app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate', 'prior-transfer', true));
        $this->assertDatabaseCount('product_identity_merges', 0);
        $this->assertDatabaseCount('supplier_invoice_versions', 1);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertDatabaseHas('supplier_invoice_lines', ['id' => 'invoice-line-1', 'is_current' => true]);
    }

    public function test_audit_failure_rolls_back_relation_invoice_and_projection(): void
    {
        $actor = $this->seedTransferredMerge();
        $audit = \Mockery::mock(\App\Ports\Out\AuditEventWriterPort::class);
        $audit->shouldReceive('write')->once()->andThrow(new \RuntimeException('injected audit failure'));
        $this->app->instance(\App\Ports\Out\AuditEventWriterPort::class, $audit);
        try {
            app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate', 'prior-transfer');
            $this->fail('Expected injected failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('injected audit failure', $exception->getMessage());
            $this->assertDatabaseCount('product_identity_merges', 0);
            $this->assertDatabaseCount('supplier_invoice_versions', 1);
            $this->assertDatabaseCount('audit_events', 0);
            $this->assertDatabaseHas('supplier_invoice_lines', ['id' => 'invoice-line-1', 'is_current' => true]);
            $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-1', 'last_revision_no' => 1]);
            $this->assertDatabaseCount('supplier_invoice_list_projection', 0);
        }
    }

    public function test_cli_requires_explicit_attestation_and_defaults_to_dry_run(): void
    {
        $actor = $this->seedTransferredMerge();
        $args = ['operation' => 'merge-1', 'source' => 'product-1', 'canonical' => 'product-2',
            '--actor' => $actor, '--reason' => 'Explicit duplicate', '--prior-transfer' => 'prior-transfer'];
        $this->artisan('products:adopt-transferred-merge', $args)->assertFailed();
        $this->artisan('products:adopt-transferred-merge', $args + ['--same-physical-product' => true])->assertSuccessful();
        $this->assertDatabaseCount('product_identity_merges', 0);
        $this->artisan('products:adopt-transferred-merge', $args + ['--same-physical-product' => true, '--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('product_identity_merges', 1);
    }

    public function test_invalid_actor_or_residual_source_stock_prevents_adoption(): void
    {
        $actor = $this->seedTransferredMerge();
        $this->assertRejected(fn () => app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-2', 'unknown-actor', 'Explicit duplicate', 'prior-transfer'));
        DB::table('inventory_movements')->where('id', 'movement-receipt-1')->update(['qty_delta' => 3]);
        $this->assertRejected(fn () => app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate', 'prior-transfer'));
    }

    private function assertRejected(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected rejected merge.');
        } catch (DomainException) {
            $this->assertDatabaseCount('product_identity_merges', 0);
            $this->assertDatabaseCount('supplier_invoice_versions', 1);
            $this->assertDatabaseHas('supplier_invoice_lines', ['id' => 'invoice-line-1', 'is_current' => true]);
            $this->assertDatabaseCount('inventory_movements', 3);
        }
    }
}
