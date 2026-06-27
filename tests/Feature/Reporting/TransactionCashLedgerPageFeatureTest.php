<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionCashLedgerPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_transaction_cash_ledger_page(): void
    {
        $this->get(route('admin.reports.transaction_cash_ledger.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_transaction_cash_ledger_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.reports.transaction_cash_ledger.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_transaction_cash_ledger_page_and_see_report_data(): void
    {
        $this->seedCashInEvent('note-1', 'wi-1', 'pay-1', '2026-04-02', 8000, 'Budi');
        $this->seedCashInEvent('note-2', 'wi-2', 'pay-2', '2026-04-03', 4000, 'Sari');
        $this->seedCashOutEvent('note-1', 'wi-1', 'pay-1', 'ref-1', '2026-04-04', 1000, 'Refund');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2026-04-01',
            ])
        );

        $response->assertOk();
        $response->assertSee('Arus Kas Transaksi');
        $response->assertSee('transaction-cash-ledger-filter-form', false);
        $response->assertSee('value="custom"', false);
        $response->assertSee('name="date_from"', false);
        $response->assertSee('name="date_to"', false);
        $response->assertSee('Unduh Excel');
        $response->assertSee('Unduh PDF');
        $response->assertSee('admin/reports/transaction-cash-ledger/export.xlsx', false);
        $response->assertSee('admin/reports/transaction-cash-ledger/export.pdf', false);
        $response->assertSee('period_mode=monthly', false);
        $response->assertSee('reference_date=2026-04-01', false);
        $response->assertSee('Rincian Ringkas');
        $response->assertSee('Kejadian Kas');
        $response->assertSee('Sisa Kas Hari Ini');
        $response->assertSee('Rp 12.000');
        $response->assertSee('Rp 1.000');
        $response->assertSee('Rp 11.000');
        $response->assertDontSee('Tabel Sumber');
        $response->assertDontSee('payment_allocations');
        $response->assertDontSee('customer_refunds');
    }

    public function test_admin_sees_owner_readable_report_sections_on_transaction_cash_ledger_page(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2026-04-01',
            ])
        );

        $response->assertOk();
        $response->assertSee('Ringkasan Utama');
        $response->assertDontSee('Catatan Laporan');
        $response->assertDontSee('Detail lengkap tersedia di Excel');
    }

    public function test_admin_can_see_cash_and_transfer_money_in_split_on_transaction_cash_ledger_page(): void
    {
        $this->seedCashInEvent('note-page-cash', 'wi-page-cash', 'pay-page-cash', '2026-04-02', 85000, 'Cash Page', 'cash');
        $this->seedCashInEvent('note-page-transfer', 'wi-page-transfer', 'pay-page-transfer', '2026-04-02', 30000, 'Transfer Page', 'transfer');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'daily',
                'reference_date' => '2026-04-02',
            ])
        );

        $response->assertOk();
        $response->assertSee('Kas Masuk');
        $response->assertSee('Rp 115.000');
        $response->assertSee('Tunai Masuk');
        $response->assertSee('Rp 85.000');
        $response->assertSee('Transfer Masuk');
        $response->assertSee('Rp 30.000');
    }


    public function test_admin_cash_ledger_page_keeps_payment_method_summary_without_detail_table(): void
    {
        $this->seedCashInEvent('note-page-detail-cash', 'wi-page-detail-cash', 'pay-page-detail-cash', '2026-04-02', 85000, 'Cash Detail', 'cash');
        $this->seedCashInEvent('note-page-detail-transfer', 'wi-page-detail-transfer', 'pay-page-detail-transfer', '2026-04-02', 30000, 'Transfer Detail', 'transfer');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'daily',
                'reference_date' => '2026-04-02',
            ])
        );

        $response->assertOk();
        $response->assertSee('Tunai Masuk');
        $response->assertSee('Transfer Masuk');
        $response->assertDontSee('Detail lengkap tersedia di Excel');
        $response->assertDontSee('Metode Pembayaran');
        $response->assertDontSee('note-page-detail-cash');
        $response->assertDontSee('note-page-detail-transfer');
    }

    public function test_daily_mode_uses_reference_date_only(): void
    {
        $this->seedCashInEvent('note-daily-1', 'wi-daily-1', 'pay-daily-1', '2026-04-02', 7000, 'Daily A');
        $this->seedCashInEvent('note-daily-2', 'wi-daily-2', 'pay-daily-2', '2026-04-03', 9000, 'Daily B');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'daily',
                'reference_date' => '2026-04-02',
            ])
        );

        $response->assertOk();
        $response->assertSee('02 April 2026 s/d 02 April 2026');
        $response->assertSee('02 April 2026');
        $response->assertSee('Rp 7.000');
        $response->assertDontSee('Rp 9.000');
        $response->assertDontSee('note-daily-1');
        $response->assertDontSee('note-daily-2');
    }

    public function test_weekly_mode_uses_monday_to_sunday_range(): void
    {
        $this->seedCashInEvent('note-week-1', 'wi-week-1', 'pay-week-1', '2026-04-06', 5000, 'Week Mon');
        $this->seedCashInEvent('note-week-2', 'wi-week-2', 'pay-week-2', '2026-04-09', 6000, 'Week Thu');
        $this->seedCashInEvent('note-week-3', 'wi-week-3', 'pay-week-3', '2026-04-13', 11000, 'Next Week');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'weekly',
                'reference_date' => '2026-04-09',
            ])
        );

        $response->assertOk();
        $response->assertSee('06 April 2026 s/d 12 April 2026');
        $response->assertSee('Rp 11.000');
        $response->assertDontSee('note-week-1');
        $response->assertDontSee('note-week-2');
        $response->assertDontSee('note-week-3');
    }

    public function test_monthly_mode_uses_first_to_last_day_of_month(): void
    {
        $this->seedCashInEvent('note-month-1', 'wi-month-1', 'pay-month-1', '2026-04-01', 3000, 'Month Start');
        $this->seedCashInEvent('note-month-2', 'wi-month-2', 'pay-month-2', '2026-04-29', 4000, 'Month End');
        $this->seedCashInEvent('note-month-3', 'wi-month-3', 'pay-month-3', '2026-05-01', 9000, 'Next Month');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2026-04-11',
            ])
        );

        $response->assertOk();
        $response->assertSee('01 April 2026 s/d 30 April 2026');
        $response->assertSee('Rp 7.000');
        $response->assertDontSee('note-month-1');
        $response->assertDontSee('note-month-2');
        $response->assertDontSee('note-month-3');
    }

    public function test_custom_mode_uses_explicit_date_range(): void
    {
        $this->seedCashInEvent('note-custom-1', 'wi-custom-1', 'pay-custom-1', '2026-04-02', 7000, 'Custom A');
        $this->seedCashInEvent('note-custom-2', 'wi-custom-2', 'pay-custom-2', '2026-04-04', 9000, 'Custom B');
        $this->seedCashInEvent('note-custom-3', 'wi-custom-3', 'pay-custom-3', '2026-04-05', 11000, 'Outside Custom');

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'custom',
                'date_from' => '2026-04-02',
                'date_to' => '2026-04-04',
            ])
        );

        $response->assertOk();
        $response->assertSee('02 April 2026 s/d 04 April 2026');
        $response->assertSee('02 April 2026');
        $response->assertSee('04 April 2026');
        $response->assertSee('Rp 16.000');
        $response->assertDontSee('note-custom-1');
        $response->assertDontSee('note-custom-2');
        $response->assertDontSee('note-custom-3');
    }

    public function test_custom_mode_requires_explicit_date_range(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.reports.transaction_cash_ledger.index'))
            ->get(route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'custom',
            ]));

        $response->assertRedirect(route('admin.reports.transaction_cash_ledger.index'));
        $response->assertSessionHasErrors(['date_from', 'date_to']);
    }

    public function test_custom_mode_rejects_invalid_date_order(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.reports.transaction_cash_ledger.index'))
            ->get(route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'custom',
                'date_from' => '2026-04-04',
                'date_to' => '2026-04-02',
            ]));

        $response->assertRedirect(route('admin.reports.transaction_cash_ledger.index'));
        $response->assertSessionHasErrors(['date_from']);
    }


    public function test_unknown_period_mode_is_rejected(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.reports.transaction_cash_ledger.index'))
            ->get(route('admin.reports.transaction_cash_ledger.index', [
                'period_mode' => 'quarterly',
                'reference_date' => '2026-04-01',
            ]));

        $response->assertRedirect(route('admin.reports.transaction_cash_ledger.index'));
        $response->assertSessionHasErrors(['period_mode']);
    }


    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-reporting-page@example.test',
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
        string $customerName,
        ?string $paymentMethod = null,
    ): void {
        $this->seedNote($noteId, $customerName, $paidAt, $amountRupiah);
        $this->seedWorkItem($workItemId, $noteId, 1, $amountRupiah);

        $paymentRow = [
            'id' => $paymentId,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ];

        if ($paymentMethod !== null) {
            $paymentRow['payment_method'] = $paymentMethod;
        }

        DB::table('customer_payments')->insert($paymentRow);

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
