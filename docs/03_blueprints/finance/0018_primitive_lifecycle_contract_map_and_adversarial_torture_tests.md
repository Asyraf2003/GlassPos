# Primitive Lifecycle Contract Map + Adversarial Torture Test Blueprint

## 0. Status, scope, and evidence boundary

Audit window: 2026-09-15–2026-09-16, Asia/Makassar. Status: **BLUEPRINT READY FOR REVIEW; CONTRACT GAPS REMAIN**. Example fixture business date remains 2026-09-15.

ACTIVE STEP: map primitive contracts and design five composition chains. This document does not authorize production implementation, migrations, data repair, or changes to ADR/AI_RULES. Execute one later slice at a time, with proof and owner feedback.

FACT: current PHP, JavaScript, routes, migrations, ports, and test assertions were inspected locally. No application tests, browser session, or live database mutation were executed for this blueprint. Existing test coverage below means inspected assertions or named adjacent tests, not a fresh passing result. Historical GREEN is explicitly attributed to the handoff/error log containing it.

PROOF: source anchors in section 1, contract comparisons in sections 2–5, and independently calculated integer checkpoints in sections 6–9. Arithmetic validation of fixtures is not application correctness proof.

DECISION: use a small primitive vocabulary and five chains. Reuse existing focused regressions. Do not extend the existing gauntlet into hundreds of cases. A conflict checkpoint is an investigation target, not an instruction to bless current behavior.

SCOPE-IN: cashier/admin customer-note lifecycle, accepted payment, settlement, ordinary refund, revision surplus, current versus historical identity, inventory effects, read models, and their audit evidence.

SCOPE-OUT: residual UI fixes, unrelated supplier/accounting features, production backfill, ADR rewrites, customer credit, global standards changes, exhaustive combinations. Inventory receipts are fixture prerequisites only; supplier finance is not another chain.

### Contract precedence and stale narrative

- AI_RULES mandatory package was read from `docs/01_standards/0001_index.md`, including architecture/public contracts, audit, domain, workflow, output, and stack constraints. `AGENTS.md`, `docs/0001_docs_help.md`, and documentation placement guidance constrain this artifact.
- ADR-0044 owns settlement intent versus tender and explicitly allows repeated DP as ordinary payments. ADR-0021's older “DP then final” UX narrative is not a two-payment limit.
- ADR-0045 refines revision history: active allocation may be rebuilt; original payments, refunds, inventory events, and old revision snapshots cannot be rewritten. Older allocation-immutability wording must not be used to forbid its explicit active-replacement allowance.
- ADR-0042 refines ADR-0041: a fully refunded note may accept new current lines while all old lines remain shadow. It does not authorize silently reviving an old component.
- ADR-0025's 2026-09-05 owner update selects automatic surplus due + paid. Do not revive ADR-0026's earlier customer-credit options as current behavior. ADR-0027/0029 still define distinct due and cash-out records.
- ADR-0030 is marked **Draft for owner review**. Its preview direction is useful, but this blueprint grounds binding payment assertions in accepted ADR-0042/0044/0045, not its stale “inline payment forced skip” statement.
- [0064 forensic snapshot](../../04_lifecycle/error_log/0064_manual_qa_revision_payment_surplus_forensic_snapshot.md) is historical evidence, with incomplete payment joins and no complete movement ledger. It cannot prove movement cardinality.
- [0065 forensic chain](../../04_lifecycle/error_log/0065_manual_qa_refund_revision_new_receivable_chain.md), section 20, closes the partial-refund → authorized revision → reopen → second-close fix. Its focused proof is 1 test/26 assertions; adjacent proof 21/159; recorded final verify 1707/11520. These are historical results, not reruns here.
- Current `StoreTransactionWorkspacePaymentValidator` no longer checks full-cash tender against payload grand total. Current workflow invokes `ReopenNoteForRevisionOutstanding`. Do not reopen these closed fixes from earlier paragraphs of 0065.
- Latest relevant reference: [handoff 0025](../../04_lifecycle/handoff/0025_cashier_ui_lifecycle_adversarial_hardening_handoff.md) closes the automated detail/refund-history target; [handoff 0024](../../04_lifecycle/handoff/0024_first_principles_finance_engine_continuation_handoff.md) retains stale-editor/versioning gaps. Their old proposed next steps do not override this session's blueprint-only scope.

### Status legend

The matrix uses exactly the six requested statuses. `LOCKED` is narrowly scoped: explicit contract, current matching implementation, and relevant assertions with recorded prior proof. It never means the entire family is proven for every composition. All chains below remain unexecuted.

- LOCKED: narrow primitive with the evidence above.
- IMPLEMENTED BUT WEAKLY TESTED: behavior/assertions exist, but the critical composition or runtime proof is insufficient.
- DUPLICATED TRUTH: competing or independently maintained definitions need equivalence proof.
- CONTRACT GAP: required meaning/authority is insufficiently specified.
- SOURCE CONFLICT: inspected source contradicts a stated contract or another source; runtime blast radius may still need characterization.
- MISSING PRIMITIVE: no supported primitive found in the inspected cashier domain. This may be intentionally unsupported, not a request to build it.

## 1. Source-of-truth registry

Paths below are relative to repository root. Matrix aliases name precise owners, not hypothetical services.

| ID | Current source / responsibility |
|---|---|
| S01 | `app/Core/Shared/ValueObjects/Money.php`; integer rupiah. `app/Core/Note/Note/Note.php`, `app/Core/Note/WorkItem/WorkItem.php`; aggregate amounts and raw work state. |
| S02 | `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`; locks root, validates allocation, writes payment + allocations, invokes auto-close. `app/Application/Payment/Services/BuildCustomerPaymentCashDetail.php`; tender/change. |
| S03 | `app/Application/Payment/Services/ResolveNotePayableComponents.php`, `PayableComponentsFromWorkItem.php`, `AllocatePaymentAcrossComponents.php`, `PaymentComponentTypePriority.php`, `SortPayableNoteComponents.php`, `ExistingPaymentComponentTotals.php`; component identity/capacity/priority. |
| S04 | `app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php`, `Queries/DatabaseNotePaymentAmountByNoteIdQuery.php`; allocation totals and unique payment-note links. `DatabaseCustomerRefundReaderAdapter.php`; ordinary current/all refund bases. |
| S05 | `app/Application/Note/Services/BuildCreateNoteRevisionSettlement.php`, `BuildNoteRevisionSettlement.php`; revision settlement snapshot builder. |
| S06 | `app/Application/Note/Services/NoteOutstandingPaymentAmountResolver.php`, `Concerns/ResolvesNoteOperationalCurrentRevisionSettlement.php`, `CurrentRevision/CurrentRevisionRowSettlementProjector.php`, `CurrentRevision/CurrentRevisionComponentSettlementSummaryBuilder.php`, `CurrentRevision/CurrentRevisionComponentCollectibleSettlementBuilder.php`; current payment preflight. `app/Providers/NoteApplicationServiceProvider.php` explicitly wires revision dependencies. |
| S07 | `app/Application/Note/Services/NotePaymentSettlementPreviewResolver.php`, `CreateTransactionWorkspaceInlinePaymentAmountResolver.php`, `AutoCloseNoteWhenFullyPaid.php`; other note-level settlement consumers. `app/Application/Note/Policies/NotePaidStatusPolicy.php`; paid mutation eligibility. |
| S08 | `app/Core/Note/Note/NoteOperationalStateMutations.php`, `NoteOperationalStatusPolicy.php`; stored transitions versus derived financial status. `app/Application/Note/Services/ReopenNoteForRevisionOutstanding.php`, `FinalizeRefundedNoteFromActiveRows.php`; orchestration. |
| S09 | `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`, `CreateNoteRevisionWorkflow.php`, `CreateNoteRevisionCommitter.php`, `CreateNoteRevisionSettlementCommitter.php`; transaction, replacement, pointer, snapshot, surplus sequence. |
| S10 | `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php`, `NoteReplacementPaymentAllocationReconciler.php`, `UpdateTransactionWorkspaceWorkItemPersister.php`; capture/delete/replay. `app/Adapters/Out/Note/WorkItemDeletesTrait.php`; refund FK anchors retained, other operational rows deleted. |
| S11 | `app/Application/Note/Services/NoteRevisionBootstrapFactory.php`, `NoteRevisionLinePayloadMapper.php`, `Concerns/BuildsNoteRevisionLines.php`; immutable snapshot construction. `app/Adapters/Out/Note/Concerns/QueriesNoteRevisionRecords.php`; pointer lookup and next number. |
| S12 | `app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php`, `SelectedNoteRowsRefundEligibilityGuard.php`, `SelectedNoteRowsRefundableComponentGuard.php`; selected-row eligibility. `app/Application/Payment/Services/RefundComponentTypePolicy.php`; currently only store product types refundable. |
| S13 | `app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php`, `RecordSelectedRowsRefundPlanBucketProcessor.php`, `RecordCustomerRefundOperation.php`, `RefundPairLimitGuard.php`, `AllocateRefundAcrossComponents.php`; source-payment buckets and component refund limits. |
| S14 | `app/Application/Note/Services/CancelSelectedRowsAndSyncActiveNoteTotal.php`, `WorkItemStatusTransitionService.php`; selected refund cancellation versus generic raw status transitions. |
| S15 | `app/Application/Note/Services/AutoSettleNoteRevisionSurplusRefund.php`, `AutoSettleNoteRevisionSurplusRefundDueRecorder.php`, `AutoSettleNoteRevisionSurplusRefundPaymentRecorder.php`; due then actual cash-out. `app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php`; explicit later payment route. |
| S16 | `app/Application/Inventory/Services/IssueInventoryOperation.php`, `ReverseIssuedInventoryOperation.php`, `AutoReverseRefundedStoreStockInventory.php`; issue and reversal. `app/Application/Note/Services/ReverseIssuedInventoryByNoteService.php`, `CreateTransactionWorkspaceWorkItemPersister.php`; revision and create timing. |
| S17 | `app/Application/Note/UseCases/CorrectPaidWorkItemStatusHandler.php`; status correction. `app/Application/Note/Services/CorrectPaidServiceOnlyWorkItemMutation.php`, `CorrectPaidServiceOnlyWorkItemFinalizer.php`, `FinalizePaidNoteCorrection.php`; financial correction without revision commit. Service-with-stock/external fee-only correction handlers also exist. |
| S18 | `app/Application/Note/Services/DeleteTransactionWorkspaceDraftOperation.php`; `app/Adapters/Out/Note/DatabaseTransactionWorkspaceDraftDeleterAdapter.php`; actor/workspace draft cleanup. `routes/web/note.php`; exposed action inventory. |
| S19 | `app/Application/Note/Services/NoteHistoryProjectionService.php`, `Concerns/BuildsNoteHistoryCurrentRevisionSettlement.php`, `NoteBillingProjectionBuilder.php`, `NoteBillingProjectionComponentRowsBuilder.php`, `NoteBillingProjectionRowMapper.php`; current UI projections. `app/Adapters/Out/Note/Queries/CashierNoteHistoryRowMapper.php`; separate financial and work counts. |
| S20 | `app/Application/Note/Services/NotePaymentTimelineBuilder.php`; `app/Adapters/Out/Payment/DatabaseNotePaymentTimelineReaderAdapter.php`, `Queries/DatabaseNotePaymentTimelineLifecycleContextQuery.php`; event-time payment interpretation. `NoteRefundTimelineBuilder.php`, `NoteRevisionTimelineSummaryBuilder.php` under Note services; refund rows and positional revision summaries. |
| S21 | `app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php`, `TransactionSummaryCashPaymentTotalsQuery.php`; current totals, legacy/component precedence, refund joins. `app/Application/Reporting/UseCases/GetTransactionSummaryPerNoteHandler.php`; historical net cash override. `DTO/TransactionSummaryPerNoteRowAccessors.php`; older DTO formula. |
| S22 | `app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php`, `TransactionCashLedgerPaymentRowsQuery.php`, `TransactionCashLedgerComponentAllocationRowsQuery.php`; actual event money. `OperationalProfitMetricsQuery.php`, `OperationalProfit/CashFlowMetricQuery.php`, `OperationalProfit/ProductCostMetricQuery.php`; cash profit, external cost, movement-based COGS. |
| S23 | `app/Application/Note/Services/PersistNoteMutationTimeline.php`; note events + before/after snapshots. `app/Providers/InfrastructureServiceProvider.php`: `AuditLogPort` → `DatabaseAuditLogAdapter` (legacy table), `AuditEventWriterPort` → `DatabaseAuditOutboxWriterAdapter` (durable canonical capture). |
| S24 | `app/Application/Note/Services/CreateNoteRevisionIdempotencyService.php`; `app/Application/Payment/Services/RecordNotePaymentIdempotencyService.php`, `RecordSelectedRowsRefundIdempotencyService.php`; key/hash replay. Revision request key is nullable; workflow has no base-revision comparison. |
| S25 | `public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`, `summary.js`, `public/assets/static/js/pages/cashier-note-payment.js`; calculator/preset derivations. `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspacePaymentValidator.php`; current full-cash validator boundary. |
| S26 | `app/Adapters/Out/Note/Mappers/NoteMapper.php`; rehydrates total by summing non-canceled persisted work items, not directly from current revision. |
| S27 | `app/Application/Payment/UseCases/RecordCustomerPaymentHandler.php`, `AllocateCustomerPaymentHandler.php`; older separate record/allocate application paths. `app/Core/Payment/Policies/PaymentAllocationPolicy.php` computes payment remaining as payment amount minus already allocated. No cashier route to these separate handlers was found in the inspected route inventory. |

