<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class AbsurdTransactionGauntletFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_absurd_transaction_gauntlet_preserves_cross_domain_truth_end_to_end(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();
        $actorId = (string) $admin->getAuthIdentifier();

        // CHECKPOINT 1: stock must originate from the real procurement receive path.
        $this->seedMinimalProduct('gauntlet-product-a', 'G-A', 'Oli Gauntlet A', 'Federal', null, 100000);
        $this->seedMinimalProduct('gauntlet-product-b', 'G-B', 'Busi Gauntlet B', 'NGK', null, 50000);
        $this->seedMinimalProduct('gauntlet-product-c', 'G-C', 'Kampas Gauntlet C', 'Nissin', null, 80000);

        $this->actingAs($admin)
            ->post(route('admin.procurement.supplier-invoices.store'), $this->supplierInvoicePayload())
            ->assertRedirect(route('admin.procurement.supplier-invoices.index'))
            ->assertSessionHasNoErrors();

        $this->assertInventory('gauntlet-product-a', 10, 40000, 400000);
        $this->assertInventory('gauntlet-product-b', 10, 20000, 200000);
        $this->assertInventory('gauntlet-product-c', 10, 30000, 300000);
        self::assertSame(3, DB::table('inventory_movements')->where('source_type', 'supplier_receipt_line')->count());

        // CHECKPOINT 2: one note mixes package stock, standalone stock, external purchase, and service-only.
        $create = app(CreateTransactionWorkspaceHandler::class)->handle($this->createMixedTransactionPayload($actorId));
        self::assertTrue($create->isSuccess(), (string) $create->message());

        $noteId = (string) ($create->data()['note']['id'] ?? '');
        self::assertNotSame('', $noteId);

        $oldProductRowId = $this->workItemIdByType($noteId, 'store_stock_sale_only');
        self::assertNotSame('', $oldProductRowId);

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'note_state' => 'open',
            'total_rupiah' => 780000,
            'latest_revision_number' => 1,
        ]);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'total_rupiah' => 780000,
            'net_paid_rupiah' => 300000,
            'outstanding_rupiah' => 480000,
        ]);
        self::assertSame(300000, (int) DB::table('customer_payments')->where('id', '<>', '')->sum('amount_rupiah'));
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'amount_paid_rupiah' => 300000,
            'amount_received_rupiah' => 350000,
            'change_rupiah' => 50000,
        ]);
        $this->assertInventory('gauntlet-product-a', 8, 40000, 320000);
        $this->assertInventory('gauntlet-product-b', 9, 20000, 180000);
        $this->assertInventory('gauntlet-product-c', 8, 30000, 240000);

        // CHECKPOINT 3: edit the still-open note. Quantity mix and total both change.
        $revisionPayload = $this->upwardRevisionPayload();
        $this->actingAs($admin)
            ->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $revisionPayload)
            ->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))
            ->assertSessionHasNoErrors();

        // Exact retry must replay instead of producing another revision or another stock correction.
        $this->actingAs($admin)
            ->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $revisionPayload)
            ->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))
            ->assertSessionHasNoErrors();

        self::assertSame(2, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
        self::assertSame(3, DB::table('inventory_movements')->where('source_type', 'transaction_workspace_updated')->count());

        // Same key + mutated payload must fail and leave revision/stock counts unchanged.
        $mismatchedRevision = $revisionPayload;
        $mismatchedRevision['note']['customer_name'] = 'Payload Tampered With Same Key';
        $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => $noteId]))
            ->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $mismatchedRevision)
            ->assertSessionHasErrors(['revision']);

        self::assertSame(2, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
        self::assertSame(3, DB::table('inventory_movements')->where('source_type', 'transaction_workspace_updated')->count());

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'note_state' => 'open',
            'total_rupiah' => 880000,
            'latest_revision_number' => 2,
        ]);
        $this->assertDatabaseHas('note_revision_settlements', [
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 880000,
            'carry_forward_paid_rupiah' => 300000,
            'outstanding_rupiah' => 580000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);
        $this->assertInventory('gauntlet-product-a', 9, 40000, 360000);
        $this->assertInventory('gauntlet-product-b', 8, 20000, 160000);
        $this->assertInventory('gauntlet-product-c', 7, 30000, 210000);

        $packageRowId = $this->workItemIdByServiceName($noteId, 'Paket Gauntlet Revised');
        $productRowId = $this->workItemIdByType($noteId, 'store_stock_sale_only');
        $externalRowId = $this->workItemIdByServiceName($noteId, 'Servis External Revised');
        $serviceRowId = $this->workItemIdByServiceName($noteId, 'Servis Murni Revised');

        foreach ([$packageRowId, $productRowId, $externalRowId, $serviceRowId] as $rowId) {
            self::assertNotSame('', $rowId);
        }
        self::assertNotSame($oldProductRowId, $productRowId);

        // ADR-0044: an existing-note partial payment after revision credits intent, not tender.
        $partialCashPayload = [
            'selected_row_ids' => [$externalRowId.'::service_fee::'.$externalRowId],
            'payment_scope' => 'partial',
            'payment_method' => 'cash',
            'paid_at' => '2026-09-12',
            'amount_paid' => 20000,
            'amount_received' => 100000,
            'idempotency_key' => 'gauntlet-payment-partial-cash-002',
        ];
        $this->actingAs($admin)->post(route('admin.notes.payments.store', ['noteId' => $noteId]), $partialCashPayload)
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.notes.payments.store', ['noteId' => $noteId]), $partialCashPayload)
            ->assertSessionHasNoErrors();
        self::assertSame(2, DB::table('customer_payments')->count());
        self::assertSame(320000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'amount_paid_rupiah' => 20000, 'amount_received_rupiah' => 100000, 'change_rupiah' => 80000,
        ]);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId, 'net_paid_rupiah' => 320000, 'outstanding_rupiah' => 560000,
        ]);

        // CHECKPOINT 4: settle by transfer. A selected-row suggestion is deliberately misleading;
        // backend note-level settlement must remain authoritative. Duplicate submit must be harmless.
        $paymentPayload = [
            'selected_row_ids' => [$externalRowId.'::service_fee::'.$externalRowId],
            'payment_scope' => 'partial',
            'payment_method' => 'transfer',
            'paid_at' => '2026-09-12',
            'amount_paid' => 560000,
            'idempotency_key' => 'gauntlet-payment-settle-001',
        ];

        $this->actingAs($admin)
            ->post(route('admin.notes.payments.store', ['noteId' => $noteId]), $paymentPayload)
            ->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.notes.payments.store', ['noteId' => $noteId]), $paymentPayload)
            ->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        self::assertSame(3, DB::table('customer_payments')->count());
        self::assertSame(880000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(2, DB::table('customer_payments')->where('payment_method', 'cash')->count());
        self::assertSame(1, DB::table('customer_payments')->where('payment_method', 'transfer')->count());
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'closed']);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'total_rupiah' => 880000,
            'net_paid_rupiah' => 880000,
            'outstanding_rupiah' => 0,
        ]);

        $tamperedPayment = $paymentPayload;
        $tamperedPayment['amount_paid'] = 1;
        $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => $noteId]))
            ->post(route('admin.notes.payments.store', ['noteId' => $noteId]), $tamperedPayment)
            ->assertSessionHasErrors(['payment']);
        self::assertSame(3, DB::table('customer_payments')->count());

        // CHECKPOINT 5: stale pre-revision row IDs must not refund historical/shadow rows.
        $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => $noteId]))
            ->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), [
                'selected_row_ids' => [$oldProductRowId],
                'refunded_at' => '2026-09-12',
                'reason' => 'Gauntlet stale pre-revision row must fail.',
                'idempotency_key' => 'gauntlet-refund-stale-001',
            ])
            ->assertSessionHasErrors(['refund']);
        self::assertSame(0, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        self::assertSame(0, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line_reversal')->count());

        // CHECKPOINT 6: an atomic refund request mixing a valid product row with a blocked external row
        // must not partially mutate money, note rows, or inventory.
        $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => $noteId]))
            ->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), [
                'selected_row_ids' => [$productRowId, $externalRowId],
                'refunded_at' => '2026-09-12',
                'reason' => 'Gauntlet valid plus blocked external row must rollback as one plan.',
                'idempotency_key' => 'gauntlet-refund-mixed-invalid-001',
            ])
            ->assertSessionHasErrors(['refund']);
        self::assertSame(0, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        $this->assertInventory('gauntlet-product-c', 7, 30000, 210000);

        // CHECKPOINT 7: refund standalone product. Client-supplied refund amount is deliberately forged;
        // the backend must derive Rp240k from authoritative payment/component state.
        $productRefundPayload = [
            'selected_row_ids' => [$productRowId],
            'refunded_at' => '2026-09-12',
            'reason' => 'Gauntlet refund standalone product row.',
            'idempotency_key' => 'gauntlet-refund-product-001',
            'refund_amount_rupiah' => 1,
        ];

        $this->actingAs($admin)
            ->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), $productRefundPayload)
            ->assertSessionHas('success');
        $this->actingAs($admin)
            ->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), $productRefundPayload)
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        self::assertSame(240000, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        $this->assertInventory('gauntlet-product-c', 10, 30000, 300000);
        self::assertSame(1, DB::table('inventory_movements')
            ->where('product_id', 'gauntlet-product-c')
            ->where('source_type', 'work_item_store_stock_line_reversal')
            ->count());

        // CHECKPOINT 8: selecting the package refunds only its stock components (Rp200k).
        // The Rp180k service remains active; both internal products return exactly once.
        $this->actingAs($admin)
            ->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), [
                'selected_row_ids' => [$packageRowId],
                'refunded_at' => '2026-09-12',
                'reason' => 'Gauntlet refund package row with two stock components.',
                'idempotency_key' => 'gauntlet-refund-package-001',
            ])
            ->assertSessionHas('success');

        self::assertSame(440000, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        $this->assertInventory('gauntlet-product-a', 10, 40000, 400000);
        $this->assertInventory('gauntlet-product-b', 10, 20000, 200000);
        foreach (['gauntlet-product-a', 'gauntlet-product-b'] as $productId) {
            self::assertSame(1, DB::table('inventory_movements')
                ->where('product_id', $productId)
                ->where('source_type', 'work_item_store_stock_line_reversal')->count());
        }
        $this->assertDatabaseMissing('refund_component_allocations', [
            'component_type' => 'service_fee',
            'component_ref_id' => $packageRowId,
        ]);
        $this->assertDatabaseMissing('work_items', ['id' => $packageRowId, 'status' => 'canceled']);

        // CHECKPOINT 9: service-only refund is blocked without money or inventory mutation.
        $movementCountBeforeServiceRefund = DB::table('inventory_movements')->count();
        $refundAllocationCountBeforeBlockedAttempts = DB::table('refund_component_allocations')->count();
        $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => $noteId]))
            ->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), [
                'selected_row_ids' => [$serviceRowId],
                'refunded_at' => '2026-09-12',
                'reason' => 'Gauntlet service-only refund remains policy-blocked.',
                'idempotency_key' => 'gauntlet-refund-service-001',
            ])
            ->assertSessionHasErrors(['refund']);

        self::assertSame(440000, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        self::assertSame($movementCountBeforeServiceRefund, DB::table('inventory_movements')->count());
        self::assertSame($refundAllocationCountBeforeBlockedAttempts, DB::table('refund_component_allocations')->count());

        // CHECKPOINT 10: external purchase refund is explicitly blocked by current policy.
        $refundTotalBeforeExternalAttempt = (int) DB::table('customer_refunds')->sum('amount_rupiah');
        $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => $noteId]))
            ->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), [
                'selected_row_ids' => [$externalRowId],
                'refunded_at' => '2026-09-12',
                'reason' => 'Gauntlet external purchase refund remains policy-blocked.',
                'idempotency_key' => 'gauntlet-refund-external-blocked-001',
            ])
            ->assertSessionHasErrors(['refund']);
        self::assertSame($refundTotalBeforeExternalAttempt, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        self::assertSame($movementCountBeforeServiceRefund, DB::table('inventory_movements')->count());
        self::assertSame($refundAllocationCountBeforeBlockedAttempts, DB::table('refund_component_allocations')->count());

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'note_state' => 'closed',
            // Package snapshot 380k + external 170k + service-only 90k.
            // The package's 200k component refund is separate from row cancellation.
            'total_rupiah' => 640000,
        ]);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'net_paid_rupiah' => 440000,
            'outstanding_rupiah' => 0,
        ]);

        // CHECKPOINT 11: explicit admin correction replaces the active rows with external-only Rp150k.
        // It removes package service Rp180k and service-only Rp90k, and lowers external cost Rp20k.
        // Gross paid 880k - ordinary refunds 440k - revised obligation 150k = surplus 290k.
        // This revision uses separate surplus-refund tables; blocked refund policy stays unchanged.
        $finalRevisionPayload = $this->finalDownwardExternalRevisionPayload();
        $this->actingAs($admin)
            ->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $finalRevisionPayload)
            ->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)
            ->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $finalRevisionPayload)
            ->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))
            ->assertSessionHasNoErrors();

        self::assertSame(3, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 150000,
            'latest_revision_number' => 3,
        ]);
        $this->assertDatabaseHas('note_revision_settlements', [
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 150000,
            'carry_forward_paid_rupiah' => 880000,
            'carry_forward_refunded_rupiah' => 440000,
            'net_paid_rupiah' => 440000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 290000,
            'settlement_status' => 'overpaid_pending',
        ]);
        self::assertSame(440000, (int) DB::table('customer_refunds')->where('note_id', $noteId)->sum('amount_rupiah'));
        self::assertSame(290000, (int) DB::table('note_revision_surplus_dispositions')->where('note_root_id', $noteId)->sum('amount_rupiah'));
        self::assertSame(290000, (int) DB::table('note_revision_surplus_refund_payments')->where('note_root_id', $noteId)->sum('amount_rupiah'));

        // CHECKPOINT 12: final DB truth. Every physical item has returned to the supplier-received stock.
        $this->assertInventory('gauntlet-product-a', 10, 40000, 400000);
        $this->assertInventory('gauntlet-product-b', 10, 20000, 200000);
        $this->assertInventory('gauntlet-product-c', 10, 30000, 300000);
        self::assertSame(3, DB::table('inventory_movements')->where('source_type', 'supplier_receipt_line')->count());
        self::assertSame(6, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line')->count());
        self::assertSame(3, DB::table('inventory_movements')->where('source_type', 'transaction_workspace_updated')->count());
        self::assertSame(3, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line_reversal')->count());

        self::assertSame(880000, (int) DB::table('customer_payments')->where('id', '<>', '')->sum('amount_rupiah'));
        self::assertSame(440000, (int) DB::table('customer_refunds')->where('note_id', $noteId)->sum('amount_rupiah'));
        self::assertSame(290000, (int) DB::table('note_revision_surplus_refund_payments')->where('note_root_id', $noteId)->sum('amount_rupiah'));
        self::assertSame(
            150000,
            (int) DB::table('customer_payments')->sum('amount_rupiah')
                - (int) DB::table('customer_refunds')->where('note_id', $noteId)->sum('amount_rupiah')
                - (int) DB::table('note_revision_surplus_refund_payments')->where('note_root_id', $noteId)->sum('amount_rupiah'),
            'Gross customer cash-in minus ordinary refunds minus revision-surplus refunds must equal the final active note total.',
        );

        self::assertSame(2, DB::table('audit_logs')->where('event', 'selected_rows_refund_plan_recorded')->count());
        self::assertSame(1, DB::table('note_mutation_events')
            ->where('note_id', $noteId)
            ->where('mutation_type', 'note_rows_canceled_via_refund')
            ->count());
        $this->assertDatabaseHas('idempotency_records', [
            'actor_id' => $actorId,
            'operation' => 'create_note_revision',
            'idempotency_key' => 'gauntlet-revision-upward-001',
            'status' => 'succeeded',
            'result_note_id' => $noteId,
        ]);
        $this->assertDatabaseHas('idempotency_records', [
            'actor_id' => $actorId,
            'operation' => 'record_note_payment',
            'idempotency_key' => 'gauntlet-payment-settle-001',
            'status' => 'succeeded',
            'result_note_id' => $noteId,
        ]);
        $this->assertDatabaseHas('idempotency_records', [
            'actor_id' => $actorId,
            'operation' => 'record_selected_rows_refund',
            'idempotency_key' => 'gauntlet-refund-product-001',
            'status' => 'succeeded',
            'result_note_id' => $noteId,
        ]);
        $this->assertDatabaseHas('idempotency_records', [
            'actor_id' => $actorId,
            'operation' => 'create_note_revision',
            'idempotency_key' => 'gauntlet-revision-final-001',
            'status' => 'succeeded',
            'result_note_id' => $noteId,
        ]);

        // CHECKPOINT 13: reporting must tell the same story as DB truth. These assertions are intentionally
        // business-level, not implementation-level. If they go RED, the gauntlet has found a cross-domain miss.
        $timeline = app(NoteDetailPageDataBuilder::class)->build($noteId)['note']['payment_timeline'];
        self::assertCount(3, $timeline, 'Replacement/refund must preserve every historical credited payment.');
        self::assertSame(880000, array_sum(array_column($timeline, 'payment_amount_rupiah')));
        $cashTimeline = array_values(array_filter($timeline, static fn (array $event): bool => $event['payment_method'] === 'cash'));
        self::assertCount(2, $cashTimeline);
        self::assertSame(450000, array_sum(array_column($cashTimeline, 'amount_received_rupiah')));
        self::assertSame(130000, array_sum(array_column($cashTimeline, 'change_rupiah')));
        $ledger = app(TransactionCashLedgerReportingQuery::class)->reconciliation('2026-09-01', '2026-09-30');
        self::assertSame(880000, $ledger['total_in_rupiah'], 'Cash ledger must preserve gross historical customer money-in across revisions/refunds.');
        self::assertSame(320000, $ledger['cash_in_rupiah']);
        self::assertSame(560000, $ledger['transfer_in_rupiah']);
        self::assertSame(730000, $ledger['total_out_rupiah']);
        self::assertSame(150000, $ledger['total_in_rupiah'] - $ledger['total_out_rupiah']);

        $transaction = app(GetTransactionReportDatasetHandler::class)->handle('2026-09-01', '2026-09-30');
        self::assertTrue($transaction->isSuccess());
        $summary = $transaction->data()['summary'];
        self::assertSame(1, $summary['total_rows']);
        self::assertSame(150000, $summary['gross_transaction_rupiah']);
        self::assertSame(0, $summary['outstanding_rupiah']);
        self::assertSame(150000, $summary['net_cash_collected_rupiah']);

        $profit = app(GetOperationalProfitSummaryHandler::class)->handle('2026-09-01', '2026-09-30');
        self::assertTrue($profit->isSuccess());
        $profitRow = $profit->data()['row'];
        self::assertSame(880000, $profitRow['cash_in_rupiah']);
        self::assertSame(730000, $profitRow['refunded_rupiah']);
        self::assertSame(0, $profitRow['store_stock_cogs_rupiah']);
        self::assertSame(70000, $profitRow['external_purchase_cost_rupiah']);
        self::assertSame(80000, $profitRow['cash_operational_profit_rupiah']);
    }

    /** @return array<string, mixed> */
    private function supplierInvoicePayload(): array
    {
        return [
            'nomor_faktur' => 'GAUNTLET-SUP-001',
            'nama_pt_pengirim' => 'PT Supplier Gauntlet',
            'tanggal_pengiriman' => '2026-09-10',
            'auto_receive' => true,
            'lines' => [
                ['line_no' => 1, 'product_id' => 'gauntlet-product-a', 'qty_pcs' => 10, 'line_total_rupiah' => 400000],
                ['line_no' => 2, 'product_id' => 'gauntlet-product-b', 'qty_pcs' => 10, 'line_total_rupiah' => 200000],
                ['line_no' => 3, 'product_id' => 'gauntlet-product-c', 'qty_pcs' => 10, 'line_total_rupiah' => 300000],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function createMixedTransactionPayload(string $actorId): array
    {
        return [
            'idempotency_key' => 'gauntlet-create-001',
            '_actor_id' => $actorId,
            'note' => [
                'customer_name' => 'Budi Absurd Gauntlet',
                'customer_phone' => '081234567890',
                'transaction_date' => '2026-09-11',
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'store_stock',
                    'pricing_mode' => 'package_auto_split',
                    'package_total_rupiah' => 400000,
                    'service' => ['name' => 'Paket Gauntlet Original', 'price_rupiah' => 0, 'notes' => null],
                    'product_lines' => [
                        ['product_id' => 'gauntlet-product-a', 'qty' => 2, 'unit_price_rupiah' => 100000, 'price_basis' => 'current_catalog'],
                        ['product_id' => 'gauntlet-product-b', 'qty' => 1, 'unit_price_rupiah' => 50000, 'price_basis' => 'current_catalog'],
                    ],
                    'external_purchase_lines' => [],
                ],
                [
                    'entry_mode' => 'product',
                    'description' => 'Standalone stock row',
                    'part_source' => 'none',
                    'product_lines' => [
                        ['product_id' => 'gauntlet-product-c', 'qty' => 2, 'unit_price_rupiah' => 80000, 'price_basis' => 'current_catalog'],
                    ],
                    'external_purchase_lines' => [],
                ],
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'external_purchase',
                    'service' => ['name' => 'Servis External Original', 'price_rupiah' => 90000, 'notes' => null],
                    'product_lines' => [],
                    'external_purchase_lines' => [
                        ['label' => 'Bearing Luar Original', 'qty' => 1, 'unit_cost_rupiah' => 60000],
                    ],
                ],
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => ['name' => 'Servis Murni Original', 'price_rupiah' => 70000, 'notes' => null],
                    'product_lines' => [],
                    'external_purchase_lines' => [],
                ],
            ],
            'inline_payment' => [
                'decision' => 'pay_partial',
                'payment_method' => 'cash',
                'paid_at' => '2026-09-11',
                'amount_paid_rupiah' => 300000,
                'amount_received_rupiah' => 350000,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function upwardRevisionPayload(): array
    {
        return [
            'idempotency_key' => 'gauntlet-revision-upward-001',
            'reason' => 'Gauntlet open-note revision changes package mix, standalone qty, external cost, and service fee.',
            'note' => [
                'customer_name' => 'Budi Absurd Gauntlet Revised',
                'customer_phone' => '081234567890',
                'transaction_date' => '2026-09-12',
            ],
            'items' => [
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'store_stock',
                    'pricing_mode' => 'package_auto_split',
                    'package_total_rupiah' => 380000,
                    'service' => ['name' => 'Paket Gauntlet Revised', 'price_rupiah' => 0, 'notes' => null],
                    'product_lines' => [
                        ['product_id' => 'gauntlet-product-a', 'qty' => 1, 'unit_price_rupiah' => 100000, 'price_basis' => 'revision_snapshot'],
                        ['product_id' => 'gauntlet-product-b', 'qty' => 2, 'unit_price_rupiah' => 50000, 'price_basis' => 'revision_snapshot'],
                    ],
                    'external_purchase_lines' => [],
                ],
                [
                    'entry_mode' => 'product',
                    'description' => 'Standalone stock row revised',
                    'part_source' => 'none',
                    'product_lines' => [
                        ['product_id' => 'gauntlet-product-c', 'qty' => 3, 'unit_price_rupiah' => 80000, 'price_basis' => 'revision_snapshot'],
                    ],
                    'external_purchase_lines' => [],
                ],
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'external_purchase',
                    'service' => ['name' => 'Servis External Revised', 'price_rupiah' => 80000, 'notes' => null],
                    'product_lines' => [],
                    'external_purchase_lines' => [
                        ['label' => 'Bearing Luar Revised', 'total_rupiah' => 90000],
                    ],
                ],
                [
                    'entry_mode' => 'service',
                    'description' => null,
                    'part_source' => 'none',
                    'service' => ['name' => 'Servis Murni Revised', 'price_rupiah' => 90000, 'notes' => null],
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
        ];
    }

    /** @return array<string, mixed> */
    private function finalDownwardExternalRevisionPayload(): array
    {
        return [
            'idempotency_key' => 'gauntlet-revision-final-001',
            'reason' => 'Gauntlet admin correction removes package/service-only services and lowers external cost; Rp290k revision surplus.',
            'note' => [
                'customer_name' => 'Budi Absurd Gauntlet Final',
                'customer_phone' => '081234567890',
                'transaction_date' => '2026-09-13',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'external_purchase',
                'service' => ['name' => 'Servis External Final', 'price_rupiah' => 80000, 'notes' => null],
                'product_lines' => [],
                'external_purchase_lines' => [
                    ['label' => 'Bearing Luar Final', 'total_rupiah' => 70000],
                ],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => null,
                'amount_paid_rupiah' => null,
                'amount_received_rupiah' => null,
            ],
        ];
    }

    private function workItemIdByType(string $noteId, string $type): string
    {
        return (string) DB::table('work_items')
            ->where('note_id', $noteId)
            ->where('transaction_type', $type)
            ->value('id');
    }

    private function workItemIdByServiceName(string $noteId, string $serviceName): string
    {
        return (string) DB::table('work_items')
            ->join('work_item_service_details', 'work_item_service_details.work_item_id', '=', 'work_items.id')
            ->where('work_items.note_id', $noteId)
            ->where('work_item_service_details.service_name', $serviceName)
            ->value('work_items.id');
    }

    private function assertInventory(string $productId, int $qty, int $avgCost, int $value): void
    {
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => $productId,
            'qty_on_hand' => $qty,
        ]);
        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => $productId,
            'avg_cost_rupiah' => $avgCost,
            'inventory_value_rupiah' => $value,
        ]);
    }
}
