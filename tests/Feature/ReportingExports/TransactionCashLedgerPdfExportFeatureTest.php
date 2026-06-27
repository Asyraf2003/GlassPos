<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionCashLedgerPdfExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_transaction_cash_ledger_as_pdf(): void
    {
        $this->seedCashInEvent('note-1', 'wi-1', 'pay-1', '2030-01-02', 8000, 'Budi');
        $this->seedCashInEvent('note-2', 'wi-2', 'pay-2', '2030-01-03', 4000, 'Sari');
        $this->seedCashOutEvent('note-1', 'wi-1', 'pay-1', 'ref-1', '2030-01-04', 1000, 'Refund');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertDownload('laporan-buku-kas-transaksi-2030-01-01-sampai-2030-01-31.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kasir_cannot_export_transaction_cash_ledger_as_pdf(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.transaction_cash_ledger.export_pdf')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_transaction_cash_ledger_pdf_export_rejects_range_longer_than_one_month(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.export_pdf', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2030-02-01',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export PDF maksimal 1 bulan.');
    }

    public function test_transaction_cash_ledger_pdf_view_contains_indonesian_report_labels(): void
    {
        $html = view('admin.reporting.transaction_cash_ledger.export_pdf', [
            'title' => 'Laporan Buku Kas Transaksi',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Total Kejadian', 'value' => 3],
                ['label' => 'Kas Masuk', 'value' => 'Rp 12.000'],
                ['label' => 'Kas Keluar', 'value' => 'Rp 1.000'],
                ['label' => 'Nilai Bersih', 'value' => 'Rp 11.000'],
            ],
            'rows' => [
                [
                    'date' => '02 Januari 2030',
                    'note_label' => 'Budi · 2030-01-02',
                    'event_type' => 'Alokasi Pembayaran',
                    'direction' => 'Masuk',
                    'payment_marker' => 'Ada',
                    'refund_marker' => '-',
                    'source_table' => 'customer_payments',
                    'source_id' => 'pay-1',
                    'source_disposition_id' => '-',
                    'amount' => 'Rp 8.000',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Laporan Buku Kas Transaksi', $html);
        $this->assertStringContainsString('Total Kejadian', $html);
        $this->assertStringContainsString('Kas Masuk', $html);
        $this->assertStringContainsString('Kas Keluar', $html);
        $this->assertStringContainsString('Nilai Bersih', $html);
        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringNotContainsString('Catatan Laporan', $html);
        $this->assertStringNotContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('Tabel Sumber', $html);
        $this->assertStringNotContainsString('customer_payments', $html);
        $this->assertStringNotContainsString('pay-1', $html);
    }

    public function test_transaction_cash_ledger_pdf_view_uses_owner_readable_report_sections_not_detail_tables(): void
    {
        $html = view('admin.reporting.transaction_cash_ledger.export_pdf', [
            'title' => 'Laporan Buku Kas Transaksi',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Total Kejadian', 'value' => 3],
                ['label' => 'Kas Masuk', 'value' => 'Rp 12.000'],
                ['label' => 'Kas Keluar', 'value' => 'Rp 1.000'],
                ['label' => 'Nilai Bersih', 'value' => 'Rp 11.000'],
            ],
            'rows' => [
                [
                    'date' => '02 Januari 2030',
                    'note_label' => 'Budi · 2030-01-02',
                    'event_type' => 'Alokasi Pembayaran',
                    'direction' => 'Masuk',
                    'payment_marker' => 'Ada',
                    'refund_marker' => '-',
                    'source_table' => 'customer_payments',
                    'source_id' => 'pay-1',
                    'source_disposition_id' => '-',
                    'amount' => 'Rp 8.000',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringNotContainsString('Catatan Laporan', $html);
        $this->assertStringNotContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('<table class="summary">', $html);
        $this->assertStringNotContainsString('<table class="detail">', $html);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-transaction-cash-ledger-report-pdf-export@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedCashInEvent(
        string $noteId,
        string $workItemId,
        string $paymentId,
        string $paidAt,
        int $amountRupiah,
        string $customerName
    ): void {
        $this->seedNote($noteId, $customerName, $paidAt, $amountRupiah);
        $this->seedWorkItem($workItemId, $noteId, 1, $amountRupiah);

        DB::table('customer_payments')->insert([
            'id' => $paymentId,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'payment-allocation-' . $paymentId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => 'alloc-' . $paymentId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_fee',
            'component_ref_id' => $workItemId,
            'component_amount_rupiah_snapshot' => $amountRupiah,
            'allocated_amount_rupiah' => $amountRupiah,
            'allocation_priority' => 1,
        ]);
    }

    private function seedCashOutEvent(
        string $noteId,
        string $workItemId,
        string $paymentId,
        string $refundId,
        string $refundedAt,
        int $amountRupiah,
        string $reason
    ): void {
        DB::table('customer_refunds')->insert([
            'id' => $refundId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
            'refunded_at' => $refundedAt,
            'reason' => $reason,
        ]);

        DB::table('refund_component_allocations')->insert([
            'id' => 'refund-alloc-' . $refundId,
            'customer_refund_id' => $refundId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_fee',
            'component_ref_id' => $workItemId,
            'refunded_amount_rupiah' => $amountRupiah,
            'refund_priority' => 1,
        ]);
    }

    private function seedNote(string $id, string $customerName, string $transactionDate, int $totalRupiah): void
    {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => $customerName,
            'transaction_date' => $transactionDate,
            'total_rupiah' => $totalRupiah,
        ]);
    }

    private function seedWorkItem(string $id, string $noteId, int $lineNo, int $subtotalRupiah): void
    {
        DB::table('work_items')->insert([
            'id' => $id,
            'note_id' => $noteId,
            'line_no' => $lineNo,
            'transaction_type' => 'service_only',
            'status' => 'open',
            'subtotal_rupiah' => $subtotalRupiah,
        ]);
    }
}
