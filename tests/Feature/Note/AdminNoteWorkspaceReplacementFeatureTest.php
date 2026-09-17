<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class AdminNoteWorkspaceReplacementFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_admin_can_open_and_submit_closed_note_workspace_replacement_as_revision(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-14 days'));
        $today = date('Y-m-d');

        $this->seedNoteBase('note-admin-1', 'Budi Lama', $oldDate, 50000, 'closed');
        $this->seedWorkItemBase('wi-admin-old-1', 'note-admin-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-admin-old-1', 'Servis Lama', 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('pay-admin-1', 50000, $oldDate);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-admin-old-1',
            'customer_payment_id' => 'pay-admin-1',
            'note_id' => 'note-admin-1',
            'work_item_id' => 'wi-admin-old-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-admin-old-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 20,
        ]);

        $show = $this->actingAs($user)
            ->get(route('admin.notes.show', ['noteId' => 'note-admin-1']));

        $show->assertOk();
        $show->assertSee(route('admin.notes.workspace.edit', ['noteId' => 'note-admin-1']), false);

        $edit = $this->actingAs($user)
            ->get(route('admin.notes.workspace.edit', ['noteId' => 'note-admin-1']));

        $edit->assertOk();
        $edit->assertSee('Edit Nota');
        $edit->assertSee('Budi Lama');
        $edit->assertSee('Servis Lama');
        $edit->assertSee($today);
        $edit->assertSee(route('admin.notes.workspace.update', ['noteId' => 'note-admin-1']), false);

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-admin-1']),
            [
            'base_revision_id' => $this->revisionBaseForTest('note-admin-1'),
                'note' => [
                    'customer_name' => 'Budi Baru Admin',
                    'customer_phone' => '08123456789',
                    'transaction_date' => $today,
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'none',
                        'service' => [
                            'name' => 'Servis Baru Admin',
                            'price_rupiah' => '70000',
                            'notes' => null,
                        ],
                        'product_lines' => [],
                        'external_purchase_lines' => [],
                    ],
                ],
                'inline_payment' => [
                    'decision' => 'skip',
                    'payment_method' => null,
                    'paid_at' => null,
                    'amount_paid_rupiah' => null,
                    'amount_received_rupiah' => null,
                ],
            ],
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-admin-1']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notes', [
            'id' => 'note-admin-1',
            'customer_name' => 'Budi Baru Admin',
            'customer_phone' => '08123456789',
            'transaction_date' => $today,
            'total_rupiah' => 70000,
            'latest_revision_number' => 2,
        ]);

        $this->assertDatabaseMissing('work_items', [
            'id' => 'wi-admin-old-1',
        ]);

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-admin-1',
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'subtotal_rupiah' => 70000,
        ]);

        $this->assertDatabaseHas('note_revisions', [
            'note_root_id' => 'note-admin-1',
            'revision_number' => 2,
            'customer_name' => 'Budi Baru Admin',
            'grand_total_rupiah' => 70000,
        ]);

        $this->assertDatabaseMissing('payment_component_allocations', [
            'work_item_id' => 'wi-admin-old-1',
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => 'pay-admin-1',
            'note_id' => 'note-admin-1',
            'component_type' => 'service_fee',
            'component_amount_rupiah_snapshot' => 70000,
            'allocated_amount_rupiah' => 50000,
        ]);
    }
    public function test_admin_workspace_config_json_escapes_script_breaking_sequences_from_stored_fields(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-14 days'));
        $payload = '</script><script>alert(1)</script>';

        $this->seedNoteBase('note-admin-xss-1', $payload, $oldDate, 50000, 'closed');
        $this->seedWorkItemBase('wi-admin-xss-1', 'note-admin-xss-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-admin-xss-1', $payload, 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('pay-admin-xss-1', 50000, $oldDate);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-admin-xss-1',
            'customer_payment_id' => 'pay-admin-xss-1',
            'note_id' => 'note-admin-xss-1',
            'work_item_id' => 'wi-admin-xss-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-admin-xss-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 20,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.notes.workspace.edit', ['noteId' => 'note-admin-xss-1']));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertIsString($html);
        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringContainsString('\u003C/script\u003E\u003Cscript\u003Ealert(1)\u003C/script\u003E', $html);
    }

    public function test_admin_workspace_config_json_escapes_script_breaking_sequences_from_product_label(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-14 days'));
        $payload = '</script><script>alert(2)</script>';

        $this->seedNoteBase('note-admin-product-xss-1', 'Budi Product XSS', $oldDate, 150000, 'closed');
        $this->seedNotePaymentProduct('product-xss-1', 'PRD-XSS-1', $payload, 'Merek XSS', 100, 100000);
        $this->seedWorkItemBase(
            'wi-admin-product-xss-1',
            'note-admin-product-xss-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            150000
        );
        $this->seedServiceDetailBase('wi-admin-product-xss-1', 'Servis Product XSS', 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedStoreStockLineBase('ssl-admin-product-xss-1', 'wi-admin-product-xss-1', 'product-xss-1', 1, 100000);
        $this->seedCustomerPaymentBase('pay-admin-product-xss-1', 150000, $oldDate);
        $this->seedServiceWithStoreStockCurrentRevision(
            'note-admin-product-xss-1',
            'note-admin-product-xss-1-r001',
            'wi-admin-product-xss-1',
            'Budi Product XSS',
            $oldDate,
            150000,
            'Servis Product XSS',
            50000,
            'ssl-admin-product-xss-1',
            'product-xss-1',
            1,
            100000
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-admin-product-xss-1',
            'customer_payment_id' => 'pay-admin-product-xss-1',
            'note_id' => 'note-admin-product-xss-1',
            'work_item_id' => 'wi-admin-product-xss-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-admin-product-xss-1',
            'component_amount_rupiah_snapshot' => 150000,
            'allocated_amount_rupiah' => 150000,
            'allocation_priority' => 20,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.notes.workspace.edit', ['noteId' => 'note-admin-product-xss-1']));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertIsString($html);
        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringContainsString('\u003C/script\u003E\u003Cscript\u003Ealert(2)\u003C/script\u003E', $html);
    }

}
