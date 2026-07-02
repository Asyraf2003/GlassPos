<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class TransactionReportExcelExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_transaction_report_as_xlsx_with_numeric_rupiah_cells(): void
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
            route('admin.reports.transaction_summary.export_excel', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertDownload('laporan-transaksi-2030-01-01-sampai-2030-01-31.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'transaction-report-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);

        $this->assertSame(['Ringkasan', 'Rincian Nota', 'Rekap Per Tanggal', 'Rekap Per Pelanggan'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Rincian Nota');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);

        $this->assertSame('Laporan Transaksi', $summary->getCell('A1')->getValue());
        $this->assertSame('Januari 2030', $summary->getCell('B2')->getValue());
        $this->assertSame(2, $summary->getCell('B6')->getValue());
        $this->assertSame(150000, $summary->getCell('B7')->getValue());
        $this->assertSame(149999, $summary->getCell('B8')->getValue());
        $this->assertSame(9000, $summary->getCell('B9')->getValue());
        $this->assertSame(0, $summary->getCell('B10')->getValue());
        $this->assertSame('Total Kelebihan Bayar Sudah Dikembalikan', $summary->getCell('A11')->getValue());
        $this->assertSame(0, $summary->getCell('B11')->getValue());
        $this->assertSame('Total Sisa Refund Belum Dibayar', $summary->getCell('A12')->getValue());
        $this->assertSame(0, $summary->getCell('B12')->getValue());
        $this->assertSame(140999, $summary->getCell('B13')->getValue());
        $this->assertSame(9001, $summary->getCell('B14')->getValue());

        $this->assertSame('ID Nota', $detail->getCell('B1')->getValue());
        $this->assertSame('note-1', $detail->getCell('B2')->getValue());
        $this->assertSame('Budi', $detail->getCell('D2')->getValue());
        $this->assertSame(100000, $detail->getCell('E2')->getValue());
        $this->assertSame(99999, $detail->getCell('F2')->getValue());
        $this->assertSame(9000, $detail->getCell('G2')->getValue());
        $this->assertSame(0, $detail->getCell('H2')->getValue());
        $this->assertSame('Kelebihan Bayar Sudah Dikembalikan', $detail->getCell('I1')->getValue());
        $this->assertSame(0, $detail->getCell('I2')->getValue());
        $this->assertSame('Sisa Refund Belum Dibayar', $detail->getCell('J1')->getValue());
        $this->assertSame(0, $detail->getCell('J2')->getValue());
        $this->assertSame(90999, $detail->getCell('K2')->getValue());
        $this->assertSame(9001, $detail->getCell('L2')->getValue());
        $this->assertSame('Ada Uang Dikembalikan', $detail->getCell('M2')->getValue());
        $this->assertSame('note-2', $detail->getCell('B3')->getValue());
        $this->assertNull($detail->getCell('B4')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_kasir_cannot_export_transaction_report(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.transaction_summary.export_excel')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_excel_export_rejects_range_longer_than_366_days(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_summary.export_excel', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2031-01-02',
            ])
        );

        $response->assertStatus(422);
        $response->assertSeeText('Export Excel maksimal 366 hari.');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-transaction-report-export@example.test',
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
