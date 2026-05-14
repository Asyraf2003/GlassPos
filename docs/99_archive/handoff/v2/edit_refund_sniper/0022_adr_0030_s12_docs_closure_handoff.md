# Handoff 0022 - ADR 0030 S12 Docs Closure

## Scope

HyperPOS ADR 0030 refund/edit/payment logic.

This handoff closes the S12 docs-continuity slice after the S12 source/test patch and ADR 0030 implementation verification update were locally verified.

## Current Proven State

S12 is closed.

S12 source/test patch was locally verified with these owner-provided proof lines:

- Targeted feature: 1 passed / 3 assertions.
- Settlement builder unit: 4 passed / 22 assertions.
- Focused blast-radius: 21 passed / 102 assertions.
- Full make verify: 1021 passed / 5485 assertions in 49.50s.

ADR 0030 Implementation Verification was updated for S12 and scoped validation passed.

Relevant ADR block:

- S12 block lines 411-462.
- Status: Fixed and locally verified.
- Full make verify proof present.
- carry_forward_refunded_rupiah present.
- surplus_refund_paid_rupiah and remaining_refund_due_rupiah caveat present.
- No customer_refunds for surplus refund_paid.
- No refund_component_allocations for surplus refund_paid.
- No refunded lifecycle for surplus refund_paid.
- No inventory reversal for surplus refund_paid.

## S12 Production / Source Patch

Files:

- app/Ports/Out/Note/NoteRevisionSurplusRefundPaymentReaderPort.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundPaymentAdapter.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundPaymentSumQuery.php
- app/Application/Note/Services/BuildCreateNoteRevisionSettlement.php

Decision:

Add sumActiveAmountByNoteRootId(string $noteRootId): int to NoteRevisionSurplusRefundPaymentReaderPort.

DatabaseNoteRevisionSurplusRefundPaymentAdapter delegates sum queries to extracted DatabaseNoteRevisionSurplusRefundPaymentSumQuery.

BuildCreateNoteRevisionSettlement adds active surplus refund_paid by note root into carry-forward refunded.

Formula:

carryForwardRefunded = ordinary/component refunded + activeSurplusRefundPaidByNoteRootId

## S12 Tests

Files:

- tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php
- tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php

Proof:

- Targeted feature: 1 passed / 3 assertions.
- Settlement builder unit: 4 passed / 22 assertions.
- Focused blast-radius: 21 passed / 102 assertions.
- Full make verify: 1021 passed / 5485 assertions in 49.50s.

## Semantic Caveat

This is a compatibility patch.

It folds surplus refund_paid into carry_forward_refunded_rupiah.

Cleaner future schema may need explicit fields:

- surplus_refund_paid_rupiah
- remaining_refund_due_rupiah

Do not open schema or migration in this slice.

## Locked Decisions Preserved

- Do not merge revision submit and payment.
- Do not implement customer_credit.
- Do not implement customer_balance_entries.
- Do not implement PostgreSQL.
- Do not implement Go API.
- Do not implement dashboard.
- Do not use customer_refunds for surplus refund_paid.
- Do not use refund_component_allocations for surplus refund_paid.
- Do not trigger refunded lifecycle for surplus refund_paid.
- Do not trigger inventory reversal for surplus refund_paid.
- Do not patch production before RED or source-gap proof.

## Verification Gaps

- Browser/manual cashier edit-payment QA has not been run unless separately proven.
- Runtime JavaScript behavior is protected by static JS contract and focused PHP render tests, not by a browser-executed test, unless separately proven.
- Future schema split for explicit surplus_refund_paid_rupiah / remaining_refund_due_rupiah remains out of scope.

## Next Safe Step

Find the next explicit ADR 0030 slice or verification gap after S12.

Stop if no explicit next slice or gap is found.

Do not patch production before RED or source-gap proof.
