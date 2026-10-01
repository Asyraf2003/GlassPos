<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Adapters\Out\Procurement\DatabaseMobileSupplierHubReaderAdapter;
use App\Application\Procurement\UseCases\UpdateSupplierHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class SupplierPaymentBankPresentationFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::query()->create(['name' => 'Admin', 'email' => 'payment-bank@test.local', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);
        $this->actingAs($user);
        $this->seedMinimalSupplier('bank-supplier', 'Old Supplier', 'old supplier');
        $this->seedMinimalSupplierInvoice('bank-invoice', 'bank-supplier', '2026-09-01', '2026-10-01', 100000, 'Old Supplier', 'PRIVATE-INVOICE-NO');
    }

    public function test_current_master_is_used_in_payment_views_without_changing_snapshot(): void
    {
        app(UpdateSupplierHandler::class)->handle('bank-supplier', 'Current Supplier', ['bank_name' => 'BCA', 'bank_account_number' => '001000']);
        $this->withHeader('Sec-CH-UA-Mobile', '?1')->get(route('admin.dashboard'))->assertOk()
            ->assertSee('BCA | 001000')->assertSee('Current Supplier')->assertDontSee('PRIVATE-INVOICE-NO');
        $this->get(route('admin.procurement.supplier-invoices.table'))
            ->assertOk()->assertJsonPath('data.rows.0.bank_name', 'BCA')
            ->assertJsonPath('data.rows.0.bank_account_number', '001000')
            ->assertJsonPath('data.rows.0.supplier_nama_pt_pengirim_current', 'Current Supplier');
        $this->assertDatabaseHas('supplier_invoices', ['id' => 'bank-invoice', 'supplier_nama_pt_pengirim_snapshot' => 'Old Supplier']);
        $this->withHeader('Sec-CH-UA-Mobile', '?0')->get(route('admin.procurement.supplier-invoices.index'))
            ->assertOk()->assertSee('procurement-payment-bank')->assertSee('Sisa tagihan');
        $this->get(route('admin.procurement.supplier-invoices.payment-proofs.show', 'bank-invoice'))
            ->assertOk()->assertSee('BCA | 001000')->assertSee('Current Supplier');
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(DatabaseMobileSupplierHubReaderAdapter::class)->outstandingInvoices();
        self::assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_paid_legacy_payment_without_media_is_valid_and_visible(): void
    {
        $this->withHeader('Sec-CH-UA-Mobile', '?1')->get(route('admin.dashboard'))->assertSee('Data bank belum diisi');
        $this->seedMinimalSupplierPayment('legacy-payment', 'bank-invoice', 100000, '2026-09-15', 'pending', null);
        $reader = app(DatabaseMobileSupplierHubReaderAdapter::class);
        self::assertSame([], $reader->outstandingInvoices());
        $rows = $reader->recentPaymentProofs();
        self::assertCount(1, $rows);
        self::assertNull($rows[0]['attachment_id']);
        self::assertNull($rows[0]['original_filename']);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Data belum ada')
            ->assertSee('data-payment-id="legacy-payment"', false)->assertSee('data-mobile-attach-payment', false);
        $this->get(route('admin.procurement.supplier-invoices.payment-proofs.show', 'bank-invoice'))
            ->assertOk()->assertSee('Data belum ada')->assertSee('data-scope-type="supplier_payment"', false);
        self::assertSame(1, DB::table('supplier_payments')->count());
    }

    public function test_bank_edits_change_only_master_and_next_payment_destination(): void
    {
        $this->seedMinimalSupplierPayment('partial-paid', 'bank-invoice', 30000, '2026-09-15', 'uploaded', null);
        DB::table('supplier_payment_proof_attachments')->insert([
            'id' => 'historical-proof', 'supplier_payment_id' => 'partial-paid',
            'storage_path' => 'supplier-payment-proofs/partial-paid/proof.pdf', 'original_filename' => 'transfer.pdf',
            'mime_type' => 'application/pdf', 'file_size_bytes' => 100,
            'uploaded_at' => '2026-09-15 12:00:00', 'uploaded_by_actor_id' => 'admin',
        ]);
        $invoiceBefore = DB::table('supplier_invoices')->first();
        $paymentBefore = DB::table('supplier_payments')->first();
        $proofBefore = DB::table('supplier_payment_proof_attachments')->first();
        foreach ([['BCA', '00012'], ['Mandiri', '00099'], [null, null]] as [$bank, $account]) {
            app(UpdateSupplierHandler::class)->handle('bank-supplier', 'Current Supplier', ['bank_name' => $bank, 'bank_account_number' => $account]);
            self::assertEquals($invoiceBefore, DB::table('supplier_invoices')->first());
            self::assertEquals($paymentBefore, DB::table('supplier_payments')->first());
            self::assertEquals($proofBefore, DB::table('supplier_payment_proof_attachments')->first());
            $reader = app(DatabaseMobileSupplierHubReaderAdapter::class);
            $nextPayment = $reader->outstandingInvoices()[0];
            self::assertSame(70000, $nextPayment['outstanding_rupiah']);
            self::assertSame($account, $nextPayment['bank_account_number']);
            self::assertArrayNotHasKey('bank_account_number', $reader->recentPaymentProofs()[0]);
            $label = $bank === null ? 'Data bank belum diisi' : $bank.' | '.$account;
            $response = $this->withHeader('Sec-CH-UA-Mobile', '?1')->get(route('admin.dashboard'))
                ->assertOk()->assertSee($label);
            $document = new \DOMDocument();
            @$document->loadHTML($response->getContent());
            $history = (new \DOMXPath($document))->query('//*[@data-mobile-hub-section="history"]')->item(0)->textContent;
            self::assertStringNotContainsString($label, $history);
            $this->get(route('admin.procurement.supplier-invoices.payment-proofs.show', 'bank-invoice'))
                ->assertOk()->assertSee($label);
        }
    }

    public function test_reversed_payment_stays_out_of_active_history(): void
    {
        $this->seedMinimalSupplierPayment('reversed-payment', 'bank-invoice', 100000, '2026-09-15', 'pending', null);
        $this->post(route('admin.procurement.supplier-payments.reverse.store', 'reversed-payment'), ['reason' => 'Correction'])
            ->assertSessionHasNoErrors();
        $reader = app(DatabaseMobileSupplierHubReaderAdapter::class);
        self::assertSame([], $reader->recentPaymentProofs());
        self::assertSame(100000, $reader->outstandingInvoices()[0]['outstanding_rupiah']);
    }

    public function test_desktop_bank_reads_do_not_add_per_invoice_queries(): void
    {
        $this->seedMinimalSupplierInvoice('second-bank-invoice', 'bank-supplier', '2026-09-01', '2026-10-01', 10000);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $rows = app(\App\Adapters\Out\Procurement\DatabaseProcurementInvoiceTableReaderAdapter::class)->search(
            \App\Application\Procurement\DTO\ProcurementInvoiceTableQuery::fromValidated([]),
        );
        self::assertCount(2, $rows['rows']);
        self::assertCount(2, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_multiple_proofs_keep_secure_download_links(): void
    {
        $this->seedMinimalSupplierPayment('paid', 'bank-invoice', 100000, '2026-09-15', 'uploaded', null);
        foreach (['a', 'b'] as $id) {
            DB::table('supplier_payment_proof_attachments')->insert([
                'id' => $id, 'supplier_payment_id' => 'paid', 'storage_path' => 'supplier-payment-proofs/paid/'.$id.'.pdf',
                'original_filename' => $id.'.pdf', 'mime_type' => 'application/pdf', 'file_size_bytes' => 100,
                'uploaded_at' => '2026-09-15 12:00:00', 'uploaded_by_actor_id' => 'admin',
            ]);
        }
        $response = $this->withHeader('Sec-CH-UA-Mobile', '?1')->get(route('admin.dashboard'))->assertOk();
        foreach (['a', 'b'] as $id) {
            $response->assertSee(route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => $id, 'download' => 1]));
        }
        self::assertCount(2, app(DatabaseMobileSupplierHubReaderAdapter::class)->recentPaymentProofs());
        $response->assertDontSee('data-payment-id="paid"', false);
    }
}