### Persistence and constraints inspected

| Tables / migration anchors | Meaning and limitation |
|---|---|
| `2026_03_14_000100_create_notes_table.php`, `000200_create_work_items_table.php`, `000300_create_work_item_service_details_table.php`, `000400_create_work_item_external_purchase_lines_table.php`, `000500_create_work_item_store_stock_lines_table.php` | Current implementation uses `notes`/`work_items`, not the literal `customer_orders/customer_transactions/customer_transaction_lines` names in AI_RULES 0050. This is a naming/source-map conflict; do not query nonexistent aliases or rewrite standards here. |
| `2026_03_14_000600_create_customer_payments_table.php`, `000700_create_payment_allocations_table.php`; `2026_04_27_000700_add_payment_method_and_cash_details_to_customer_payments.php` | Accepted credited money, compatibility allocations, one cash detail per payment identity. No immutable direct note-root column on a payment is established by the inspected linking query. |
| `2026_04_02_000800_create_payment_component_allocations_table.php` | Unique payment + component type + component ref. Prevents duplicate pair rows, not aggregate over-allocation or duplicate business payment. |
| `2026_03_15_000100_create_customer_refunds_table.php`, `2026_04_02_000900_create_refund_component_allocations_table.php`, `2026_04_06_220200_add_v2_transaction_finance_foreign_keys.php` | Source refund row → payment/note; component allocation → refund/payment/note/work item. Unique refund + component type + ref. Restrictive refund work-item anchors explain retained historical rows. |
| `2026_04_22_000001_create_note_revisions_table.php`, `000002_create_note_revision_lines_table.php`, `000003_add_current_revision_pointer_to_notes_table.php` | Root + revision number unique; revision + line number unique; parent pointer and payload snapshots. Uniqueness is not stale-editor protection. |
| `2026_05_13_000100_create_note_revision_settlements_table.php`, `000200_create_note_revision_surplus_dispositions_table.php`, `000300_create_note_revision_surplus_refund_payments_table.php` | One settlement per revision; distinct due and paid records; paid unique disposition + idempotency key, audit references. Settlement row is a commit-time snapshot, not updated after every later payment. |
| `2026_03_12_000600_create_inventory_movements_table.php`, `000700_create_product_inventory_table.php`, `2026_03_13_000100_create_product_inventory_costing_table.php` | Event quantity/cost versus current quantity and average-cost projection. |
| `2026_05_15_000005_add_unique_inventory_reversal_source_key.php` | Generated `reversal_source_id` is populated for `work_item_store_stock_line_reversal` only. Unique index does **not** cover `transaction_workspace_updated`. Revision serialization remains essential. |
| `2026_04_02_001000_create_note_mutation_events_table.php`, `001100_create_note_mutation_snapshots_table.php` | Note lifecycle events, related payment/refund, before/after; root close fields alone cannot retain every closure. |

## 2. Primitive Contract Matrix

Notation: G = current collectible obligation; P = accepted credited payments; A = current allocation; F = ordinary actual refunds; U = committed revision refund due total; V = actual surplus refund paid; D = remaining committed refund liability; N = money available for current settlement; O = outstanding. Historical gross revision snapshot is called Gsnapshot, never silently substituted for G after component refund.

Coverage codes resolve to exact existing files in section 5. A dash means no direct effect, not no audit requirement. Owners marked GAP are deliberately not invented.

### A. Money / obligation

| PRIMITIVE | BUSINESS MEANING | SOURCE OF TRUTH | ALLOWED TRANSITIONS | FORBIDDEN TRANSITIONS | FINANCIAL EFFECT | STOCK EFFECT | REPORTING EFFECT | AUDIT EVIDENCE | CURRENT TEST COVERAGE | STATUS |
|---|---|---|---|---|---|---|---|---|---|---|
| M01 integer money | Exact rupiah, no fractional hidden balance | S01 Money; ADR-0011 | Integer add/subtract/compare | Float monetary authority | Exact totals | Exact event costs | Exact reconciliation | Persisted integer fields | T01, T02 | LOCKED |
| M02 current gross obligation | What current eligible components charge | Current revision + refund/shadow projection, S06/S19; root S26 competes | Create/revise/neutralize | Historical shadow included as payable | Determines O | Does not itself issue stock | Current sales separate from original snapshot | Revision payload + refund IDs | T04, T06, T09 | DUPLICATED TRUTH |
| M03 payment accepted | Credited settlement, not banknote tender | `customer_payments.amount_rupiah`, S02 | Append valid payment | Rewrite amount/method/time on revision | P increases once | None from payment itself | Event cash-in/transfer-in | Payment id, recorded/paid dates | T02, T03 | LOCKED |
| M04 payment allocated | Distribution of credited money to eligible components | `payment_component_allocations`, S03; legacy bridge S04 | Allocate; rebuild current projection on revision | Allocate tender/change; exceed component/payment capacity | A increases or redistributes, not new P | None | Current allocated amount, not historical cash | Payment + work item + type/ref/priority | T02, T04, T07 | IMPLEMENTED BUT WEAKLY TESTED |
| M05 allocation priority | External first, store second, service last | S03 priority then orderIndex | Fill next eligible capacity; multiple DP | New independent DP engine | Determines funding sources | None | Component attribution | Stored allocation priority | T07; gauntlet adjacent | IMPLEMENTED BUT WEAKLY TESTED |
| M06 unallocated payment | Accepted money deliberately awaiting allocation | S27 supports separate payment record and remaining=payment−allocated; no approved cashier wallet contract | Legacy record→later allocate exists; cashier S02 must allocate atomically | Treat lost revision linkage as valid wallet | Unallocated balance can exist through older application path; lifecycle/ownership GAP | None | Global cashflow can see money absent from note reports | Payment ID + later allocation, no invented note owner | T07 separate APIs, no cashier wallet proof | CONTRACT GAP |
| M07 ordinary refunded amount | Money returned against source payment/component | `customer_refunds` + `refund_component_allocations`, S13 | Append within source limits | Sum both tables as two refunds | F increases once | Depends on explicit refund path | Ordinary outflow | Refund + source payment + component | T08, T09 | LOCKED |
| M08 committed surplus/due | Money no longer available for current settlement | `note_revision_surplus_dispositions`, S15 | Pending surplus → refund_due | Consume committed due in later revision | D increases; N decreases | None | Liability, not cash-out | Disposition + settlement + audit | T10 | IMPLEMENTED BUT WEAKLY TESTED |
| M09 surplus refund paid | Actual payout of committed surplus | `note_revision_surplus_refund_payments`, S15 | Due → partial/full paid | Reclaim in later revision; fake ordinary refund | V increases; D decreases; N unchanged on paying already committed due | None | Separate surplus outflow | Disposition/idempotency/audit | T10, T11 | IMPLEMENTED BUT WEAKLY TESTED |
| M10 net paid / available | P less committed customer returns exactly once | ADR-0042 formula; S05 vs S06/S07/S10 not one universal owner | Recompute on event | Subtract refund twice; spend paid-out surplus | N = P − F − V − D | None | Separate N from physical cash net | Link complete ledger set | T04/T05/T10; multi-revision gap | DUPLICATED TRUTH |
| M11 outstanding / surplus | Debt or excess after available settlement | S05 formula; current S06; other S07 | O=max(G−N,0), surplus=max(N−G,0) before disposition | UI status or method decides O | DP1/2/3+ and final are normal events; price change changes G only | None | Debt and surplus explicit | Revision settlement snapshot plus later events | T01/T02/T04/T05 | DUPLICATED TRUTH |
| M12 cash received / change | Physical tender and amount handed back | Cash detail, S02; ADR-0044 | received ≥ intent; change=received−intent | Count change as refund; derive intent solely from tender | Credited amount remains intent | None | Additional tender columns, no inflated cash-in | One immutable cash detail | T02, T03 | LOCKED |
| M13 settled / debt label | O=0 versus O>0, qualified for zero/refunded note | S06 and operational evaluator S08 | Payment/revision changes financial projection | Method='DP' makes debt; Lunas rewrites history | None independently | None | Label follows named basis | Payment/revision/refund provenance | T02/T05/T06 | DUPLICATED TRUTH |

### B–D. Operational state, component rights, versioning

