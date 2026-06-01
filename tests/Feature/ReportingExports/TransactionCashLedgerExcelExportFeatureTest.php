<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class TransactionCashLedgerExcelExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_transaction_cash_ledger_as_xlsx_with_numeric_rupiah_cells(): void
    {
        $this->seedCashInEvent('note-1', 'wi-1', 'pay-1', '2030-01-02', 8000, 'Budi');
        $this->seedCashInEvent('note-2', 'wi-2', 'pay-2', '2030-01-03', 4000, 'Sari');
        $this->seedCashOutEvent('note-1', 'wi-1', 'pay-1', 'ref-1', '2030-01-04', 1000, 'Refund');
        $this->seedCashInEvent('note-outside', 'wi-outside', 'pay-outside', '2030-02-01', 50000, 'Outside');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.export_excel', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertDownload('laporan-buku-kas-transaksi-2030-01-01-sampai-2030-01-31.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'transaction-cash-ledger-report-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);

        $this->assertSame(['Ringkasan', 'Detail Event Kas', 'Rekap Per Tanggal'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Detail Event Kas');
        $period = $spreadsheet->getSheetByName('Rekap Per Tanggal');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);
        $this->assertNotNull($period);

        $this->assertSame('Laporan Buku Kas Transaksi', $summary->getCell('A1')->getValue());
        $this->assertSame('01 Januari 2030 s/d 31 Januari 2030', $summary->getCell('B2')->getValue());
        $this->assertSame(3, $summary->getCell('B6')->getValue());
        $this->assertSame(12000, $summary->getCell('B7')->getValue());
        $this->assertSame(12000, $summary->getCell('B8')->getValue());
        $this->assertSame(0, $summary->getCell('B9')->getValue());
        $this->assertSame(1000, $summary->getCell('B10')->getValue());
        $this->assertSame(11000, $summary->getCell('B11')->getValue());

        $this->assertSame('Tanggal Event', $detail->getCell('B1')->getValue());
        $this->assertSame('Metode Pembayaran', $detail->getCell('G1')->getValue());
        $this->assertSame('Nominal', $detail->getCell('H1')->getValue());
        $this->assertSame('Tabel Sumber', $detail->getCell('K1')->getValue());
        $this->assertSame('ID Sumber', $detail->getCell('L1')->getValue());
        $this->assertSame('ID Disposisi Sumber', $detail->getCell('M1')->getValue());
        $this->assertSame('02 Januari 2030', $detail->getCell('B2')->getValue());
        $this->assertSame('note-1', $detail->getCell('C2')->getValue());
        $this->assertSame('Alokasi Pembayaran', $detail->getCell('E2')->getValue());
        $this->assertSame('Masuk', $detail->getCell('F2')->getValue());
        $this->assertSame('Tunai', $detail->getCell('G2')->getValue());
        $this->assertSame(8000, $detail->getCell('H2')->getValue());
        $this->assertSame('payment_allocations', $detail->getCell('K2')->getValue());
        $this->assertSame('pay-1', $detail->getCell('L2')->getValue());
        $this->assertNull($detail->getCell('M2')->getValue());

        $this->assertSame('04 Januari 2030', $detail->getCell('B4')->getValue());
        $this->assertSame('Pengembalian Dana', $detail->getCell('E4')->getValue());
        $this->assertSame('Keluar', $detail->getCell('F4')->getValue());
        $this->assertSame('-', $detail->getCell('G4')->getValue());
        $this->assertSame(1000, $detail->getCell('H4')->getValue());
        $this->assertSame('customer_refunds', $detail->getCell('K4')->getValue());
        $this->assertSame('ref-1', $detail->getCell('L4')->getValue());
        $this->assertNull($detail->getCell('M4')->getValue());
        $this->assertNull($detail->getCell('C5')->getValue());

        $this->assertSame('02 Januari 2030', $period->getCell('A2')->getValue());
        $this->assertSame(1, $period->getCell('B2')->getValue());
        $this->assertSame(8000, $period->getCell('C2')->getValue());
        $this->assertSame(8000, $period->getCell('D2')->getValue());
        $this->assertSame(0, $period->getCell('E2')->getValue());
        $this->assertSame(0, $period->getCell('F2')->getValue());
        $this->assertSame(8000, $period->getCell('G2')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    public function test_kasir_cannot_export_transaction_cash_ledger(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(
            route('admin.reports.transaction_cash_ledger.export_excel')
        );

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_transaction_cash_ledger_excel_export_rejects_range_longer_than_366_days(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.export_excel', [
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
            'email' => $role . '-transaction-cash-ledger-report-export@example.test',
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
            'payment_method' => 'cash',
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
