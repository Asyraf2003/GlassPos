<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionReportPdfExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_transaction_report_as_pdf(): void
    {
        $this->seedNote('note-1', 'Budi', '2030-01-07', 100000);
        $this->seedNote('note-2', 'Siti', '2030-01-09', 50000);
        $this->seedNote('note-outside', 'Outside', '2030-02-01', 30000);

        $this->seedCustomerPayment('payment-1', 100000, '2030-01-07');
        $this->seedCustomerPayment('payment-2', 50000, '2030-01-09');
        $this->seedCustomerPayment('payment-outside', 30000, '2030-02-01');

        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 99999);
        $this->seedPaymentAllocation('allocation-2', 'payment-2', 'note-2', 50000);
        $this->seedPaymentAllocation('allocation-outside', 'payment-outside', 'note-outside', 30000);

        $this->seedCustomerRefund('refund-1', 'payment-1', 'note-1', 9000, '2030-01-08', 'Koreksi');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_summary.export_pdf', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertDownload('laporan-transaksi-2030-01-01-sampai-2030-01-31.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kasir_cannot_export_transaction_report_as_pdf(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.transaction_summary.export_pdf')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_pdf_export_rejects_range_longer_than_one_month(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_summary.export_pdf', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2030-02-01',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export PDF maksimal 1 bulan.');
    }

    public function test_pdf_view_contains_indonesian_report_labels(): void
    {
        $html = view('admin.reporting.transaction_summary.export_pdf', [
            'title' => 'Laporan Transaksi',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Jumlah Nota', 'value' => 2],
                ['label' => 'Total Transaksi', 'value' => 'Rp 150.000'],
                ['label' => 'Total Dibayar', 'value' => 'Rp 149.999'],
                ['label' => 'Total Dana Dikembalikan', 'value' => 'Rp 9.000'],
                ['label' => 'Pengembalian Belum Dibayar', 'value' => 'Rp 0'],
                ['label' => 'Pengembalian Surplus Sudah Dibayar', 'value' => 'Rp 0'],
                ['label' => 'Sisa Pengembalian Belum Dibayar', 'value' => 'Rp 0'],
                ['label' => 'Net Dibayar', 'value' => 'Rp 140.999'],
                ['label' => 'Sisa Piutang', 'value' => 'Rp 9.001'],
            ],
            'rows' => [
                [
                    'date' => '07 Januari 2030',
                    'note_id' => 'note-1',
                    'customer_name' => 'Budi',
                    'total' => 'Rp 100.000',
                    'paid' => 'Rp 99.999',
                    'refund' => 'Rp 9.000',
                    'refund_due' => 'Rp 0',
                    'surplus_refund_paid' => 'Rp 0',
                    'remaining_refund_due' => 'Rp 0',
                    'net_paid' => 'Rp 90.999',
                    'outstanding' => 'Rp 9.001',
                    'status' => 'Ada Pengembalian Dana',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Laporan Transaksi', $html);
        $this->assertStringContainsString('Jumlah Nota', $html);
        $this->assertStringContainsString('Total Dana Dikembalikan', $html);
        $this->assertStringContainsString('Pengembalian Belum Dibayar', $html);
        $this->assertStringContainsString('Pengembalian Surplus Sudah Dibayar', $html);
        $this->assertStringContainsString('Sisa Pengembalian Belum Dibayar', $html);
        $this->assertStringContainsString('Sisa Piutang', $html);
        $this->assertStringContainsString('Ringkasan Utama', $html);
        $this->assertStringNotContainsString('Catatan Laporan', $html);
        $this->assertStringNotContainsString('Detail lengkap tersedia di Excel', $html);
        $this->assertStringNotContainsString('Ada Pengembalian Dana', $html);
    }

    public function test_pdf_view_uses_owner_readable_report_sections_not_detail_tables(): void
    {
        $html = view('admin.reporting.transaction_summary.export_pdf', [
            'title' => 'Laporan Transaksi',
            'periodLabel' => '01 Januari 2030 s/d 31 Januari 2030',
            'generatedAt' => '31 Januari 2030 10:00',
            'summaryItems' => [
                ['label' => 'Jumlah Nota', 'value' => 2],
                ['label' => 'Total Nilai Transaksi', 'value' => 'Rp 150.000'],
                ['label' => 'Dana Dikembalikan', 'value' => 'Rp 9.000'],
                ['label' => 'Sisa Tagihan', 'value' => 'Rp 9.001'],
            ],
            'rows' => [
                [
                    'date' => '07 Januari 2030',
                    'note_id' => 'note-1',
                    'customer_name' => 'Budi',
                    'total' => 'Rp 100.000',
                    'paid' => 'Rp 99.999',
                    'refund' => 'Rp 9.000',
                    'refund_due' => 'Rp 0',
                    'surplus_refund_paid' => 'Rp 0',
                    'remaining_refund_due' => 'Rp 0',
                    'net_paid' => 'Rp 90.999',
                    'outstanding' => 'Rp 9.001',
                    'status' => 'Ada Pengembalian Dana',
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
            'email' => $role . '-transaction-report-pdf-export@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
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

    private function seedCustomerPayment(string $id, int $amountRupiah, string $paidAt): void
    {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ]);
    }

    private function seedPaymentAllocation(string $id, string $paymentId, string $noteId, int $amountRupiah): void
    {
        DB::table('payment_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
        ]);
    }

    private function seedCustomerRefund(
        string $id,
        string $paymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        string $reason,
    ): void {
        DB::table('customer_refunds')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
            'refunded_at' => $refundedAt,
            'reason' => $reason,
        ]);
    }
}