| PRIMITIVE | BUSINESS MEANING | SOURCE OF TRUTH | ALLOWED TRANSITIONS | FORBIDDEN TRANSITIONS | FINANCIAL EFFECT | STOCK EFFECT | REPORTING EFFECT | AUDIT EVIDENCE | CURRENT TEST COVERAGE | STATUS |
|---|---|---|---|---|---|---|---|---|---|---|
| O01 persisted open/closed | Root operational state and actor/time | Note state mutations S08, NoteWriterPort | open→closed with actor; closed→open via explicit reopen | Treat raw work status as root state | No money event itself | None | Stored lifecycle separate from financial badge | note_closed/note_reopened snapshots | T05/T12 | LOCKED |
| O02 ever closed / second close | Every close must remain reconstructable | `note_mutation_events`, S23; root fields hold latest close | Append second close after reopen | Overwrite entire history with latest timestamp | None | None | Audit/history retains both | Related payment IDs and timestamps | T05 verifies two distinct events | LOCKED |
| O03 revision-caused reopen | New debt on previously closed root | S08 Reopen + S09 ordering | closed→open before inline payment, then possible close | Skip transition due to stale closed flag | No synthetic payment | None | Debt queue and close history agree | actor/reason/time + O | T05; no all-refunded/surplus composition | IMPLEMENTED BUT WEAKLY TESTED |
| O04 fully refunded root → new work | New current lines while old lines shadow | ADR-0042; raw STATE_REFUNDED; S08 reopen handles closed only | Authorized revision with zero editable old rows per contract | Old refunded identity revived | New G/new debt | Only new source issues | Refund history retained | Revision + explicit state transition needed | T09/T14 adjacent; root transition missing proof | SOURCE CONFLICT |
| O05 zero obligation | Empty active result versus settled positive obligation | S08 status policy returns open for G≤0; finalize-refund can set refunded; S19 badge uses O | Explicit fully-refunded finalization | Infer operational close from 0−0 alone | Zero O need not mean new successful settlement | None | Must label refunded/empty distinctly | Finalize event | T12, T09 | CONTRACT GAP |
| C01 raw work status | Physical job progress open/done/canceled | `work_items.status`, S14 | open→done/canceled; canceled→canceled no-op | canceled→open/done | No automatic money effect in raw setter | None in raw setter | Work counts only | Correction timeline when orchestrated | T12/T13 | DUPLICATED TRUTH |
| C02 current component identity | Rights belong to type/ref within current version | S03/S11: work item ID or stock/external line ID | Replacement produces fresh IDs; old IDs historical | Re-add same product grants old ID rights | Allocate/refund current only | New movement source required | Current rows exclude old anchors | revision→work_item_root_id→component IDs | T09/T14 | IMPLEMENTED BUT WEAKLY TESTED |
| C03 refund shadow | Refunded/neutralized component loses current rights | S12–S14; S06 collectible skipper | Refund→shadow, retain history | Pay/refund old component again | Removes collectible capacity; retains F | At most one eligible return | Current result + historical refund | Refund allocation FK anchors | T06/T09/T14 | IMPLEMENTED BUT WEAKLY TESTED |
| C04 identity across reorder | Explain add/remove versus similar replacement | S20 summary indexes lineNo; replacement rebuilds IDs | Preserve snapshot; show honest membership change | Treat equal positions/product names as lineage | Must not move refund rights | Must not reuse reversal source | History narrative cannot imply false replacement | Snapshot IDs versus positional summary | T14 adjacent, reorder lineage gap | SOURCE CONFLICT |
| V01 immutable version graph | R1→R2→R3 with one current pointer | S09/S11 + revision tables | Append child; advance root pointer | Rewrite parent; sum all revisions as current | New obligation only | Reverse/reissue through S16 | Historical version readable | parent/revision number, actor/reason | T04/T09/T14 | IMPLEMENTED BUT WEAKLY TESTED |
| V02 carry-forward | Old accepted money minus committed returns funds new version | S05 + S10 | Reconcile without copying payments | Repeat refund subtraction during every rebuild | N carried, A rebuilt/capped | Independent event replay | Historical cash unchanged | Settlement + old payment links | T04/T10; B targets second rebuild | DUPLICATED TRUTH |
| V03 full-layer snapshot | Historical product/service/package/external shape | S11 payload; movement source supplies cost | New snapshot with labels/qty/split | Current master replaces old facts | Snapshot prices/cost basis | Cost from original movement | Historical reconstruction | Product-name snapshot, package split, external amounts | T14; exact floor/catalog/cost linkage incomplete | IMPLEMENTED BUT WEAKLY TESTED |
| V04 stale editor | Old draft cannot overwrite a newer revision | ADR-0045; S09 locks current, request lacks base version comparison | Reject stale/conflict per contract | Silent last-write-wins | Protect latest G/A/refund | Prevent stale replacement | No lost history/current state | Base identity absent from inspected contract | T14 only stale row tests, not stale edit | MISSING PRIMITIVE |
| V05 duplicate command | Same semantic command has one effect | S24 + idempotency_records; unique scopes | Same key/hash replay; changed payload reject | Duplicate version/payment/refund | No double P/F/V | No duplicate issue/reversal | One business event effect | key/hash/result IDs | T02/T09/T14/T15 | IMPLEMENTED BUT WEAKLY TESTED |

### E–F. Refund and distinct mutation operations

| PRIMITIVE | BUSINESS MEANING | SOURCE OF TRUTH | ALLOWED TRANSITIONS | FORBIDDEN TRANSITIONS | FINANCIAL EFFECT | STOCK EFFECT | REPORTING EFFECT | AUDIT EVIDENCE | CURRENT TEST COVERAGE | STATUS |
|---|---|---|---|---|---|---|---|---|---|---|
| F01 logical refund action | One intent can consume several historical payments | SelectedRowsRefundPlan + S13 bucket loop | One plan→N source refund rows | Call N source rows N customer actions | Sum buckets once | Reversal once/source | Group action, drill into sources | `selected_rows_refund_plan_recorded.refund_ids`, idempotency result | 0065 §17 proves split; T06/T08 adjacent | IMPLEMENTED BUT WEAKLY TESTED |
| F02 refundable remaining | Paid component/source less prior refund and subject to eligibility | S12/S13; RefundPairLimitGuard | Refund within each source capacity | Repeat beyond capacity; stale target | F increases up to limit | Current return policy | No duplicate totals | Pair and component allocations | T08/T09 | IMPLEMENTED BUT WEAKLY TESTED |
| F03 ordinary eligibility | Current supported refund targets | RefundComponentTypePolicy: product_only_work_item/service_store_stock_part | Settled eligible selected row; full product refund | Service/external or open selected row in current path | Unsupported attempts no writes | No writes on blocked attempts | No report change | Failure envelope + unchanged row counts | T08/T16 | SOURCE CONFLICT |
| F04 independent money/stock decision | Money-only versus goods-return refund | ADR-0042; S13 always calls full-row inventory reversal for supported stock | Contract permits no-stock-return compensation | Assume every refund returns goods | Chosen money effect | Must follow physical decision | COGS must reflect actual return | Decision field required by contract | T16 characterization preserves restricted policy | MISSING PRIMITIVE |
| X01 delete workspace draft | Discard actor's unsaved editor data | S18 `transaction_workspace_drafts` | Delete actor/workspace draft; repeat harmless | Delete persisted note/payment/history | Zero | Zero | Zero report delta | No business audit promised for scratch data | Create workspace tests adjacent | IMPLEMENTED BUT WEAKLY TESTED |
| X02 cancel via refund | Neutralize selected whole rows after refund plan | S14 + S13 | Eligible selected row→canceled; active total sync | Cancel paid row as shortcut without refund | G reduced, F records returned money | Explicit refund reversal | Canceled history separate from active totals | note_rows_canceled_via_refund | T08/T09 | IMPLEMENTED BUT WEAKLY TESTED |
| X03 generic paid status correction | Existing endpoint can request canceled on paid row | S17 + S14; route + request permit canceled | Current source updates raw status | ADR-0052 forbids paid cancellation bypass | No refund/total sync in handler | No reversal in handler | Raw mapper/report divergence risk | Legacy audit before/after | T13; contract consistency not proven | SOURCE CONFLICT |
| X04 nominal service correction | Existing fee correction mutates same row/total | S17 mutation/finalizer | Current source updates price and returns refund_required | ADR-0045 accepted edit without new version | Delta total; refund_required is not due/paid ledger | None for service fee | Root vs revision may disagree | note mutation + legacy audit | T13; later version contract conflict | SOURCE CONFLICT |
| X05 whole-note cancel/delete | Explicit complete-note lifecycle action | No route found in S18; no canceled root constant in S01/S08 | No new behavior defined here | Map cancel to SQL deletion/refund-all automatically | GAP | GAP | GAP | GAP | No supported end-to-end action identified | MISSING PRIMITIVE |
| X06 reset | Supposed erase/zero operation | **NOT A DOMAIN ACTION** in inspected cashier routes/use cases | Local UI mode reset/draft cleanup only | Erase history, zero reports, reverse money/stock implicitly | None defined | None defined | No “clean report” promise | No reset event defined | No domain reset test appropriate | MISSING PRIMITIVE |

### G–H. Inventory, reporting, audit

| PRIMITIVE | BUSINESS MEANING | SOURCE OF TRUTH | ALLOWED TRANSITIONS | FORBIDDEN TRANSITIONS | FINANCIAL EFFECT | STOCK EFFECT | REPORTING EFFECT | AUDIT EVIDENCE | CURRENT TEST COVERAGE | STATUS |
|---|---|---|---|---|---|---|---|---|---|---|
| I01 stock in/current qty | Physical on-hand projection backed by movements | product_inventory + inventory_movements; S16 | Valid incoming/reversal; locked issue | External cost enters stock; negative issue by default | Cost projection only | Signed quantity | Stock/value by period | Source type/id and movement id | T17 | LOCKED |
| I02 stock out | Sale/usage event, including package parts | S16 CreateWorkItemPersister→IssueInventoryOperation | Issue at persisted creation/replacement, even unpaid | Infer issue solely from settlement | Captured average unit cost | −qty/source line | COGS event, stock qty | Immutable source/movement | T17/T18 | LOCKED |
| I03 refund reversal | Exact compensation for earlier issue | S16 original movement qty/cost | One positive reversal/source; no second return | Return another product/new similar row | Reverse original event cost | +original qty | Reduce net COGS | reversal source + generated unique key | T17 and 0065 §17 | LOCKED |
| I04 revision correction | Reverse old live issues, issue replacement graph | S10/S16; skip already refund-reversed line | Append correction + fresh issue | Back-edit old movement; duplicate refund return | Recompute current costing via official strategy | Explicit +old/−new, even unchanged retained stock rows | Net COGS must include both buckets | transaction_workspace_updated versus refund source | T18; D adds full cardinality | IMPLEMENTED BUT WEAKLY TESTED |
| I05 cost basis | Historical issue average, exact reversal basis | Inventory movement unit/total cost; product_inventory_costing | Incoming average; outgoing snapshot; reverse original cost | Latest master cost rewrites old issue | COGS exact integer | Qty independent of price | Read movement cost, not fresh master price | Source-linked cost snapshot | T17/T18; changing-master chain gap | IMPLEMENTED BUT WEAKLY TESTED |
| I06 external purchase | Transaction-local outside cost/pass-through | work_item_external_purchase_lines; ADR-0003/0018; S11/S22 | Add/change via revision, snapshot | Treat as reusable stock/receipt without new contract | Charge/cost explicit, not pure service profit | Zero inventory movements | External cost distinct from stock COGS | External line + revision | T16; gauntlet | LOCKED |
| I07 external lifecycle | Bought/received/delivered/refundable/reusable | No complete dedicated lifecycle in inspected note model | Current cost entry/revision; refund blocked | Invent supplier/ownership/reuse transitions | Upfront funding enforcement not located; GAP | No conversion-to-stock action found | Cost/refund timing gaps | Label/amount snapshot insufficient for procurement state | T16 proves blocking, not lifecycle | CONTRACT GAP |
| R01 current report truth | Current obligation/debt, not sum of versions | S21 read models | Read final projection after mutation | Repair data during report read | Read-only | Read-only | Current G/O/refund/due columns | Trace to source IDs | T19 | DUPLICATED TRUTH |
| R02 historical money reporting | Credited event money-in minus actual outflow | S22 payment/refund/surplus events | Aggregate once/source id | Tender as revenue; due as actual cash-out | P−F−V | None | Cash/transfer separated; due remains liability | Ledger source_table/source_id | T02/T11/T19 | IMPLEMENTED BUT WEAKLY TESTED |
| R03 sales/profit/COGS distinction | Current charge is not cash-profit | S21 versus S22 + product-cost queries | Named current/period/event modes | One unlabeled “revenue” number for all bases | Cash profit subtracts net costs | Read movements | External pass-through not service profit | Ledger + active/snapshot membership | T19; E period/identity gap | DUPLICATED TRUTH |
| A01 durable sensitive audit | Business mutation and required durable evidence commit together | ADR-0042; S23 bindings differ | Outbox capture then async materialize | Legacy-only capture called canonical outbox compliance | Rollback required if durable capture fails | Same atomicity | Reports never repair missing audit | Outbox/event/snapshot IDs; legacy note timeline separate | T20 proves legacy audit rollback, not universal outbox | SOURCE CONFLICT |

