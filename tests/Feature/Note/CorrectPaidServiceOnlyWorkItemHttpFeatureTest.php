<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CorrectPaidServiceOnlyWorkItemHttpFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_cannot_correct_closed_paid_service_only_work_item_via_http(): void
    {
        $this->loginAsKasir();
        $user = $this->createCashierUser('cashier@example.test');
        $this->seedPaidServiceOnlyNote('note-1', 'wi-1', 50000);

        $response = $this->actingAs($user)->post('/cashier/notes/note-1/corrections/service-only', [
            'line_no' => 1,
            'service_name' => 'Servis Koreksi',
            'service_price_rupiah' => 40000,
            'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            'reason' => 'Harga awal terlalu tinggi.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-1',
            'service_name' => 'Servis A',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);
        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 50000,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'paid_service_only_work_item_corrected',
        ]);
    }

    public function test_cashier_cannot_access_closed_paid_service_only_validation_flow(): void
    {
        $this->loginAsKasir();
        $user = $this->createCashierUser('cashier-invalid-part-source@example.test');
        $this->seedPaidServiceOnlyNote('note-1', 'wi-1', 50000);

        $response = $this->from(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->actingAs($user)
            ->post('/cashier/notes/note-1/corrections/service-only', [
                'base_revision_id' => $this->revisionBaseForTest('note-1'),
                'line_no' => 1,
                'service_name' => 'Servis Koreksi',
                'service_price_rupiah' => 40000,
                'part_source' => 'store_stock',
                'reason' => 'Part source tidak valid.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-1',
            'service_name' => 'Servis A',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'paid_service_only_work_item_corrected',
        ]);
    }

    public function test_cashier_cannot_correct_unpaid_service_only_work_item_via_http(): void
    {
        $this->loginAsKasir();
        $user = $this->createCashierUser('cashier-unpaid-note@example.test');
        $this->seedUnpaidServiceOnlyNote('note-1', 'wi-1', 50000);

        $response = $this->from(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->actingAs($user)
            ->post('/cashier/notes/note-1/corrections/service-only', [
                'base_revision_id' => $this->revisionBaseForTest('note-1'),
                'line_no' => 1,
                'service_name' => 'Servis Koreksi',
                'service_price_rupiah' => 40000,
                'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
                'reason' => 'Tidak boleh untuk note unpaid.',
            ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));
        $response->assertSessionHasErrors(['correction']);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-1',
            'service_name' => 'Servis A',
            'service_price_rupiah' => 50000,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'paid_service_only_work_item_corrected',
        ]);
    }

    public function test_cashier_cannot_access_closed_paid_non_service_only_correction_flow(): void
    {
        $this->loginAsKasir();
        $user = $this->createCashierUser('cashier-non-service-only@example.test');
        $this->seedPaidStoreStockOnlyNote('note-1', 'wi-1', 40000);

        $response = $this->from(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->actingAs($user)
            ->post('/cashier/notes/note-1/corrections/service-only', [
                'base_revision_id' => $this->revisionBaseForTest('note-1'),
                'line_no' => 1,
                'service_name' => 'Servis Koreksi',
                'service_price_rupiah' => 30000,
                'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
                'reason' => 'Tidak boleh untuk tipe lain.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            'subtotal_rupiah' => 40000,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'paid_service_only_work_item_corrected',
        ]);
    }

    private function createCashierUser(string $email): User
    {
        $user = User::query()->create([
            'name' => 'Kasir',
            'email' => $email,
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedPaidServiceOnlyNote(string $noteId, string $workItemId, int $subtotalRupiah): void
    {
        $this->seedNoteBase($noteId, 'Budi', now()->toDateString(), $subtotalRupiah, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, $subtotalRupiah);
        $this->seedServiceDetailBase($workItemId, 'Servis A', $subtotalRupiah, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('cp-1', $subtotalRupiah, now()->toDateString());
        $this->seedPaymentAllocationBase('pa-1', 'cp-1', $noteId, $subtotalRupiah);
    }

    private function seedUnpaidServiceOnlyNote(string $noteId, string $workItemId, int $subtotalRupiah): void
    {
        $this->seedNoteBase($noteId, 'Budi', now()->toDateString(), $subtotalRupiah, 'open');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, $subtotalRupiah);
        $this->seedServiceDetailBase($workItemId, 'Servis A', $subtotalRupiah, ServiceDetail::PART_SOURCE_NONE);
    }

    private function seedPaidStoreStockOnlyNote(string $noteId, string $workItemId, int $subtotalRupiah): void
    {
        $this->seedNotePaymentProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, $subtotalRupiah);
        $this->seedNoteBase($noteId, 'Budi', now()->toDateString(), $subtotalRupiah, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, $subtotalRupiah);
        $this->seedStoreStockLineBase('store-line-1', $workItemId, 'product-1', 1, $subtotalRupiah);
        $this->seedCustomerPaymentBase('cp-1', $subtotalRupiah, now()->toDateString());
        $this->seedPaymentAllocationBase('pa-1', 'cp-1', $noteId, $subtotalRupiah);
    }
}
