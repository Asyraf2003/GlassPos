<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionReportPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_transaction_report_page(): void
    {
        $this->get(route('admin.reports.transaction_summary.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_transaction_report_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.reports.transaction_summary.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_transaction_report_page_and_see_sidebar_routes(): void
    {
        $this->seedNote('note-1', 'Budi', '2030-01-07', 100000);
        $this->seedNote('note-2', 'Siti', '2030-01-09', 50000);

        $this->seedWorkItem('wi-1', 'note-1', 1, 100000);
        $this->seedWorkItem('wi-2', 'note-2', 1, 50000);

        $this->seedCustomerPayment('payment-1', 70000, '2030-01-07');
        $this->seedCustomerPayment('payment-2', 50000, '2030-01-09');

        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 99999);
        $this->seedPaymentAllocation('allocation-2', 'payment-2', 'note-2', 50000);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 70000,
                'allocation_priority' => 1,
            ],
        ]);

        $this->seedCustomerRefund('refund-1', 'payment-1', 'note-1', 9000, '2030-01-08', 'Koreksi');

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'rca-1',
                'customer_refund_id' => 'refund-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'refunded_amount_rupiah' => 5000,
                'refund_priority' => 1,
            ],
        ]);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_summary.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Laporan Transaksi');
        $content = (string) preg_replace('/\s+/', ' ', $response->getContent());
        $this->assertStringContainsString('href="' . route('admin.dashboard') . '"', $content);
        $this->assertStringContainsString('data-layout-smart-back', $content);
        $response->assertSee('transaction-report-filter-form', false);
        $response->assertSee('Unduh Excel');
        $response->assertSee('/admin/reports/transactions/export.xlsx', false);
        $response->assertSee('Unduh PDF');
        $response->assertSee('/admin/reports/transactions/export.pdf', false);
        $response->assertSee('01 Januari 2030 s/d 31 Januari 2030');
        $response->assertSee('Rp 150.000');
        $response->assertSee('Rp 149.999');
        $response->assertSee('Rp 9.000');
        $response->assertSee('Rp 140.999');
        $response->assertSee('Rp 9.001');
        $response->assertSee('note-1');
        $response->assertSee('note-2');
        $response->assertSee('Budi');
        $response->assertSee('Siti');
        $response->assertSee(route('admin.reports.transaction_cash_ledger.index'), false);
        $response->assertSee(route('admin.reports.employee_debt.index'), false);
        $response->assertSee(route('admin.reports.operational_profit.index'), false);
        $response->assertSee(route('admin.reports.supplier_payable.index'), false);
        $response->assertSee(route('admin.reports.inventory_stock_value.index'), false);
        $response->assertSee(route('admin.reports.transaction_summary.index'), false);
    }

    public function test_admin_sees_owner_readable_report_sections_on_transaction_report_page(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_summary.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Ringkasan Utama');
        $response->assertSee('Catatan Laporan');
        $response->assertSee('Detail lengkap tersedia di Excel');
    }



    public function test_admin_can_see_surplus_refund_paid_and_remaining_refund_due_on_transaction_report_page(): void
    {
        $this->seedNote('note-surplus-paid-screen', 'Budi Surplus Paid', '2030-01-10', 100000);
        $this->seedWorkItem('wi-surplus-paid-screen', 'note-surplus-paid-screen', 1, 100000);

        $this->seedCustomerPayment('payment-surplus-paid-screen', 100000, '2030-01-10');
        $this->seedPaymentAllocation(
            'allocation-surplus-paid-screen',
            'payment-surplus-paid-screen',
            'note-surplus-paid-screen',
            100000,
        );

        $this->seedCustomerRefund(
            'refund-customer-screen',
            'payment-surplus-paid-screen',
            'note-surplus-paid-screen',
            9000,
            '2030-01-10',
            'Customer refund screen fixture',
        );

        $this->seedRefundDueDisposition(
            'disp-surplus-paid-screen',
            'note-surplus-paid-screen',
            'rev-surplus-paid-screen',
            'settlement-surplus-paid-screen',
            7000,
        );

        $this->seedSurplusRefundPayment(
            'surplus-payment-screen',
            'disp-surplus-paid-screen',
            'note-surplus-paid-screen',
            'rev-surplus-paid-screen',
            'settlement-surplus-paid-screen',
            3000,
            '2030-01-10',
        );

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_summary.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertSee('Surplus Refund Paid');
        $response->assertSee('Sisa Refund Due');
        $response->assertSee('Rp 3.000');
        $response->assertSee('Rp 4.000');
    }

    public function test_admin_can_filter_transaction_report_with_custom_range(): void
    {
        $this->seedNote('note-outside-before', 'Outside Before', '2030-01-04', 10000);
        $this->seedNote('note-inside', 'Inside Range', '2030-01-05', 25000);
        $this->seedNote('note-outside-after', 'Outside After', '2030-01-07', 30000);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_summary.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-05',
                'date_to' => '2030-01-05',
            ])
        );

        $response->assertOk();
        $response->assertSee('05 Januari 2030 s/d 05 Januari 2030');
        $response->assertSee('Rp 25.000');
        $response->assertSee('note-inside');
        $response->assertDontSee('note-outside-before');
        $response->assertDontSee('note-outside-after');
    }

    public function test_custom_range_requires_start_and_end_dates(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.transaction_summary.index', [
                'period_mode' => 'custom',
                'date_from' => '2030-01-05',
            ])
        );

        $response->assertSessionHasErrors('date_to');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-transaction-report@example.test',
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
    private function seedRefundDueDisposition(
        string $id,
        string $noteId,
        string $revisionId,
        string $settlementId,
        int $amountRupiah,
    ): void {
        DB::table('note_revisions')->insert([
            'id' => $revisionId,
            'note_root_id' => $noteId,
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'Report screen refund due fixture',
            'customer_name' => 'Reporting Screen Customer',
            'customer_phone' => null,
            'transaction_date' => '2030-01-10',
            'grand_total_rupiah' => 100000,
            'line_count' => 0,
            'created_at' => '2030-01-10 09:00:00',
            'updated_at' => null,
        ]);

        DB::table('note_revision_settlements')->insert([
            'id' => $settlementId,
            'note_revision_id' => $revisionId,
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 100000,
            'carry_forward_paid_rupiah' => 107000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 107000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => $amountRupiah,
            'settlement_status' => 'overpaid_pending',
            'created_at' => '2030-01-10 09:00:00',
            'updated_at' => null,
        ]);

        DB::table('audit_events')->insert([
            'id' => 'audit-' . $id,
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => $id,
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => 'admin-1',
            'actor_role' => 'admin',
            'reason' => 'Report screen refund due fixture',
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2030-01-10 09:30:00',
            'metadata_json' => null,
        ]);

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => $id,
            'note_revision_settlement_id' => $settlementId,
            'note_root_id' => $noteId,
            'note_revision_id' => $revisionId,
            'disposition_type' => 'refund_due',
            'amount_rupiah' => $amountRupiah,
            'before_pending_rupiah' => $amountRupiah,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2030-01-10 09:30:00',
            'created_at' => '2030-01-10 09:30:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-' . $id,
        ]);
    }

    private function seedSurplusRefundPayment(
        string $id,
        string $dispositionId,
        string $noteId,
        string $revisionId,
        string $settlementId,
        int $amountRupiah,
        string $effectiveDate,
    ): void {
        DB::table('audit_events')->insert([
            'id' => 'audit-' . $id,
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_refund_payment',
            'aggregate_id' => $id,
            'event_name' => 'note_revision_surplus_refund_paid_recorded',
            'actor_id' => 'admin-1',
            'actor_role' => 'admin',
            'reason' => 'Report screen surplus refund paid fixture',
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => $effectiveDate . ' 10:00:00',
            'metadata_json' => null,
        ]);

        DB::table('note_revision_surplus_refund_payments')->insert([
            'id' => $id,
            'note_revision_surplus_disposition_id' => $dispositionId,
            'note_revision_settlement_id' => $settlementId,
            'note_root_id' => $noteId,
            'note_revision_id' => $revisionId,
            'amount_rupiah' => $amountRupiah,
            'effective_date' => $effectiveDate,
            'occurred_at' => $effectiveDate . ' 10:00:00',
            'status' => 'active',
            'idempotency_key' => 'idem-' . $id,
            'audit_event_id' => 'audit-' . $id,
            'created_at' => $effectiveDate . ' 10:00:00',
            'updated_at' => null,
        ]);
    }


}