## 3. State transition map and operation consequences

### 3.1 Independent axes

    Financial: G, P, F, U, V, D -> N -> O / surplus -> label
    Root: open -> closed -> open (reopened) -> closed
    Full refund: closed -> refunded
    Work item: open -> done OR canceled; canceled cannot regain ordinary rights
    Identity: current Rn component -> historical/shadow; new Rn+1 component gets new rights
    Inventory: stock_out(source L) -> exact stock_in(reversal source L)
    Revision: R1 -> R2 -> R3; one root pointer; older snapshots remain

`reopened` is event/metadata, not a third persistent root state. `refunded` exists on the root, while partial/component refund may leave the root closed. Raw work items may remain open after financial settlement; never force them done to fix a financial badge.

New debt after partial refund on a closed root is covered by T05. New debt after root STATE_REFUNDED is a separate O04 conflict: `reopenIfNeeded` ignores it, while `close()` accepts only open. Do not expand T05's closed conclusion to that state.

### 3.2 Formal finance oracle, with named bases

For ordinary refunds modeled as actual payout, and revision surplus due/paid linked to their dispositions:

    D = sum(max(disposition_due − payments_for_that_disposition, 0))
    N = P − F − V − D
    physical_money_net = P − F − V
    O = max(G − N, 0)
    unresolved_surplus = max(N − G, 0) before new disposition

Use per-disposition pairing in the oracle. S05 currently uses `ordinaryRefunded + max(sumSurplusPaid, sumSurplusDue)`; equivalence requires valid paid≤due membership. Do not add U and V together and subtract the same obligation twice.

`physical_money_net` includes cash and transfer settlement. A literal till balance needs instrument-specific inflows/outflows; do not label aggregate transfer money as physical banknotes. `change` is returned tender within a payment and already excluded from P, not F/V.

S05 snapshots use `max(P−committed,0)`. Negative raw availability must be detected as an integrity issue in the oracle, not hidden by an assertion that only sees the clamp.

G must exclude noncollectible refund shadow. Gsnapshot and `notes.total_rupiah` can retain product value after a package-component refund; section 0065 and current collectible builders demonstrate why raw total is not automatically payable. The blueprint does not decide to rewrite those snapshots.

What changes on revision: current rows/IDs, total, current pointer, current allocations, stock correction/new issues, settlement snapshot, actor/reason/time.

What never changes: original payment/tender/change, original refund sources, old inventory movements, earlier revision payloads, historical audit events.

What is carried: available settlement and committed/paid refunds, plus references to old effects. What is recomputed: current eligible allocation/obligation and outstanding/surplus. Historical: old versions and stale IDs. Actionable: only current eligible components and explicit remaining refund-due execution.

### 3.3 Existing operations, not synonyms

| Operation | Preconditions and mutation | Historical / money / stock / reporting / repeat consequence |
|---|---|---|
| Draft cleanup | Actor + workspace key; deletes `transaction_workspace_drafts`. Successful create invokes it. | Scratch data removed; saved note and ledgers untouched. Zero money/stock/report delta. Repeat is harmless. No standalone persisted-note DELETE route found. |
| Unpaid line removal | Edit/revision of active note, at least one valid current row retained. | New revision; old snapshot remains. No refund money when P=0. Previously issued stock reverses. Current G/O shrink; historical movement remains. Key replay must not add another version. |
| Ordinary selected refund | Current supported settled stock component; reason/actor; resolve buckets under locking flow. | Source refund + allocations; whole-row cancellation only when plan says cancellable. Goods return through explicit movement. Repeat same key replays; new-key historical target rejects. |
| Revision | Authorized current edit, valid prices/stock; root lock. | Replacement and immutable revision; carry money, possible automatic surplus due/paid. Reissued stock is not a second sale. Replay protected when key present; stale-editor gap remains. |
| Paid status correction | Existing cashier-prefixed route, paid policy, line_no, target status, reason. | Only raw status + legacy audit/projection; canceled accepted by request/transition. No money/stock reversal or new version in handler: X03 conflict, not a supported substitute for refund. |
| Paid nominal correction | Paid service-only or fee-only application handler; reason/actor. | Same row and total updated; refund_required reported in payload/timeline, not actual cash-out. No revision/due commit in inspected finalizer: X04 conflict. Repetition/idempotency and stale line_no need characterization. |
| Manual admin reopen | Existing `/admin/notes/{noteId}/reopen` route, closed root and authorization. | Operational event only, no invented payment/refund. Financial zero/outstanding distinction must remain visible. Existing reopen tests cover focused path. |
| Whole-note cancel/delete/reset | No supported complete contract in inspected route/domain surface. | **NOT A DOMAIN ACTION for reset**; whole-note cancel/delete remain missing. No history erasure, zero-report, cash return, or stock return semantics are inferred. |

## 4. Duplicated-truth audit and highest risks

SAFE duplication means equal semantics with an explicit equivalence boundary. READ-MODEL DERIVATION means a legitimate differently named projection. DANGEROUS SECOND SOURCE OF TRUTH means divergent authority or insufficiently constrained recomputation. No refactor is proposed in this step.

| ID / primitive | Independent sites and formula/basis | Classification | Risk / required discriminator |
|---|---|---|---|
| D01 G | S26 sum non-canceled persisted rows; notes.total; revision grandTotal; S06 collectible components | DANGEROUS SECOND SOURCE OF TRUTH | Retained non-canceled refund anchor or package shadow may inflate raw total. Compare all named totals, not just labels. |
| D02 P linkage | S04 union legacy/component/refund payment IDs; S21 same union; S22 fallback queries | SAME CONTRACT / SAFE DUPLICATION within intact one-note linkage | Deduplicate identities, never `SUM(DISTINCT amount)` (equal-value payments are separate). Full disappearance of all links after surplus is not covered; test source identity loss separately. |
| D03 A precedence | S04 prefers component rows and excludes their legacy payment IDs; S21 query prefers legacy rows and excludes components for matching pair | DANGEROUS SECOND SOURCE OF TRUTH | Divergent compatibility values produce different allocated totals. Existing historical cash fix does not make allocation precedence equal. |
| D04 N/O | S05 max(legacy A,linked P,component A) minus ordinary+surplus commitment; S06 current component projector; S07 gross-linked ordinary-refund formulas | DANGEROUS SECOND SOURCE OF TRUTH | Surplus due/paid and historical refund basis differ. Do not claim universal settlement owner exists. |
| D05 replay N | S10 capture current component A then subtract all component refunds per payment again; S05 builds ledger carry independently | DANGEROUS SECOND SOURCE OF TRUTH | After one net rebuild, a later rebuild may subtract historical refund again. B checks allocation distribution after every revision, not only final displayed net. Source hypothesis; not newly executed RED. |
| D06 close eligibility | S07 auto-close max(A,P)−all ordinary F versus revision reopen S05 including surplus | DANGEROUS SECOND SOURCE OF TRUTH | Example oracle after P=130003, surplus paid=30001, G rises to140009: N=100002, O=40007; gross-minus-ordinary path sees O=10006. New payment10006 must not close root with debt30001. This is a source-derived counterexample, not runtime proof. |
| D07 correction paid | NotePaidStatusPolicy subtracts current refunds; S07 auto-close subtracts all ordinary refunds; S06 resolves current collectible rows | DANGEROUS SECOND SOURCE OF TRUTH | A historical refund can affect permission differently from payment eligibility. C probes exact state. |
| D08 billing | Direct component-key NoteBillingProjectionRowMapper versus workspace row remainder redistribution in NoteBillingProjectionComponentRowsBuilder; current projector adds legacy reconciliation | READ-MODEL DERIVATION, equivalence not fully proven | Same component selection must present same outstanding; a row remainder must not invent funded component rights. |
| D09 root/work/financial status | S08 root enum; WorkItem.status; S19 financial line counts vs raw open/done/canceled counts | READ-MODEL DERIVATION | Different concepts are legitimate; same “Selesai” label is not proof they mean the same thing. Presentation mismatch is retained as risk, no patch. |
| D10 current pointer | S11 pointer lookup; max revision number for next; bootstrap/detail repair paths | READ-MODEL DERIVATION | Existence of history ≠ valid current pointer. Pointer repair regression is already closed in handoff 0025. New stale-editor assertion is different. |
| D11 refundable | S12 type allowlist/row status; S13 pair/component balance; UI action projection | DANGEROUS SECOND SOURCE OF TRUTH where UI advertises blocked service/external action | Balance alone is not permission. ADR-0042 richer behavior conflicts with allowlist; separate current rejection proof from desired contract. |
| D12 calculator | S25 reads backend payable but `effectivePaymentTotal` returns max(edited total−context.netPaid,0); product preset sums raw productTotal | DANGEROUS SECOND SOURCE OF TRUTH if presented as final payable | Preview may be useful for unsaved draft, but it ignores committed returns/current component eligibility. Tag draft estimate; submit uses backend. Detail JS sums selected outstanding; distinguish selected versus note-level debt. |
| D13 payment timeline | S20 derives event obligation by revision.created_at and refund timestamps; persisted payment/cash values remain immutable | READ-MODEL DERIVATION | Same-second order, inline payment before snapshot, refund date vs action timestamp may reclassify event “Pelunasan/Sisa”. Existing timeline tests cover selected examples, not all chronology. |
| D14 refund history | S13 logical plan audit contains refund_ids; S20 renders one timeline row/customer_refund | READ-MODEL DERIVATION | N ledger rows can be one action. No grouping by amount/date/name; stable operation link needed. No dedicated logical refund FK in inspected refund schema. |
| D15 revision narration | S20 maps lineNo, emits at most three changes; S11 stores revision/work-item identity | DANGEROUS SECOND SOURCE OF TRUTH for lineage claims | Removing/reordering can be narrated as price/name replacement. Snapshot is evidence; summary is not identity authority. |
| D16 report money | DTO netCash=A−F; handler override netCash=P−F−V; S22 cashflow reads actual events | DANGEROUS SECOND SOURCE OF TRUTH for direct DTO consumers | Closed historical cash fixes use override; do not regress them. Test dataset/export parity and direct-consumer inventory before consolidation. |
| D17 report O | S21 CASE uses note_history_projection only when ordinary refund exists, otherwise G−A; fallback G−A+F | DANGEROUS SECOND SOURCE OF TRUTH | Missing/stale projection, surplus-only state, and legacy precedence can alter debt. Report must fail a comparison, never repair the note. |
| D18 stock available | product_inventory for issue lock; UI cached lookup; movement history for reconciliation | READ-MODEL DERIVATION | Lookup is a preview. Under-lock issue owns acceptance; insufficient-stock failure must roll back all money/version writes. |
| D19 COGS/external cost | S22 movement buckets vs package-specific COGS queries; external current lines less refund allocations | READ-MODEL DERIVATION with period/membership gap | Preserved historical external rows/current replacement and cross-date subtraction need explicit membership proof. No new costing formula. |
| D20 audit | S23 legacy AuditLogPort versus canonical AuditEventWriterPort/outbox; note timeline separately | DANGEROUS SECOND SOURCE OF TRUTH for compliance claims | Revision/selected refund/payment legacy capture is not evidence of mandatory durable outbox capture. Inject failure at actual bound port, not just any audit mock. |
| D21 semantic command identity | S24 revision replay hashes payload via CreateTransactionWorkspaceIdempotencyScopeResolver; target noteId is a separate handler argument | DANGEROUS SECOND SOURCE OF TRUTH | Actor/operation/key lookup does not itself prove target-root membership. Controller passes validated body separately from route noteId; same body/key against another root could replay the first result. Characterize root binding before calling revision idempotency complete. |
| D22 accepted partial intent | S07 inline partial cash returns min(requested intent,outstanding); S06 note partial rejects amount≥outstanding | DANGEROUS SECOND SOURCE OF TRUTH | Same oversized partial intent may be silently clipped in one surface and rejected in another. ADR-0044 accepted-intent boundary and ADR-0022 no-silent-adjust rule need a focused surface comparison; normal A values remain below outstanding. |
| D23 remaining payment / legacy allocation | S27 separate allocation subtracts historical refunds from A and reads note without getByIdForUpdate; S02 combined flow locks note and uses component capacity | DANGEROUS SECOND SOURCE OF TRUTH | Lower-level entry point is not evidence of approved cashier unallocated money. Cross-note allocation also invalidates a naive sum of whole payment amount per note. Establish caller/scope and serialization before generalizing D02. |

