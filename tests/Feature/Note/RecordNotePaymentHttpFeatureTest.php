<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RecordNotePaymentHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_cashier_can_record_note_payment_and_store_component_allocations(): void
    {
        $this->loginAsKasir();
        $user = User::query()->create([
            'name' => 'Kasir Aktif',
            'email' => 'cashier@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-1',
            'current_revision_id' => 'note-1-r001',
            'latest_revision_number' => 1,
            'customer_name' => 'Budi',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 150000,
        ]);

        DB::table('work_items')->insert([
            ['id' => 'wi-1', 'note_id' => 'note-1', 'line_no' => 1, 'transaction_type' => WorkItem::TYPE_SERVICE_ONLY, 'status' => WorkItem::STATUS_OPEN, 'subtotal_rupiah' => 50000],
            ['id' => 'wi-2', 'note_id' => 'note-1', 'line_no' => 2, 'transaction_type' => WorkItem::TYPE_SERVICE_ONLY, 'status' => WorkItem::STATUS_OPEN, 'subtotal_rupiah' => 100000],
        ]);

        DB::table('work_item_service_details')->insert([
            ['work_item_id' => 'wi-1', 'service_name' => 'Servis A', 'service_price_rupiah' => 50000, 'part_source' => ServiceDetail::PART_SOURCE_NONE],
            ['work_item_id' => 'wi-2', 'service_name' => 'Servis B', 'service_price_rupiah' => 100000, 'part_source' => ServiceDetail::PART_SOURCE_NONE],
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-1-r001',
            'note_root_id' => 'note-1',
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'selected row payment fixture',
            'customer_name' => 'Budi',
            'customer_phone' => null,
            'transaction_date' => $today,
            'grand_total_rupiah' => 150000,
            'line_count' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('note_revision_lines')->insert([
            [
                'id' => 'note-1-r001-l001',
                'note_revision_id' => 'note-1-r001',
                'work_item_root_id' => 'wi-1',
                'line_no' => 1,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => 'Servis A',
                'service_price_rupiah' => 50000,
                'subtotal_rupiah' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'note-1-r001-l002',
                'note_revision_id' => 'note-1-r001',
                'work_item_root_id' => 'wi-2',
                'line_no' => 2,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => 'Servis B',
                'service_price_rupiah' => 100000,
                'subtotal_rupiah' => 100000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->post('/cashier/notes/note-1/payments', [
            'selected_row_ids' => ['wi-1::service_fee::wi-1'],
            'payment_method' => 'cash',
            'paid_at' => $today,
            'amount_received' => 70000,
        ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));
        $this->assertDatabaseHas('customer_payments', [
            'amount_rupiah' => 50000,
            'paid_at' => $today,
            'payment_method' => 'cash',
        ]);

        $paymentId = (string) DB::table('customer_payments')->value('id');
        $this->assertNotSame('', $paymentId);

        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => $paymentId,
            'amount_paid_rupiah' => 50000,
            'amount_received_rupiah' => 70000,
            'change_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'allocated_amount_rupiah' => 50000,
        ]);
    }

    public function test_rejects_selected_row_payment_when_note_already_paid_via_legacy_allocation(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Legacy Paid',
            'email' => 'cashier-legacy-paid@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-legacy-paid-1',
            'current_revision_id' => 'note-legacy-paid-1-r001',
            'latest_revision_number' => 1,
            'customer_name' => 'Budi Legacy',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 50000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'wi-legacy-paid-1',
            'note_id' => 'note-legacy-paid-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'wi-legacy-paid-1',
            'service_name' => 'Servis Legacy Paid',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-legacy-paid-1-r001',
            'note_root_id' => 'note-legacy-paid-1',
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'legacy paid selected row characterization',
            'customer_name' => 'Budi Legacy',
            'customer_phone' => null,
            'transaction_date' => $today,
            'grand_total_rupiah' => 50000,
            'line_count' => 1,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);

        DB::table('note_revision_lines')->insert([
            'id' => 'note-legacy-paid-1-r001-l001',
            'note_revision_id' => 'note-legacy-paid-1-r001',
            'work_item_root_id' => 'wi-legacy-paid-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'service_label' => 'Servis Legacy Paid',
            'service_price_rupiah' => 50000,
            'subtotal_rupiah' => 50000,
            'payload' => null,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'legacy-payment-1',
            'amount_rupiah' => 50000,
            'paid_at' => $today,
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'legacy-allocation-1',
            'customer_payment_id' => 'legacy-payment-1',
            'note_id' => 'note-legacy-paid-1',
            'amount_rupiah' => 50000,
        ]);

        $response = $this->actingAs($user)->post('/cashier/notes/note-legacy-paid-1/payments', [
            'selected_row_ids' => ['wi-legacy-paid-1'],
            'payment_method' => 'cash',
            'paid_at' => $today,
            'amount_received' => 50000,
        ]);

        $response->assertSessionHasErrors([
            'payment' => 'Hanya billing row outstanding yang boleh dipilih untuk pembayaran.',
        ]);

        $this->assertSame(1, DB::table('customer_payments')->count());
        $this->assertSame(0, DB::table('payment_component_allocations')->count());
    }

    public function test_selected_row_payment_uses_combined_legacy_and_component_allocations(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Mixed Allocation',
            'email' => 'cashier-mixed-allocation@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-mixed-allocation-1',
            'current_revision_id' => 'note-mixed-allocation-1-r001',
            'latest_revision_number' => 1,
            'customer_name' => 'Budi Mixed',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 100000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'wi-mixed-allocation-1',
            'note_id' => 'note-mixed-allocation-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 100000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'wi-mixed-allocation-1',
            'service_name' => 'Servis Mixed Allocation',
            'service_price_rupiah' => 100000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-mixed-allocation-1-r001',
            'note_root_id' => 'note-mixed-allocation-1',
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'mixed allocation selected row characterization',
            'customer_name' => 'Budi Mixed',
            'customer_phone' => null,
            'transaction_date' => $today,
            'grand_total_rupiah' => 100000,
            'line_count' => 1,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);

        DB::table('note_revision_lines')->insert([
            'id' => 'note-mixed-allocation-1-r001-l001',
            'note_revision_id' => 'note-mixed-allocation-1-r001',
            'work_item_root_id' => 'wi-mixed-allocation-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'service_label' => 'Servis Mixed Allocation',
            'service_price_rupiah' => 100000,
            'subtotal_rupiah' => 100000,
            'payload' => null,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);

        DB::table('customer_payments')->insert([
            [
                'id' => 'legacy-payment-mixed-1',
                'amount_rupiah' => 40000,
                'paid_at' => $today,
            ],
            [
                'id' => 'component-payment-mixed-1',
                'amount_rupiah' => 10000,
                'paid_at' => $today,
            ],
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'legacy-allocation-mixed-1',
            'customer_payment_id' => 'legacy-payment-mixed-1',
            'note_id' => 'note-mixed-allocation-1',
            'amount_rupiah' => 40000,
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => 'component-allocation-mixed-1',
            'customer_payment_id' => 'component-payment-mixed-1',
            'note_id' => 'note-mixed-allocation-1',
            'work_item_id' => 'wi-mixed-allocation-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-mixed-allocation-1',
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 10000,
            'allocation_priority' => 1,
        ]);

        $response = $this->actingAs($user)->post('/cashier/notes/note-mixed-allocation-1/payments', [
            'selected_row_ids' => ['wi-mixed-allocation-1'],
            'payment_method' => 'cash',
            'paid_at' => $today,
            'amount_received' => 90000,
        ]);

        $response->assertSessionHasNoErrors();

        $newPayment = DB::table('customer_payments')
            ->whereNotIn('id', ['legacy-payment-mixed-1', 'component-payment-mixed-1'])
            ->first();

        $this->assertNotNull($newPayment);
        $this->assertSame(50000, (int) $newPayment->amount_rupiah);

        $componentTotal = (int) DB::table('payment_component_allocations')
            ->where('note_id', 'note-mixed-allocation-1')
            ->sum('allocated_amount_rupiah');

        $legacyTotal = (int) DB::table('payment_allocations')
            ->where('note_id', 'note-mixed-allocation-1')
            ->sum('amount_rupiah');

        $this->assertSame(60000, $componentTotal);
        $this->assertSame(40000, $legacyTotal);
        $this->assertSame(100000, $legacyTotal + $componentTotal);
    }


    public function test_rejects_selected_canceled_row_payment_and_keeps_payment_tables_unchanged(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Canceled Selected Row',
            'email' => 'cashier-canceled-selected-row@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-canceled-selected-1',
            'current_revision_id' => 'note-canceled-selected-1-r001',
            'latest_revision_number' => 1,
            'customer_name' => 'Budi Canceled Selected',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 100000,
        ]);

        DB::table('work_items')->insert([
            [
                'id' => 'wi-active-selected-1',
                'note_id' => 'note-canceled-selected-1',
                'line_no' => 1,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'subtotal_rupiah' => 50000,
            ],
            [
                'id' => 'wi-canceled-selected-1',
                'note_id' => 'note-canceled-selected-1',
                'line_no' => 2,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_CANCELED,
                'subtotal_rupiah' => 50000,
            ],
        ]);

        DB::table('work_item_service_details')->insert([
            [
                'work_item_id' => 'wi-active-selected-1',
                'service_name' => 'Servis Aktif',
                'service_price_rupiah' => 50000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
            [
                'work_item_id' => 'wi-canceled-selected-1',
                'service_name' => 'Servis Batal',
                'service_price_rupiah' => 50000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-canceled-selected-1-r001',
            'note_root_id' => 'note-canceled-selected-1',
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'selected canceled row payment regression',
            'customer_name' => 'Budi Canceled Selected',
            'customer_phone' => null,
            'transaction_date' => $today,
            'grand_total_rupiah' => 100000,
            'line_count' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('note_revision_lines')->insert([
            [
                'id' => 'note-canceled-selected-1-r001-l001',
                'note_revision_id' => 'note-canceled-selected-1-r001',
                'work_item_root_id' => 'wi-active-selected-1',
                'line_no' => 1,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => 'Servis Aktif',
                'service_price_rupiah' => 50000,
                'subtotal_rupiah' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'note-canceled-selected-1-r001-l002',
                'note_revision_id' => 'note-canceled-selected-1-r001',
                'work_item_root_id' => 'wi-canceled-selected-1',
                'line_no' => 2,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_CANCELED,
                'service_label' => 'Servis Batal',
                'service_price_rupiah' => 50000,
                'subtotal_rupiah' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.show', ['noteId' => 'note-canceled-selected-1']))
            ->post(route('cashier.notes.payments.store', ['noteId' => 'note-canceled-selected-1']), [
                'selected_row_ids' => ['wi-canceled-selected-1::service_fee::wi-canceled-selected-1'],
                'payment_scope' => 'partial',
                'payment_method' => 'cash',
                'paid_at' => $today,
                'amount_paid' => '50000',
                'amount_received' => '50000',
            ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-canceled-selected-1']));
        $response->assertSessionHasErrors(['payment']);

        $this->assertSame(0, DB::table('customer_payments')->count());
        $this->assertSame(0, DB::table('payment_allocations')->count());
        $this->assertSame(0, DB::table('payment_component_allocations')->count());
    }


    public function test_selected_row_payment_can_replace_refunded_component_amount_via_http(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund Replacement Payment',
            'email' => 'cashier-refund-replacement-payment@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = date('Y-m-d');

        DB::table('notes')->insert([
            'id' => 'note-refund-repay-http-1',
            'current_revision_id' => 'note-refund-repay-http-1-r001',
            'latest_revision_number' => 1,
            'customer_name' => 'Budi Refund Repay HTTP',
            'transaction_date' => $today,
            'note_state' => 'open',
            'total_rupiah' => 300000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'wi-refund-repay-http-1',
            'note_id' => 'note-refund-repay-http-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 300000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'wi-refund-repay-http-1',
            'service_name' => 'Servis Refund Repay HTTP',
            'service_price_rupiah' => 300000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-refund-repay-http-1-r001',
            'note_root_id' => 'note-refund-repay-http-1',
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'refund repay selected row HTTP regression',
            'customer_name' => 'Budi Refund Repay HTTP',
            'customer_phone' => null,
            'transaction_date' => $today,
            'grand_total_rupiah' => 300000,
            'line_count' => 1,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);

        DB::table('note_revision_lines')->insert([
            'id' => 'note-refund-repay-http-1-r001-l001',
            'note_revision_id' => 'note-refund-repay-http-1-r001',
            'work_item_root_id' => 'wi-refund-repay-http-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'service_label' => 'Servis Refund Repay HTTP',
            'service_price_rupiah' => 300000,
            'subtotal_rupiah' => 300000,
            'payload' => null,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'payment-refund-repay-http-old-1',
            'amount_rupiah' => 300000,
            'paid_at' => $today,
            'payment_method' => 'cash',
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-refund-repay-http-old-1',
            'customer_payment_id' => 'payment-refund-repay-http-old-1',
            'note_id' => 'note-refund-repay-http-1',
            'work_item_id' => 'wi-refund-repay-http-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-refund-repay-http-1',
            'component_amount_rupiah_snapshot' => 300000,
            'allocated_amount_rupiah' => 300000,
            'allocation_priority' => 1,
        ]);

        DB::table('customer_refunds')->insert([
            'id' => 'refund-refund-repay-http-1',
            'customer_payment_id' => 'payment-refund-repay-http-old-1',
            'note_id' => 'note-refund-repay-http-1',
            'amount_rupiah' => 100000,
            'refunded_at' => $today,
            'reason' => 'Refund sebagian sebelum bayar ulang via HTTP',
        ]);

        DB::table('refund_component_allocations')->insert([
            'id' => 'rca-refund-repay-http-1',
            'customer_refund_id' => 'refund-refund-repay-http-1',
            'customer_payment_id' => 'payment-refund-repay-http-old-1',
            'note_id' => 'note-refund-repay-http-1',
            'work_item_id' => 'wi-refund-repay-http-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-refund-repay-http-1',
            'refunded_amount_rupiah' => 100000,
            'refund_priority' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.show', ['noteId' => 'note-refund-repay-http-1']))
            ->post(route('cashier.notes.payments.store', ['noteId' => 'note-refund-repay-http-1']), [
                'selected_row_ids' => ['wi-refund-repay-http-1::service_fee::wi-refund-repay-http-1'],
                'payment_scope' => 'partial',
                'payment_method' => 'cash',
                'paid_at' => $today,
                'amount_paid' => '100000',
                'amount_received' => '100000',
            ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-refund-repay-http-1']));
        $response->assertSessionHasNoErrors();

        $newPayment = DB::table('customer_payments')
            ->where('id', '!=', 'payment-refund-repay-http-old-1')
            ->first();

        $this->assertNotNull($newPayment);
        $this->assertSame(100000, (int) $newPayment->amount_rupiah);

        self::assertSame(
            400000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-refund-repay-http-1')
                ->sum('allocated_amount_rupiah')
        );

        self::assertSame(
            100000,
            (int) DB::table('refund_component_allocations')
                ->where('note_id', 'note-refund-repay-http-1')
                ->sum('refunded_amount_rupiah')
        );

        self::assertSame(
            300000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-refund-repay-http-1')
                ->sum('allocated_amount_rupiah')
                - (int) DB::table('refund_component_allocations')
                    ->where('note_id', 'note-refund-repay-http-1')
                    ->sum('refunded_amount_rupiah')
        );
    }



}
