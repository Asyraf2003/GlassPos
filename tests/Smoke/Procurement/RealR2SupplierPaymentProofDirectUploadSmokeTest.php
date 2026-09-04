<?php

declare(strict_types=1);

namespace Tests\Smoke\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofDirectUploadAdapter;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class RealR2SupplierPaymentProofDirectUploadSmokeTest extends TestCase
{
    use SeedsMinimalProcurementFixture;

    public function test_real_private_r2_prepare_put_and_finalize(): void
    {
        if (getenv('RUN_REAL_R2_SUPPLIER_PROOF_SMOKE') !== '1') {
            $this->markTestSkipped('Real R2 smoke requires explicit opt-in.');
        }

        $this->loadLocalR2Configuration();
        $this->assertSafeRuntime();
        $ids = $this->seedScope();
        $paths = [];

        try {
            $proof = "%PDF-1.4\n% GlassPos real R2 smoke\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n";
            $prepared = $this->actingAs($ids['actor'])->postJson(
                route('admin.procurement.supplier-payment-proofs.direct-upload.prepare'),
                [
                    'scope_type' => 'supplier_invoice',
                    'scope_id' => $ids['invoice'],
                    'idempotency_key' => 'real-r2-smoke-'.$ids['suffix'],
                    'files' => [[
                        'original_filename' => 'real-r2-smoke.pdf',
                        'mime_type' => 'application/pdf',
                        'file_size_bytes' => strlen($proof),
                    ]],
                ],
            );
            $prepared->assertOk()->assertJsonPath('success', true);
            $intentId = (string) $prepared->json('data.upload_intent_id');
            $paths[] = (string) $prepared->json('data.files.0.storage_path');
            $url = (string) $prepared->json('data.files.0.upload_url');
            $headers = $prepared->json('data.files.0.headers');

            self::assertSame(0, DB::transactionLevel());
            self::assertStringStartsWith('supplier-payment-proof-uploads/'.$intentId.'/', $paths[0]);
            self::assertStringNotContainsString('supplier-payment-proofs/', $paths[0]);
            self::assertIsArray($headers);
            Http::withHeaders($headers)->withBody($proof, 'application/pdf')->put($url)->throw();
            self::assertSame(0, DB::transactionLevel());

            $finalized = $this->actingAs($ids['actor'])->postJson(route(
                'admin.procurement.supplier-payment-proofs.direct-upload.finalize',
                ['uploadIntentId' => $intentId],
            ));
            $finalized->assertOk()->assertJsonPath('success', true);
            $attachment = DB::table('supplier_payment_proof_attachments')
                ->where('supplier_payment_id', $finalized->json('data.supplier_payment_id'))
                ->first();
            self::assertNotNull($attachment);
            $paths[] = (string) $attachment->storage_path;
            self::assertMatchesRegularExpression('#^supplier-payment-proofs/[^/]+/[a-f0-9]{64}\.pdf$#', $paths[1]);
            self::assertSame(strlen($proof), (int) $attachment->file_size_bytes);
            self::assertSame('application/pdf', (string) $attachment->mime_type);
            self::assertTrue(Storage::disk('r2_private')->exists($paths[1]));
            self::assertFalse(Storage::disk('r2_private')->exists($paths[0]));
        } finally {
            $this->cleanup($ids, $paths);
        }
    }

    private function assertSafeRuntime(): void
    {
        self::assertTrue(app()->environment('testing'));
        self::assertStringEndsWith('_test', strtolower(DB::connection()->getDatabaseName()));
        self::assertInstanceOf(
            LaravelSupplierPaymentProofDirectUploadAdapter::class,
            app(SupplierPaymentProofDirectUploadPort::class),
        );

        foreach (['endpoint', 'bucket', 'key', 'secret'] as $key) {
            self::assertNotSame('', trim((string) config('filesystems.disks.r2_private.'.$key)), $key.' is required');
        }
    }

    private function loadLocalR2Configuration(): void
    {
        $contents = file_get_contents(base_path('.env'));
        self::assertIsString($contents, 'Local .env is required as the explicit smoke credential source.');
        $values = Dotenv::parse($contents);
        $disk = config('filesystems.disks.r2_private', []);
        self::assertIsArray($disk);
        $disk['endpoint'] = $values['R2_ENDPOINT'] ?? '';
        $disk['bucket'] = $values['R2_PRIVATE_BUCKET'] ?? '';
        $disk['key'] = $values['R2_PRIVATE_ACCESS_KEY_ID'] ?? '';
        $disk['secret'] = $values['R2_PRIVATE_SECRET_ACCESS_KEY'] ?? '';
        config(['filesystems.disks.r2_private' => $disk]);
        Storage::forgetDisk('r2_private');
    }

    /** @return array{suffix:string,actor:User,supplier:string,product:string,invoice:string,line:string} */
    private function seedScope(): array
    {
        $suffix = bin2hex(random_bytes(6));
        $ids = [
            'suffix' => $suffix,
            'supplier' => 'smoke-supplier-'.$suffix,
            'product' => 'smoke-product-'.$suffix,
            'invoice' => 'smoke-invoice-'.$suffix,
            'line' => 'smoke-line-'.$suffix,
        ];
        $actor = User::factory()->create();
        DB::table('actor_accesses')->insert(['actor_id' => (string) $actor->getAuthIdentifier(), 'role' => 'admin']);
        $this->seedMinimalSupplier($ids['supplier'], 'PT Real R2 Smoke', 'pt real r2 smoke');
        $this->seedMinimalProduct($ids['product'], 'R2-'.$suffix, 'Produk Real R2', 'Smoke', 100, 50000);
        $this->seedMinimalSupplierInvoice($ids['invoice'], $ids['supplier'], '2026-09-04', '2026-10-04', 100000);
        $this->seedMinimalSupplierInvoiceLine($ids['line'], $ids['invoice'], $ids['product'], 2, 100000, 50000);

        return $ids + ['actor' => $actor];
    }

    /** @param array{actor:User,supplier:string,product:string,invoice:string} $ids @param list<string> $paths */
    private function cleanup(array $ids, array $paths): void
    {
        Storage::disk('r2_private')->delete(array_values(array_filter($paths)));
        $intentIds = DB::table('supplier_payment_proof_upload_intents')->where('scope_id', $ids['invoice'])->pluck('id');
        DB::table('supplier_payment_proof_upload_intent_files')->whereIn('upload_intent_id', $intentIds)->delete();
        DB::table('supplier_payment_proof_upload_intents')->whereIn('id', $intentIds)->delete();
        $paymentIds = DB::table('supplier_payments')->where('supplier_invoice_id', $ids['invoice'])->pluck('id');
        DB::table('supplier_payment_proof_attachments')->whereIn('supplier_payment_id', $paymentIds)->delete();
        DB::table('supplier_payments')->whereIn('id', $paymentIds)->delete();
        DB::table('audit_logs')->where('context', 'like', '%'.$ids['invoice'].'%')->delete();
        DB::table('supplier_invoice_list_projection')->where('supplier_invoice_id', $ids['invoice'])->delete();
        DB::table('supplier_invoice_lines')->where('supplier_invoice_id', $ids['invoice'])->delete();
        DB::table('supplier_invoices')->where('id', $ids['invoice'])->delete();
        DB::table('supplier_list_projection')->where('supplier_id', $ids['supplier'])->delete();
        DB::table('products')->where('id', $ids['product'])->delete();
        DB::table('suppliers')->where('id', $ids['supplier'])->delete();
        DB::table('actor_accesses')->where('actor_id', (string) $ids['actor']->getAuthIdentifier())->delete();
        $ids['actor']->delete();
    }
}