### Priority findings requiring classification before any patch

1. **Cross-version money:** D04–D06. Chain B must prove both source-event conservation and current allocation after a second post-refund revision and paid-out surplus. A plausible final net alone can hide lost allocation. The older unallocated/allocate paths S27 also require explicit scope boundaries before claiming all payment callers are safe.
2. **Paid cancellation/correction bypass:** X03/X04 are real source paths, not invented operations. Request permits canceled on paid row; nominal correction updates without next revision. Compare against ADR-0042/0045 and characterize exact consequences.
3. **Stale editor:** root lock serializes operations but does not know which revision the browser edited. Missing base-version contract is V04, distinct from stale refund-ID protection.
4. **All-refunded reopen:** O04 is separate from the closed-root path fixed in 0065. Do not amend that closure without a new focused test.
5. **Audit:** explicit binding proves ordinary legacy audit is not canonical outbox; durable capture obligation remains A01 conflict.
6. **Refund capability mismatch:** accepted ADR-0042 describes service compensation, no-stock-return and external decisions, while actual allowlist remains store-only. Do not weaken old tests or silently implement missing capabilities in this blueprint.
7. **Chronology:** S09 `effectiveAt()` passes note transaction date at midnight into auto-surplus occurredAt. ADR-0027 distinguishes effective business date from actual action time. This is a **SOURCE CONFLICT**, beyond a mere formatting concern; retain both dates, characterize event-time consequences.
8. **No universal historical note linkage:** payment root discovery depends on surviving allocation/refund links. If an entire payment is clipped out by surplus without ordinary refund, it may disappear from note-linked readers. GAP until a focused fixture proves or refutes it; global cashflow may still count it.

## 5. Current proof inventory and coverage gaps

| Code | Existing exact test path(s), relative to repository root | Proven intent / limit |
|---|---|---|
| T01 | `tests/Unit/Core/Shared/ValueObjects/MoneyTest.php`; `tests/Unit/Application/Note/Services/BuildNoteRevisionSettlementTest.php` | Local arithmetic owner tests; do not repeat formula tests in every HTTP suite. |
| T02 | `tests/Feature/Note/ExistingNoteCashSettlementIntentFeatureTest.php` | 20k intent/100k tender then 80k settlement, immutable cash details, changed-payload replay rejection, timeline and reports; handoff 0024 records prior proof. |
| T03 | `tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php`; `CreateTransactionWorkspaceInlinePaymentAmountResolverFeatureTest.php` in same directory | Create/inline boundaries; not proof of every existing-note path. |
| T04 | `tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php`; `RevisionSettlementHistoricalPaymentFeatureTest.php`; `NoteRevisionSettlementCarryForwardFeatureTest.php` | Delta cash/transfer and refund-only historical payment linkage. |
| T05 | `tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php` | Partial-refund closed root; 200k carried, revised865k, inline20k, final645k; two close events. 0065 §20 historical GREEN. |
| T06 | `tests/Feature/Note/CashierNoteRefundHistoryPresentationFeatureTest.php` | Product/package refund, revision, new payment, refresh, desktop/handset HTML, replay. It does not execute a real responsive browser. |
| T07 | `tests/Unit/Application/Payment/Services/AllocatePaymentAcrossComponentsTest.php`; `ResolveNotePayableComponentsTest.php`; `tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php`; `AllocateCustomerPaymentFeatureTest.php` | Priority/capacity and legacy entry points. |
| T08 | `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`; `RecordCustomerRefundFeatureTest.php`; `tests/Unit/Application/Payment/Services/RefundPairLimitGuardTest.php`; `AllocateRefundAcrossComponentsTest.php` | Source pair/component limits and selected plan; multi-DP logical action grouping not fully locked. |
| T09 | `tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php`; `RefundAfterRevisionCurrentRowBoundaryFeatureTest.php`; `tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php` | Anchors, current-only target, no stock resurrection. |
| T10 | `tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php`; `CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php`; `RecordNoteRevisionSurplusRefundPaymentHandlerTest.php` | Due/paid carry, source limits. Does not prove every preview/close consumer. |
| T11 | `tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php`; `PaymentTimelineRevisionTruthFeatureTest.php`; `tests/Feature/Payment/PaymentTimelineRefundHistoricalAnchorFeatureTest.php` | Event-time cash, history and refund-linked payment. |
| T12 | `tests/Unit/Core/Note/NoteOperationalStateTransitionsTest.php`; `tests/Feature/Note/NoteOperationalStatePersistenceFeatureTest.php`; `ReopenClosedNoteFeatureTest.php`; `AdminReopenClosedNoteHttpFeatureTest.php` | State/actor/time and controlled reopen; not all-refunded root. |
| T13 | Existing correction handlers and correction-history feature tests, including `tests/Feature/Note/NoteCorrectionHistoryBuilderFeatureTest.php` and `NoteCorrectionHistoryPageFeatureTest.php` | History rendering exists; no claim these lock paid-cancel safety or new ADR-0045 compatibility. |
| T14 | `tests/Feature/Note/TransactionEditRefundPaymentStockReportingHardeningTest.php`; `AbsurdTransactionGauntletFeatureTest.php`; `CashierNoteRevisionSubmitFeatureTest.php` | Broad current chains, master-price immutability and same-key revision replay; no explicit stale-base comparison found. Keep existing gauntlet; do not duplicate all assertions. |
| T15 | `tests/Feature/Payment/PaymentConcurrencyCharacterizationFeatureTest.php`; `PaymentRefundConcurrencyCharacterizationFeatureTest.php`; `tests/Feature/Note/CreateNoteRevisionSurplusRefundDueRaceInvariantTest.php` | Existing race tests; future proof must establish actual separate connections/barriers, not sequential tests called concurrent. |
| T16 | `tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php`; `RefundReportingOwnerDecisionV2CharacterizationTest.php` | Current external/service restrictions; blocked mixed selection atomicity. Source behavior is not promoted into richer ADR contract. |
| T17 | `tests/Feature/Inventory/IssueInventoryFeatureTest.php`; `ReverseIssuedInventoryOperationFeatureTest.php` | Negative-stock rejection, exact reversal, repeat no-op and DB duplicate guard; 0065 adds historical real source proof. |
| T18 | `tests/Feature/Note/NoteRevisionStoreStockInventoryLifecycleFeatureTest.php`; `NoteRevisionStoreStockRollbackFeatureTest.php` | Reverse old source, fresh issue, cost, projection, rollback; D adds mixed per-source history. |
| T19 | `tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php`; `TransactionSummaryPerNoteHardeningFeatureTest.php`; `tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php`; `TransactionReportPdfExportFeatureTest.php` | Dataset/page/export aggregate parity; not all five chains or cutoff semantics. |
| T20 | `tests/Feature/Note/NoteRevisionRollbackFeatureTest.php`; `tests/Feature/AuditLog/DatabaseAuditOutboxWriterAdapterTest.php` | Revision rolls back on legacy audit failure; outbox adapter tests do not prove every financial caller uses it. |

Missing composition evidence: second refund-aware replay, surplus-paid → later partial payment → close eligibility, all-refunded new receivable, stale base edit, same-second timeline ordering, note linkage after wholly clipped payment, multi-payment refund logical grouping, all movement sources across repeated replacement, outbox failure on each actual mutation path, current report versus chronological report cutoff, independent money/no-stock-return choice.

## 6. Shared fixture and automated execution rules

Use an isolated test database, not 0064/0065 production notes. Dates below are fixture business dates: 2026-09-15, Asia/Makassar. Inject ClockPort and advance event time by one minute per accepted action; use distinct recorded times for normal chains. A focused chronology probe deliberately uses same-second actions separately. Set application time consistently for cashier access-window tests.

Each chain starts fresh. Seed only product/catalog/template and balanced opening inventory facts. Do not seed lifecycle results being tested. Use real create/revision/payment/refund HTTP or application boundaries. Opening stock must include source movements and costing, not just a product_inventory number. Supplier receipt finance is outside the fixture's assertions.

| Component | Quantity / selling value | Opening qty / average cost | Meaning |
|---|---|---|---|
| P: product-only “QA P” | 3 × 47,513 = 142,539 | 17 / 19,721 | Store-stock product; first store component in order. |
| S: “QA service” | 63,719 | N/A | Service only. |
| K: package “QA package” | Q qty2 × 28,637 = 57,274; service41,983; total99,257 | Q:23 / 11,503 | Store-stock package decomposition; no extra profit field on top of the explicit service. |
| E: “QA outside” | External label/cost53,127 + service37,291 = 90,418 | N/A | One external line; no package_auto_split for external source. |
| R: replacement “QA R” | 33,571/unit | 19 / 13,709 | Not initially on note. |
| Initial note | **395,933** | Initial note COGS82,169 | Four domain rows, six payable components. |

Set price floors ≤ these explicit sale prices. For browser package entry seed/select an active template matching Q, qty2 and service41,983 through the existing package setup; for revised composition use the supported editable package path. Do not bypass a template guard to make the test pass. If that UI cannot represent the exact decomposition, record a UI/setup GAP and exercise the same application contract independently.

Coverage shape: A owns payment sequence, B owns refund/version/new debt, C owns action distinctions, D owns inventory identity, E owns reporting. Checkpoints are named assertions/helpers, not tests that depend on another test's mutable DB. Reuse scenario builders only for actions/identities. Expected totals are literal independent tables below; helpers must not call production calculation code to derive expected values.

### Chain A — Payment / debt / cash

Create the mixed note with inline DP1. Do not create an unfunded external purchase merely for convenience. Keep row order fixed through A's revision.

