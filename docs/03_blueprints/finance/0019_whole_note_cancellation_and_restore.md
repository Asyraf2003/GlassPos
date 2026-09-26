# Blueprint0019: Whole-Note Cancellation And Restore

Status: active implementation blueprint; C0 is merged in PR #2, C1 unpaid cancellation is merged in PR #4, and C2 restore implementation/proof is complete locally with PR review/merge pending. Later slices remain open.

Traceability: [C0 Issue #1](https://github.com/Asyraf2003/GlassPos/issues/1), [C1 Issue #3](https://github.com/Asyraf2003/GlassPos/issues/3) / [PR #4](https://github.com/Asyraf2003/GlassPos/pull/4), [C2 Issue #5](https://github.com/Asyraf2003/GlassPos/issues/5).

## Target and boundaries

Implement Batalkan Transaksi as an auditable root lifecycle with zero active customer effect, source inventory compensation, truthful current reporting and restore through a new accepted revision.

Contract: [ADR-0046](../../02_architecture/adr/0046_whole_note_cancellation_and_revision_restore.md). Raw owner evidence: [handoff0029](../../04_lifecycle/handoff/0029_whole_note_cancellation_owner_decision_handoff.md). Baseline: [Blueprint0018](0018_primitive_lifecycle_contract_map_and_adversarial_torture_tests.md), [ordered execution0027](../../04_lifecycle/handoff/0027_primitive_lifecycle_ordered_execution_handoff.md), [closeout0028](../../04_lifecycle/handoff/0028_blueprint0018_closeout.md). The historical baseline is not a fresh test result.

SCOPE-IN: cancellation eligibility/current effects, compensation, restore, required current read models, Detail integration, audit/idempotency/concurrency and bounded refund dependencies.

SCOPE-OUT: Auto presets, new finance/inventory engines, destructive deletion, global access redesign, unrelated bugs, historical data repair, procurement lifecycle invention and reopening closed Blueprint0018 slices without contradictory evidence.

## FACT: source effect graph

Audit source baseline: main 54b651826cca3655968c0112d1f9a4d810879a3c. The following map was inspected before production work. Proposed effects are requirements, not claims of implementation.


Paths in this table are repository-relative. C = current operational truth, H = immutable/historical facts, P = derived projection. Compensation appends effects; it never edits the original event.

| Effect | Classification / authority | Existing seam and cancellation implication |
|---|---|---|
| Root identity/state | C, notes | app/Core/Note/Note/Note.php accepts open/closed/refunded only. Add cancelled through explicit lifecycle contract, not delete. Existing string note_state schema does not itself need an enum migration. |
| Root serialization | Transaction boundary | app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php::getByIdForUpdate locks notes before loading graph. Cancellation and competitors must validate under this same root lock. |
| Current revision pointer | C, notes.current_revision_id | app/Application/Note/Services/NoteCurrentRevisionResolver.php and app/Application/Note/UseCases/CreateNoteRevisionCommitter.php. Preserve R1 on cancellation; proposed cancellation event overlays current effect. Restore must commit R2 with parent R1 through existing writer. |
| Revision snapshots | H, note_revisions/note_revision_lines | app/Application/Note/Services/NoteRevisionBootstrapFactory.php and ADR-0045. Historical shape, labels, amounts and package decomposition remain. Never rewrite R1 to zero. |
| Work items | C operational rows plus retained history anchors | app/Adapters/Out/Note/Mappers/NoteMapper.php excludes canceled work items when reconstructing total. Row neutralization must be coherent with root total and current settlement, not a root flag alone. |
| Product/store stock | C shape plus H issue/cost | app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php issues stock at persistence, including unpaid transactions. Cancellation compensates the actual committed issue by source identity. |
| Packages/components | C decomposition plus H snapshots/allocations | Same work-item persister, revision payload and allocation machinery. Unpaid package cancellation must neutralize service and stock effects together; current refund only supports its stock component. |
| Service | C obligation, H snapshot; no inventory | Existing revision/work-item services. Unpaid service cancellation creates no stock or money event. Paid service refund is currently blocked by the default refund policy. |
| External purchase | C transaction cost/pass-through plus H snapshot | work_item_external_purchase_lines; app/Application/Payment/Services/RefundComponentTypePolicy.php currently excludes external components. No store-stock issue exists to reverse. Do not cancel this graph as scratch. |
| Inventory movements | H ledger, compensatable | app/Application/Inventory/Services/ReverseIssuedInventoryOperation.php returns original quantity and unit cost; duplicate reverse-source positive movement becomes no-op. Existing work_item_store_stock_line_reversal has generated unique source constraint. |
| Inventory quantity/costing | C projection over official movements | Same reverse operation updates product_inventory/product_inventory_costing with existing costing rules. No parallel cost calculator. Missing inventory/costing currently causes continue: cancellation must prove complete compensation or fail atomically. |
| Independent loss/material usage | Separate business event | app/Application/Inventory/UseCases/RecordStockAdjustmentHandler.php uses IssueInventoryOperation, stock_adjustment source, actor/reason and audit. Reference the cancelled note in the existing reason/context; do not keep cancelled sale COGS. |
| Customer payments/cash details | H financial facts | app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php creates credited payment and cash detail under root lock. Cancellation eligibility must reject recorded-payment history before changing effects. |
| Payment allocations | C distribution; H event anchors retained | app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php captures/rebuilds allocations through NoteReplacementPaymentAllocationReconciler. Never clone payment on restore. Pure cancellation must not erase payment links. |
| Recorded-payment discovery | Derived lookup of H links | app/Adapters/Out/Payment/Queries/DatabaseNotePaymentAmountByNoteIdQuery.php unions legacy/component/refund payment IDs. Fully clipped historical links are a documented pre-existing gap: zero current allocation alone cannot establish pure-cancel eligibility. |
| Ordinary refunds/allocations | H money-out/component facts | app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php groups source buckets, row neutralization, projection, audit and idempotency. RecordCustomerRefundOperation locks root. Reuse; do not fabricate zero-value customer money refunds. |
| Refund stock behavior | Compensatable inventory effect | app/Application/Payment/Services/RecordSelectedRowsRefundPlanBucketProcessor.php unconditionally calls executeFullRowReversal. This is NOT existing explicit return/no-return Detail support. |
| Receivable/outstanding | Derived current obligation less accountable settlement | app/Application/Note/Services/NoteOutstandingPaymentAmountResolver.php and Concerns/ResolvesNoteOperationalCurrentRevisionSettlement.php use current revision snapshots. Zero root total alone does not neutralize this reader. |
| Revision settlement/due/paid | H commit-time settlement and financial events | app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php + CreateNoteRevisionSettlementCommitter. Do not rewrite settlement snapshots or invoke automatic surplus merely to cosmetically cancel a paid note. |
| History projection | P | app/Application/Note/Services/NoteHistoryProjectionService.php already treats active root total zero specially; other current readers do not all do so. Sync atomically through existing writer. |
| Current transaction reports | Read-only derived values | app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php reads notes.total_rupiah and allocation/projection data. Root cancellation must not inflate active totals or active transaction counts; historical cancelled rows need explicit labeling/filter semantics. |
| Package report | Read-only current component view | app/Adapters/Out/Reporting/Queries/ServicePackageProfitBreakdown/BreakdownSourceRowsQuery.php filters work_items.status != canceled. A root-only state change leaves package values active. |
| Operational COGS/profit | Derived immutable movements with period basis | app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php recognizes issue, stock-line reversal, and transaction_workspace_updated. Reuse recognized compensation or explicitly update source membership with tests; never hide ledger history. |
| Detail/timeline | Read-only current + H narrative | app/Application/Note/Services/NoteDetailPageDataBuilder.php, NoteRevisionTimelineSummaryBuilder and PersistNoteMutationTimeline. Display original revision separately from zero active charge; add cancellation reason/actor/time using existing history surface. |
| Audit/outbox | H durable evidence, atomic | app/Application/Note/UseCases/CreateNoteRevisionDurableAudit.php demonstrates AuditEventWriterPort. app/Providers/InfrastructureServiceProvider.php globally binds outbox, with known synchronous surplus FK exceptions. Cancellation needs canonical capture AND existing note history, in its business transaction. |
| Idempotency | Durable command receipt | app/Application/Note/Services/CreateNoteRevisionIdempotencyService.php and CreateNoteRevisionHandler demonstrate actor/operation/key + semantic hash, root inclusion, replay before stale check, collision rollback then winner replay. Reuse protocol for new operation; do not reuse revision operation name. |
| Access/date | Existing role/capability and domain gates | app/Application/Note/Policies/CashierNoteAccessGuard.php; app/Application/IdentityAccess/Policies/TransactionEntryPolicy.php; app/Adapters/In/Http/Middleware/Note/EnsureCashierNoteAccess.php. Dedicated official cancel/restore flow uses today/yesterday and admin capability; no new role family. |
| FK/history anchors | H links and current replacement boundary | app/Adapters/Out/Note/WorkItemDeletesTrait.php protects refund-referenced rows but deletes other replacement rows. Cancellation itself must not call destructive replacement. Restore can use official replacement only after preserving reconstructable cancellation/version evidence. |

## GAP / conflict classification

- Root cancellation and restore are missing. ADR-0046 now defines their contract; implementation/proof remain open.
- Current root totals, revision settlement and package projections have different consumers. Zeroing the root alone would leave actionable historical obligation. Shared current eligibility must be applied consistently; reports do not repair state.
- RefundComponentTypePolicy supports stock types only; SelectedNoteRowsRefundEligibilityGuard rejects open/unpaid rows; bucket processing always returns stock. These are existing limitations versus ADR-0042, not reasons to rebuild refund wholesale. Preserve existing defaults until a narrowly scoped dependency proves why extension is necessary.
- Recorded-payment discovery uses legacy/component/refund links. Zero allocation/net money is not never-paid proof. Characterize legacy/history ambiguity and reject safely; do not silently introduce backfills.
- ReverseIssuedInventoryOperation skips absent inventory/costing. Cancellation requires complete compensation under its transaction, not a best-effort successful marker.
- Restore must skip already compensated issues, issue fresh identities only, preserve cancellation identity and reject stale lifecycle cycles.
- ADR-0019 remains draft. Existing cashier generic workspace denial differs from official lifecycle flexibility. New cancel/restore uses accepted owner/capability/date contract without globally changing older routes.
- Current zero business effect and period-based immutable stock/cash reporting are distinct. Test action-date compensation across dates; do not force historical periods to zero.

## DECISION: bounded vertical GitHub slices

Each slice has an Issue before implementation, its own branch from accepted main, meaningful commits, focused proof, PR linked to the Issue, real diff review and a merge only when coherent/green. Prefer merge commits to preserve useful history where repository policy permits. No direct-main candidate publication. Do not create empty future Issues merely for statistics.

| Slice | Scope / acceptance | Dependencies |
|---|---|---|
| C0 | Promote owner policy to ADR-0046; source graph, conflicts, dependency map, proof gates and raw preservation. Docs-only independently valid merge. | Issue #1 |
| C1 | Atomic unpaid product/service/package root cancellation: current rights/total, inventory compensation, current projection/report correctness, mutation guards, audit, HTTP authorization, stale/key behavior. Required runtime overlap/rollback proofs ship together. | C0; merged as PR #4 |
| C2 | Restore cancelled root through new accepted revision; cancellation identity/base validation, source compensation skip, fresh stock issues, immutable history, rollback and replay. | C1; implementation/proof complete locally, see handoff0031 |
| C3 | Only refund Detail capabilities required by actual cancellation routing. Independently scoped service/partial/return-choice gaps may have separate flows when independently valid. No broad engine rewrite. | C0 plus demonstrated dependency |
| C4 | Committed external-purchase boundary: paid/unpaid/mixed classification, no store-stock conversion, truthful refund eligibility. Preserve restrictions where accepted source contract is insufficient; request only genuinely new business decisions. | C0 and relevant C3 |
| C5 | Existing Detail UI exposes cancel/restore, reason, server preview and refund routing; same domain engine, stale/retry/refresh history, no Auto. Unsupported refund cases are clearly blocked, not presented as completed. | C1/C2 and applicable C3/C4 |
| C6 | Integrated browser and adversarial acceptance, remaining race boundaries, report/export parity and broad verify. Physical device/cashier acceptance reported separately. | Prior applicable slices |

Order: C0 -> C1 -> C2; bounded C3/C4 only when required; then C5 -> C6. Execute serially across verified checkpoints. C1 cannot merge a state-only implementation leaving stock/report/payment rights active. C1 need not expose restore UI before C2, but must reject implicit reactivation.

## ACTIVE STEP

C2 implementation on branch `feat/5-restore-cancelled-note`, linked to Issue #5. Restore validates the cancellation identity and current base under the root lock, then creates an accepted child revision through the existing revision workflow. The old cancellation remains historical, and unsupported historical external-purchase snapshots are rejected. Focused, adjacent, rollback, and runtime overlap proof is recorded in [handoff0031](../../04_lifecycle/handoff/0031_restore_cancelled_note_c2_handoff.md). Awaiting PR preparation/review and merge.

## First RED and focused proof for C1


- Reuse tests/Support/BuildsPrimitiveLifecycleFixture.php and existing real create path; remove external component for pure-cancel fixtures.
- Unpaid P quantity2 + service: capture R1 and original issue, cancel against R1, assert zero active sale/receivable/profit, one +2 compensation at original cost, original issue/R1 unchanged, no payments/refunds, visible reason/actor/time.
- One service-only and one package representative establish no-stock versus decomposed-stock behavior. Do not construct a Cartesian test matrix.
- Exact replay leaves entire effect graph unchanged; changed reason/base/root under same key conflicts; new stale command has no business effects.
- Payment-present and committed-external attempts cannot perform pure cancellation. Verify refund routing/eligibility truthfully, not success via a dead-end modal.
- Use tests/Feature/Note/PrimitiveRevisionConcurrencyFeatureTest.php and PrimitiveFinancialRevisionConcurrencyFeatureTest.php independent forked connections, held root/claim barrier and third observer. Record winner/loser outcomes and source cardinality. Sequential tests are not concurrency proof.
- Inject failure after actual bound canonical audit writer insertion, assert transaction-wide rollback and same-key retry. Preserve separate pre-transaction capability access audit.
- Run nearest stock/revision/refund/projection/report/access tests, then broader verify once the coherent slice is green. Historical 1748-test receipt is not this slice's proof.

## Additional completion gates

- Restore: R1 -> cancel -> R2, new current identities and correct stock, retained R1/cancellation; insufficient-stock and audit failure rollback; stale cancellation/base and exact replay versus changed payload.
- Reporting: current note, debt queue, package breakdown and exports agree; original revision/history still readable; event report preserves cross-date issue/compensation. Reads leave source graph unchanged.
- Access: cashier today/yesterday allowed by official lifecycle, older cancellation denied, admin with/without capability; no paid/closed/refunded blanket denial replacing domain eligibility.
- Concurrency: independent connections, held transaction barrier and observed overlap; cancel/cancel, payment/cancel, revision/cancel and relevant refund/cancel outcomes cannot duplicate money/stock/version/report effects. Unpaid refund rejection alone is not proof of a paid-path race.
- Detail: real rendered controls and request payloads carry observed base/key; cancellation reason/history survives reload; recorded-payment cases keep Refund distinct. Never label a blocked unsupported refund successful.
- Broad gate: repository make verify after a coherent implementation slice; run adjacent Blueprint0018 suites appropriate to changed seams. No Cartesian explosion or redundant arithmetic mirrors.
- Review: correctness, contract compliance, blast radius, test quality, maintainability. Record real findings/fixes; no fake self-approval.

## PROOF / PROGRESS / NEXT

C0 merged as PR #2; C1 merged as PR #4; C2 local implementation/proof is recorded in handoff0031. Do not mark campaign complete until C2 merge, applicable bounded C3/C4 dependencies, C5 UI, and C6 integrated acceptance are complete.

NEXT after C2 review/merge: sync latest main and evaluate bounded refund/external dependencies against the existing product paths. Continue to C5 only when those dependencies have an accepted safe boundary; then complete C6 integrated/browser and final broad verification. Owner decisions already recorded in0029 are not asked again.