| Checkpoint/action | G | P=A=N | O | Cash received / change for this event | State / projection |
|---|---:|---:|---:|---|---|
| A1 create + DP1 cash73,129 | 395,933 | 73,129 | 322,804 | 100,003 / 26,874 | open; partial; external53,127 funded first, P20,002 |
| A2 DP2 transfer89,457 | 395,933 | 162,586 | 233,347 | N/A; no cash-detail row | open; P funding now109,459 |
| A3 revise S63,719→81,258 | 413,472 | 162,586 | 250,886 | No new payment | R2; old payments/tender unchanged |
| A4 DP3 cash112,903 | 413,472 | 275,489 | 137,983 | 120,011 / 7,108 | open; remaining debt137,983 |
| A5 full cash137,983 | 413,472 | 413,472 | 0 | 150,007 / 12,024 | closed, one close event |

All checkpoints F=U=V=D=0; allocated money must equal P without new cash from revision. At A4 cumulative allocation priority: external53,127, P142,539, package product57,274, S22,549; remaining services owe58,709 +41,983 +37,291 =137,983. At A5 cash credited total324,015, transfer89,457, tender370,021, change46,006; retained cash equals324,015.

Attack inside the chain: replay A2 same key; changed amount or instrument under that key rejects without writes. Before A5 submit transfer137,984 and cash tender137,982 for intent137,983: reject atomically. Then valid A5. DP3 must be accepted; no “already paid once” guard. Capture payment IDs immediately and compare original row/cash-detail payloads after A3/A5.

UI at A3: current total413,472, Dibayar162,586, Sisa/Tagihan to settle250,886. A5 tender150,007 is below raw gross413,472 and must still succeed. Calculator may show selected amount only if explicitly labeled; note-wide Sisa must remain250,886 before A4 and137,983 before A5.

### Chain B — Refund / versioning / new receivable

Use the same initial mixed graph. No revision before initial refund, so funding-source amounts are deterministic. Three initial payments: cash73,129/tender100,003; transfer89,457; cash233,347/tender250,009/change16,662.

| Checkpoint/action | Current G | P | F | U / V / D | N | O |
|---|---:|---:|---:|---|---:|---:|
| B1 after DP1 | 395,933 | 73,129 | 0 | 0/0/0 | 73,129 | 322,804 |
| B2 after DP2 | 395,933 | 162,586 | 0 | 0/0/0 | 162,586 | 233,347 |
| B3 full settlement | 395,933 | 395,933 | 0 | 0/0/0 | 395,933 | 0 |
| B4 refund whole P product row | 253,394 | 395,933 | 142,539 | 0/0/0 | 253,394 | 0 |
| B5 admin R2: only active rows; S63,719→51,983 | 241,658 | 395,933 | 142,539 | 11,736/11,736/0 | 241,658 | 0 |
| B6 admin R3: S51,983→70,211; add R qty5 | 427,741 | 395,933 | 142,539 | 11,736/11,736/0 | 241,658 | 186,083 |
| B7 partial transfer27,119 | 427,741 | 423,052 | 142,539 | 11,736/11,736/0 | 268,777 | 158,964 |
| B8 full cash158,964 | 427,741 | 582,016 | 142,539 | 11,736/11,736/0 | 427,741 | 0 |

B8 tender170,003/change11,039. At B4, Gsnapshot remains the old immutable395,933; active collectible G is253,394. A raw allocation sum may still include refunded original allocations. Assert current eligible net, source refunds and immutable snapshot separately. From B5 onward rebuilt current A must equal min(N,G) at each checkpoint; historical refund must not be subtracted from that net A again.

B4 is **one logical refund of142,539** split across exactly three source customer_refunds: DP1→20,002; DP2→89,457; final→33,080, each anchored to the same old P component. Sum refund_component_allocations=142,539, not285,078. One stock return qty3 for that source line. Repeat same key replays; different key with old P target rejects. Same amount/date is never a grouping key.

B5 removes old P from submitted editable rows; preserved canceled P remains historical. It creates one surplus disposition11,736 and one linked surplus payment11,736. The immutable B5 settlement snapshot may remain overpaid_pending with pre-disposition surplus11,736; **current unresolved due is0**. Never assert the snapshot must be rewritten to paid.

B6 deliberately reorders current rows (E, K, S, new R). Capture fresh IDs; old IDs cannot be payment/refund targets. P refund history remains unchanged; R is genuinely payable. Keep all original payment amounts/method/tender/time immutable. Reopen root at B6, retain earlier close in timeline; B8 records a second close. If B5/B6 first fails allocation conservation, stop and classify that failure before interpreting downstream numbers.

Logical grouping acceptance: UI presents one action142,539 with three source allocations, or explicitly labels three rows as source allocations of that action. Current implementation lacks a dedicated refund operation FK; use captured idempotency result/refund_ids and audit evidence for proof. Do not invent a grouping key.

### Chain C — Cancel / correction / versioning

Use a smaller graph P qty2=95,026 + S63,719, total158,745, no external item. This chain proves distinct operations, with isolated conflict probes at the end.

| Checkpoint/action | Required supported result |
|---|---|
| C0 save actor's create workspace draft | Draft exists; no note/payment/movement/report event from saving scratch data. |
| C1 persist unpaid note with those two rows | G/O158,745; P/F0; P stock issue−2. Successful create cleans only this actor/create draft. Other actor's draft remains. |
| C2 unpaid revision removes P, keeps S | R2 G/O63,719; no customer refund; old issue reversed+2 with revision source. Old snapshot remains. |
| C3 pay cash63,719/tender70,003 | change6,284; O0; closed. No new stock movement at payment. |
| C4 admin revision adds NEW P qty1 | R3 G111,232; P63,719; O47,513; reopen; fresh component/stock issue−1. Do not reuse deleted P ID. |
| C5 settle transfer47,513 | P111,232; O0; second close; no cash detail for transfer. |
| C6 refund current P qty1 | F47,513; active G63,719; N63,719; O0; stock return+1; whole P row canceled via refund event. |

At C6 repeat refund and attempt pre-R3 stale ID; neither creates money, movement, or report delta. Reset is NOT A DOMAIN ACTION. Do not add a whole-note cancel endpoint to this chain.

Two bounded diagnostic probes use an isolated copy of a **pre-probe fixture**, never dirty and then continue the accepted chain:

1. On C5 paid state, submit existing corrections/status with current P line_no, target canceled, reason “QA paid cancel boundary”. Current request/handler appears to accept raw cancellation without refund/stock reversal. Contract forbids that shortcut. Assert no safe success lacking corresponding domain effects; capture actual result as X03 SOURCE CONFLICT. Do not guess a refund amount to compensate.
2. On C3 service-only paid state, submit corrections/service-only63,719→61,987, reason “QA nominal correction boundary”. Current source can update root total and report `refund_required=1,732` while old revision remains63,719 and no due/paid event is written. Lock this as characterization of X04; the contract acceptance target is coherent version/settlement/audit, not an invented automatic refund on this legacy route. No later chain calculation may assume that missing obligation was executed.

C diagnostic cases remain BLOCKED for normative GREEN until source/contract classification is accepted. Supported C0–C6 is executable separately. Exact HTTP requests for probes must use existing route permissions; authorization failure is evidence, not a reason to bypass middleware.

### Chain D — Inventory identity torture

Use common mixed fixture. Opening quantities P17/Q23/R19. All initial and revised package service remains41,983; external53,127 and external service37,291 remain unchanged.

| Checkpoint/action | G / P / F / O | On hand P / Q / R |
|---|---|---|
| D1 create + DP cash73,129/tender100,003 | 395,933 /73,129 /0 /322,804 | 14 /21 /19 |
| D2 revise P3→2, Q2→3 | 377,057 /73,129 /0 /303,928 | 15 /20 /19 |
| D3 settle transfer303,928 | 377,057 /377,057 /0 /0 | 15 /20 /19 |
| D4 refund current P qty2 | 282,031 /377,057 /95,026 /0 | 17 /20 /19 |
| D5 revision: re-add SAME product P qty1; replace package Q3 with R2 | 310,775 /377,057 /95,026 /28,744 | 16 /23 /17 |
| D6 partial cash11,009/tender20,003 | 310,775 /388,066 /95,026 /17,735 | 16 /23 /17 |
| D7 settle cash17,735/tender20,009 | 310,775 /405,801 /95,026 /0 | 16 /23 /17 |

D6 change8,994; D7 change2,274. All surplus fields0. Capture actual operational line IDs at each revision and bind symbols below to them.

| Product | Exact movement sequence excluding opening receipts | Cardinality / ending cost |
|---|---|---|
| P | issue(P1)−3; revision return(P1)+3; issue(P2)−2; refund return(P2)+2; issue(P3)−1 | Five rows, three issues, one revision return, one refund return. P3 ID differs from P1/P2. Net−1; COGS19,721; stock value315,536. |
| Q | issue(Q1)−2; revision return(Q1)+2; issue(Q2)−3; revision return(Q2)+3 | Four rows; net0; COGS0; stock value264,569. |
| R | issue(R3)−2 | One row; net−2; COGS27,418; stock value233,053. |
| External/service | No movements | Zero rows, including after revision/refund/payment. |

Total note-related movements: **six stock_out + three revision returns + one refund return =10**. Issue sources are P1/P2/P3/Q1/Q2/R3; revision-return sources are P1/Q1/Q2; refund-return source is P2. Use this explicit source set, not a guessed aggregate. Original issue rows must remain byte-for-byte unchanged.

At D5 old refunded P2 must not get `transaction_workspace_updated` reversal as well. Test fresh-key stale refund P2 and same-key D5 replay; all ten movement identities remain unchanged. Before D5 success, attempt replacement R qty20 against stock19: whole mutation rolls back (revision pointer, allocations, inventory/cost, payment and audit effects). Then submit valid qty2 with a new accepted command key.

Cost oracle uses fixed opening average with no intervening receipts: final net COGS47,139. Each reversal uses original unit cost, not sale price. A later focused master-change probe can change product name/sale price without altering historical snapshot; do not expand into procurement revaluation tests.

### Chain E — Reporting follows lifecycle facts

Run B's recipe from a fresh known-zero transaction-report base. E owns report assertions only; it does not repeat B's financial formula unit tests. Freeze no unrelated expenses/payroll/debt in the fixture. Event date and note transaction date coincide for this primary chain; see period probe below.

| Checkpoint | Current gross G | Accepted P | Ordinary F | Surplus paid V | Remaining due D | Net money P−F−V | O | Store COGS | External cost | Cash operational profit |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| E1=B1 | 395,933 | 73,129 | 0 | 0 | 0 | 73,129 | 322,804 | 82,169 | 53,127 | −62,167 |
| E2=B2 | 395,933 | 162,586 | 0 | 0 | 0 | 162,586 | 233,347 | 82,169 | 53,127 | 27,290 |
| E3=B3 | 395,933 | 395,933 | 0 | 0 | 0 | 395,933 | 0 | 82,169 | 53,127 | 260,637 |
| E4=B4 | 253,394 | 395,933 | 142,539 | 0 | 0 | 253,394 | 0 | 23,006 | 53,127 | 177,261 |
| E5=B5 | 241,658 | 395,933 | 142,539 | 11,736 | 0 | 241,658 | 0 | 23,006 | 53,127 | 165,525 |
| E6=B6 | 427,741 | 395,933 | 142,539 | 11,736 | 0 | 241,658 | 186,083 | 91,551 | 53,127 | 96,980 |
| E7=B7 | 427,741 | 423,052 | 142,539 | 11,736 | 0 | 268,777 | 158,964 | 91,551 | 53,127 | 124,099 |
| E8=B8 | 427,741 | 582,016 | 142,539 | 11,736 | 0 | 427,741 | 0 | 91,551 | 53,127 | 283,063 |

Current `gross_transaction_rupiah` should match the current whole-row active total for this fixture, not sum all revisions. Do not call it actual cash revenue. At E8 current charge decomposition is store225,129 + service149,485 + external53,127 =427,741. External53,127 is not service margin. Current cash-profit is not accrual profit; negative E1 is expected because stock/cost exists before full collection in this source model.

E8 cash ledger: five payment events, cash-in465,440, transfer-in116,576, ordinary outflow142,539 across three source rows, surplus outflow11,736 across one separate row. Total out154,275. Cash tender520,015; change54,575. Cash-detail columns must not inflate accepted P582,016.

At E4 canceled P is still visible in historical/audit evidence, not an additional active sale. Draft-save/cleanup and blocked actions have zero report delta. E5 revision correction affects current charge and movement-based COGS without creating payment cash-in. E6 new receivable changes O without creating cash. E8 payment changes cash/O, not stock.

Read each checkpoint twice and compare relevant DB hashes/counts before/after report calls: no writes to notes, revisions, payment/refund/allocation, movements, or audit as a “repair”. Execute page dataset, PDF and Excel against the same filters; compare numeric source dataset fields, not locale string parsing alone. Package breakdown, debt queue, and stock report must reconcile their explicitly labeled scope.

Period probe (one focused read-model test, not a second long chain): place B8 payment on 2026-09-16 while note date remains2026-09-15. Event cash ledger Sep15 excludes158,964, Sep16 includes it. Current-note transaction report filtered by note date Sep15 may show the latest settled state, because S21 is current truth, not an as-of ledger. Label this distinction. Audit occurred time cannot be replaced by midnight effective date.

Pending-due proof uses the existing explicit due/paid use cases (T10), outside automatic B5: due11,736, paid0 → liability11,736/no surplus cash-out; then paid4,013 → remaining7,723/cash-out4,013; final7,723 → remaining0/cash-out11,736. N stays reduced by11,736 throughout. This is a small primitive test, not permission to pause the automatic cashier default or invent a wallet.

## 7. UI/read-model assertions shared by every checkpoint

| Surface / field | Required meaning | Probe |
|---|---|---|
| Detail current total | G or explicitly labeled raw/snapshot total | B4 must distinguish253,394 current collectible from395,933 original revision. |
| Tagihan / Sisa | Authoritative current O for note-wide payment; selected O must say selected | A3 250,886; B6 186,083; D5 28,744 everywhere using note scope. |
| Dibayar | Current allocated/net settlement, named separately from historical P | B6 current241,658 versus historical395,933; show refund/surplus explanation. |
| Kembalian | This payment tender−credited | A1 26,874, A5 12,024, B8 11,039; not cumulative refund. |
| Note badge | Financial projection and persisted lifecycle explicitly distinguishable | B6 underpaid/open with reopen time; B8 Lunas/closed with second close; zero/refunded not blindly “paid”. |
| Work/component status | Raw work progress distinct from financial settlement/refund shadow | Four financially settled rows cannot imply four raw done rows. Keep separately named counts; use captured IDs. |
| Bayar/Lunasi | Available iff eligible current outstanding and authorized | Visible B6/B7, absent B8. Old refunded component never selectable. |
| Refund | Based on current type, eligibility and remaining capacity | Product current closed target allowed; already-refunded/stale rejected; service/external restrictions acknowledged as conflict, no false success. |
| Edit | Same capability/date/state as backend | Cashier closed edit denied; authorized admin revision allowed. Do not derive permission only from date. |
| Revision history | Immutable graph/header/decomposition, accurate membership | B6 reorder must not transfer identity; display names from snapshot, not generic position as purported lineage. |
| Payment history | Original amount/method/tender/change/event-time balance | B8 still shows first three payments exactly; distinguish current allocation from event accepted amount. |
| Refund history | One logical action with source detail or clear allocation labeling | B4 total142,539 once, sources20,002/89,457/33,080; surplus11,736 separately labeled. |
| Stock shown | Latest product_inventory, preview can become stale | D5 P16/Q23/R17 after reload; server rejects insufficient R20 regardless of cached lookup. |
| Suggestions/calculator | Convenience estimate, never authoritative mutation amount | A3 product preset cannot masquerade as note debt; B6 “bayar penuh” must use186,083, not427,741 or raw P subtraction. |
| Report values | Same named dataset/filter as section6 E | Current gross, accepted cash, refunds, due and COGS have distinct labels. |

A UI test should assert contract values and action semantics from rendered payload/DOM. It should not copy arithmetic into Blade/JS tests or force backend state changes just to align badges. On mismatch capture backend result, page payload and screen before another mutation.

## 8. Manual QA Battle Plan

Manual status: **READY for supported chains and diagnostic capture; normative acceptance BLOCKED at declared contract gaps**. Never force a failed dependent checkpoint forward using arbitrary overpayment, SQL repair, or deleting history.

Shared setup: disposable local/staging QA dataset; dedicated customer labels QA-A/B/C/D/E; product P/Q/R and costs/qty from section6; cashier account plus authorized admin account. Use today's permitted business date for interactive cashier QA and record actual times/timezone. Do not mutate the preserved 0064/0065 notes. Seed/reset the disposable fixture through the established environment setup, not an invented cashier “reset” action.

### Forensic read bundles

All reads are scoped to the captured note ID and source IDs; retain raw timestamps and integer amounts. Do not query accepted payments only through legacy payment_allocations.

- **F-MONEY:** notes current pointer/total/state; payment IDs from UNION(payment_allocations, payment_component_allocations, customer_refunds) plus independently captured payment IDs; customer_payments and customer_payment_cash_details; both allocation tables; customer_refunds and refund_component_allocations. Sum each ledger once, preserve per-payment mapping. Captured IDs are essential if linkage disappears.
- **F-VERSION:** note_revisions ordered revision_number; note_revision_lines payload/work_item_root_id/line_no; current work_items and child tables; note_revision_settlements; dispositions and surplus payments with status, amount, effective_date, occurred_at, created_at. Check pointer belongs to root and parent chain, not just MAX(number).
- **F-STOCK:** inventory_movements for every captured stock line source, including deleted historical line IDs from revision payloads; fields id/product_id/movement_type/source_type/source_id/reversal_source_id/qty_delta/unit_cost_rupiah/total_cost_rupiah/tanggal_mutasi/created_at; product_inventory and costing. Verify sources and counts before net quantity.
- **F-AUDIT:** note_mutation_events/snapshots; audit_logs event/context; audit_outbox and materialized audit_events/snapshots when present; idempotency_records key/hash/result/refund_ids. Distinguish missing durable capture from delayed materialization.
- **F-READ:** record raw detail/history/report dataset and filter parameters with checkpoint name; compare to F-MONEY/F-VERSION/F-STOCK. Reading must not change captured rows.

These are read instructions, not a DB schema guess or permission for writes. If a field/table differs at execution time, inspect its current migration and mark GAP rather than issuing an approximate destructive query.

### A manual attack card

| SETUP | ACTION | EXPECTED PRIMITIVE STATE | EXPECTED UI | DB/FORENSIC CHECK IF UI FAILS | WHAT FAILURE WOULD MEAN |
|---|---|---|---|---|---|
| QA-A common mixed fixture | Create Detail cash DP73,129 with tender100,003; reload | P73,129/O322,804/change26,874 | Dibayar73,129, Sisa322,804; history keeps tender | F-MONEY cash row, allocation sum | Tender overwrote intent or stale rendering |
| A1 | Pay transfer89,457; double-click/retry same submitted request | Exactly one new89,457 event; O233,347 | One additional business payment, no cash fields | F-MONEY + F-AUDIT key/result | Duplicate event or cash/transfer confusion |
| A2 | Edit service to81,258; save; Back then Forward and reload | G413,472/O250,886; old payments unchanged | Current total413,472, payable250,886; R1/R2 history | F-VERSION/F-READ | Raw total substituted for payable or stale navigation state |
| A3 | DP3 cash112,903/tender120,011; then full137,983/tender150,007 | O137,983 then0; change7,108 then12,024 | Full amount below gross accepted, buttons disappear after close | F-MONEY/F-AUDIT | Multi-DP restriction, validator regression, or missed close |

On a handset, reopen the payment modal after closing it once, toggle Simple/Detail and cash/transfer, and verify the intended amount survives without treating tender as payment. Capture the submitted payload when a suggestion changes.

### B manual attack card

| SETUP | ACTION | EXPECTED PRIMITIVE STATE | EXPECTED UI | DB/FORENSIC CHECK IF UI FAILS | WHAT FAILURE WOULD MEAN |
|---|---|---|---|---|---|
| QA-B common graph | Three payments73,129 /89,457 /233,347 with tenders from B | P395,933/O0; closed | Three payment events, final is settlement | F-MONEY/F-AUDIT | Payment/event chronology mismatch |
| B3 | Select only product P; refund reason “QA B split refund”; submit once then replay | One logical142,539 refund, three source rows; stock+3 once | Product historical; sources grouped/labeled; current253,394 | F-MONEY source split + F-STOCK + F-AUDIT refund_ids | Grouping defect versus actual duplicate refund must be distinguished |
| B4 as admin | Edit active service to51,983, excluding old P | Current241,658; surplus due/paid11,736; no outstanding | Separate surplus refund section; no ordinary duplicate | F-VERSION/F-MONEY | Double refund subtraction, lost paid link, or conflated ledgers |
| B5 as admin | Reorder E/K/S, set S70,211, add R qty5 at33,571 | G427,741/N241,658/O186,083; root reopened | Old P stays historical, new R payable; correct Tagihan | F-VERSION/F-MONEY/F-AUDIT | Stale identity rights, lost allocation, or surplus reclaimed |
| B6 | Partial transfer27,119, reload, full cash158,964/tender170,003 | O158,964 then0; second close; total P582,016 | Change11,039; original payment/refund history unchanged | All bundles | Premature close, new debt rejected, or history rewritten |

Use two tabs at B5: tab1 saves B6; tab2 submits its old draft with a distinct key. Record whether backend rejects stale version. If it silently overwrites B6, stop this fixture and label V04; do not keep paying on the overwritten note. Run this tab attack in a separate copy so it cannot invalidate B7/B8 evidence.

### C manual attack card

| SETUP | ACTION | EXPECTED PRIMITIVE STATE | EXPECTED UI | DB/FORENSIC CHECK IF UI FAILS | WHAT FAILURE WOULD MEAN |
|---|---|---|---|---|---|
| QA-C P qty2 + S63,719 | Save draft, leave/reopen editor, then create unpaid158,745 | Draft only before create; after create note exists and P−2 | Draft restores; saved note has debt158,745 | Draft row, F-MONEY/F-STOCK | Scratch data confused with sale |
| C1 | Remove P through edit/revision, keep S | G/O63,719, no cash refund, stock+2 revision return | Revision history shows removal, not paid refund | F-VERSION/F-STOCK | Unpaid cancellation invented money or lost history |
| C2 | Pay63,719/tender70,003; admin add P qty1; pay transfer47,513 | O0→47,513→0; fresh P source; second close | Edit capability correct; payable delta47,513 | F-MONEY/F-AUDIT | State and revision/payment mismatch |
| C5 | Refund current P47,513 | Active63,719; stock returns; old P historical | Refund differs from draft delete and revision | F-STOCK/F-MONEY | Cancel/refund conflation |
| Separate C3/C5 diagnostic copies | Use available correction UI, or reproduce existing correction endpoint in test harness; do not fabricate a Reset button | Expected conflicts described in C probes | No claim missing due/stock effects are correct | F-VERSION/F-AUDIT + request/response | Legacy correction bypass; stop at GAP |

### D manual attack card

| SETUP | ACTION | EXPECTED PRIMITIVE STATE | EXPECTED UI | DB/FORENSIC CHECK IF UI FAILS | WHAT FAILURE WOULD MEAN |
|---|---|---|---|---|---|
| QA-D P17/Q23/R19 | Create common mixed graph with DP73,129 | P14/Q21/R19; two initial note stock issues | Product availability updates after refresh | F-STOCK | Stock issued at wrong lifecycle point |
| D1 | Change P qty3→2 and package Q2→3 | P15/Q20/R19; old sources reverse, new sources issue | Package decomposition and stock reflect qty change | F-STOCK/F-VERSION | Net saldo may hide duplicate moves |
| D2 | Settle303,928 transfer; refund P qty2 | P17/Q20/R19, refund95,026 | Refund P unavailable afterward | F-MONEY/F-STOCK | Refund/source balance or return cardinality defect |
| D4 | Re-add same P qty1, replace Q package with R qty2 | P16/Q23/R17; new P identity | Same product name is a new current row; old refund retained | F-STOCK exact ten-row set | Old P resurrected or double reversal |
| D5 | Pay11,009/tender20,003 then17,735/tender20,009 | Stock unchanged; debt17,735 then0 | Changes8,994/2,274; balances persist on reload | F-MONEY/F-STOCK | Payment illegally issuing stock or stale availability |

Separate failure probe: on D4 copy request R qty20. Server must reject without changing any previous movements or revision pointer. Back/Forward must not show the failed new stock as committed.

### E manual attack card

| SETUP | ACTION | EXPECTED PRIMITIVE STATE | EXPECTED UI | DB/FORENSIC CHECK IF UI FAILS | WHAT FAILURE WOULD MEAN |
|---|---|---|---|---|---|
| QA-E zero report base | Execute B and open reports after each E checkpoint | Exact E table | Same numbers in page/PDF/Excel for same mode/filter | F-READ compared to money/stock | Report formula/source divergence |
| E4/E5 | Inspect refund, canceled history and surplus sections | Ordinary142,539, surplus11,736; not a merged unexplained refund | One logical component action plus distinct surplus | F-MONEY/F-AUDIT | Grouping or source-category confusion |
| E6 | Refresh debt report before new payment | O186,083; cash remains241,658 net | New debt visible; no fake new cash-in | F-READ/F-MONEY | Report reading stale settlement snapshot |
| E8 | Export and reload twice | G427,741, net427,741, COGS91,551, external53,127, profit283,063 | Desktop/mobile totals readable and matching exports | Hash source rows before/after reads | Report repair/write or export divergence |

Manual visual checklist applied to these exact checkpoints: modal focus/scroll and validation message placement; disabled/enabled actions; no two unlabeled conflicting “Selesai” counts; mobile amount wrapping; click/retry behavior; refresh persistence; Back/Forward stale state; history source grouping; suggested amount semantics; cashier→admin handoff; report period labeling. HTML-only automation does not establish these interactions.

## 9. Exact proposed test ownership and ordered slices

Future files below are **proposals, not files created in this step**. Existing tests remain; do not rewrite their expectations to normalize conflicts. A file may contain the one chain plus a tightly related guarded failure probe, not dozens of combinatorial cases.

| Slice | Exact proposed ownership/file | Level / proof and stop gate |
|---|---|---|
| 1 | `tests/Feature/Note/PrimitiveSettlementSourceParityFeatureTest.php` | APPLICATION/READ-MODEL. One fixture with ordinary refund, first net rebuild, second revision, surplus-paid and next partial payment. Compare S05/S06/S07/S10 named bases. First RED classified; no UI/production patch in characterization slice. This is the next safe active step. |
| 2a | `tests/Feature/Note/PrimitiveMutationBoundaryCharacterizationTest.php` | FEATURE/HTTP. Only C's paid-status cancel probe. Classify X03 and stop for feedback. |
| 2b | Same proposed file as2a | FEATURE/HTTP. Only nominal service correction probe; classify X04 and stop. No normalization of missing refund_due into accepted behavior. |
| 2c | `tests/Feature/Note/PrimitiveFullyRefundedNewReceivableFeatureTest.php` | DOMAIN/FEATURE. Product-only P qty1=47,513, full payment47,513 then full refund47,513; authorized new service63,719 creates O63,719, followed by cash intent63,719/tender70,003/change6,284. Old rows remain shadow. Characterize O04 independently from closed-root T05; no invented transition implementation. |
| 3 | Extend `tests/Unit/Application/Note/Services/BuildNoteRevisionSettlementTest.php` only where owner formula lacks proof; `tests/Feature/Note/PrimitivePaymentDebtCashChainFeatureTest.php` | UNIT owns formula; A FEATURE owns real multi-DP/revision/HTTP integration. Reuse T02 for cash formula/replay; do not duplicate all its inputs. |
| 4 | `tests/Feature/Note/PrimitiveRefundRevisionReceivableChainFeatureTest.php` | FEATURE/DOMAIN integration. B source split, immutable event capture, two post-refund revisions, new debt and close. Reuse T05 and keep 0065 closure intact. |
| 5 | `tests/Feature/Note/PrimitiveCancelCorrectionVersionChainFeatureTest.php` | FEATURE/HTTP. Supported C0–C6; diagnostic contracts resolved in slice2. No invented reset or whole-note cancel. |
| 6 | `tests/Feature/Inventory/PrimitiveInventoryRevisionRefundChainFeatureTest.php` | APPLICATION/INTEGRATION. D exact ten movement rows, source-cost compensation, insufficient-stock rollback. No assertions solely on final qty. |
| 7 | `tests/Feature/Note/PrimitiveRevisionIdentityContractFeatureTest.php` | DOMAIN/FEATURE. Stale-base edit versus current row IDs; same-key replay versus changed key and different target root; one reorder/remove/re-add snapshot proof and master-label stability. Execute one identity probe per session. A missing request base identity is a GAP until explicit implementation slice, not a test fixture workaround. |
| 8 | `tests/Feature/Note/PrimitiveLifecyclePresentationContractFeatureTest.php`; browser runner `scripts/test-primitive-lifecycle-presentation.mjs` if existing browser harness is reused | READ-MODEL/UI. Consume checkpoint outputs; Tagihan/Dibayar/Sisa/buttons/history and Simple/Detail equality. Browser owns real modal/mobile/navigation behavior; HTTP owns payload and rendered semantic hooks. Do not repeat arithmetic owner tests. |
| 9 | `tests/Feature/Reporting/PrimitiveLifecycleReportingChainFeatureTest.php` | REPORTING. E current/event-mode dataset, cash categories, COGS/external, canceled history, read-only source hashes, one cross-date probe. Existing exports T19 own format; add only dataset parity at important checkpoints. |
| 10 | `tests/Feature/Note/PrimitiveLifecycleAuditAtomicityFeatureTest.php` | APPLICATION/FEATURE. Bound audit port failure, durable outbox and semantic IDs for payment/revision/refund/surplus. Inject one failure seam per session. Legacy audit rollback alone does not close A01. |
| 11 | Extend T15 only where missing | INTEGRATION with two real DB connections/barriers. Same-note payment/refund/revision serialize; generated inventory refund uniqueness versus revision lock. Reuse chain checkpoints, not Cartesian actor/payment combinations. |
| 12 | This blueprint's manual battle cards | Manual browser/device proof after targeted suites; final scoped regressions then `make verify` once. Record command, outcome, first failing checkpoint and remaining GAP; no completion claim from test count alone. |

Helpers, if needed: `tests/Support/BuildsPrimitiveLifecycleFixture.php` (opening data and explicit public actions), `tests/Support/CapturesPrimitiveLifecycleEvidence.php` (IDs, raw snapshots, named checkpoints). They must preserve meaning: no direct lifecycle-state seeding after create, no hidden expected-value calculator, no transport bypass to bypass authorization. Share B's action recipe with E, not its arithmetic assertions.

### Per-slice completion rule

1. Read current source and relevant contract; select exactly one slice/probe.
2. Establish nearest existing focused baseline, then add the smallest meaningful assertion.
3. Classify first failure: TEST WRONG, PRODUCTION BUG, or CONTRACT GAP. This blueprint's SOURCE CONFLICT requires that classification before any patch.
4. Only a separately authorized implementation slice may change production. Preserve failure difficulty and historical closed regressions.
5. Prove targeted behavior, relevant adjacent tests, and exact source/effect cardinality. Run broad gate after completed scoped work, not after every tiny assertion.
6. Stop for owner feedback before next slice. Do not use an unresolved earlier checkpoint to fabricate downstream successful evidence.

## 10. Closeout / continuation contract

STATUS: blueprint produced for review; production lifecycle is not declared universally proven.

PRIMITIVES LOCKED: integer money; immutable credited payment/cash detail; ordinary refund ledger meaning; narrow root transitions and close history; stock issue/refund compensation; external purchase is not inventory. See precise matrix scope and evidence limitations.

PRIMITIVES WEAK: repeated composition, allocation replay, refund operation grouping, complete version snapshots, revision inventory cardinality, due/paid consumer parity, all mutation idempotency/concurrency.

PRIMITIVES MISSING/AMBIGUOUS: approved cashier unallocated-wallet contract (legacy record/allocate exists), stale-edit base identity, whole-note cancel/delete, domain reset (absent), independent stock-return choice, complete external lifecycle, zero/refunded root semantics. Source conflicts remain for legacy corrections and canonical durable audit.

DUPLICATED SOURCES OF TRUTH: D01–D23, with exact safe/read-model/dangerous classification. The aim is to converge later on explicit ownership, not to deduplicate every display calculation blindly.

BRUTAL CHAINS: A payment/debt/cash; B split refund/revision/surplus/new receivable; C real action distinctions; D ten exact inventory movements; E current/event reporting. All designs are unexecuted.

MANUAL QA PLAN: READY for supported paths and exact diagnostic capture; GAP-dependent acceptance remains BLOCKED.

FILES CREATED/CHANGED: only this blueprint. NO PRODUCTION PATCH: YES.

NEXT SAFE ACTIVE STEP: slice 1 source-parity characterization, beginning with the second post-refund revision and committed surplus basis; do not start residual UI cleanup.

PROGRESS: documentation deliverable complete after structural/path/arithmetic verification; implementation of proposed chains 0%. No fresh application-test pass claimed.

Verification performed locally with read-only Python assertions: PASS for 48 matrix rows with 11 required columns; status counts: 10 LOCKED, 17 IMPLEMENTED BUT WEAKLY TESTED, 8 DUPLICATED TRUTH, 3 CONTRACT GAP, 6 SOURCE CONFLICT, 4 MISSING PRIMITIVE. Referenced full source/test paths and relative Markdown links resolve; nonexistent future test paths are explicitly proposed. Independent A/B/D calculations, every E ledger/profit checkpoint, and D's ten movement rows reconcile. This is documentation proof only. Only this blueprint was written by the assistant; no claim is made about unrelated pre-existing working-tree changes.

SESSION CONTEXT HEALTH: broad audit contains explicit anchors/checkpoints for a fresh implementation session. Continue one slice per later session; refresh source at execution time. This document is the continuation artifact, not a replacement for command/test proof.
