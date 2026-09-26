# C4 external cancellation boundary

Issue #11, branch `fix/11-external-cancellation-boundary`, base main 41453418.
C1/C2/C3 and #8 are closed. Raw owner0029 is unchanged.

FACT: committed external data (even full debt) cannot pure-cancel. Current selected-refund rejects external rows; ADR0042's specialized service-only/pass-through decision is not implemented. ADR0046 explicitly allows preserving unsupported restrictions with truthful presentation. This slice does not implement supplier ownership, return, debt cancellation or zero-value money refunds.

Change: existing external guard now precedes generic payment/refund classification, still inside the existing locked cancellation workflow. The response explicitly says cancellation/refund of external purchase is unsupported and the transaction unchanged. Ordinary non-external paid refund and revision correction remain unchanged.

Proof: three intended RED failures after correcting the test's projection primary key; final affected suite 22 tests / 217 assertions passes (ExternalCancellationBoundary, WholeNoteCancellation, DetailRefundChoices, RefundReportingOwnerDecisionV2Characterization). Matrix covers unpaid external, paid external and paid mixed; repeated cancellation plus refund rejection preserves root, external rows, immutable revisions, payment/allocation/refund/inventory/history graph and report values. PHPStan no errors, Pint and contract audit pass, diff check clean. Author diff review found no write/locking/atomicity change; no independent approval claimed.

Support matrix for C5: unpaid non-external -> cancellation; recorded non-external payment -> existing eligible Detail refund; any current external consequence -> explicit unsupported cancellation boundary, no success/no silent partial cancellation. Eligible non-external rows on mixed roots may still use C3 selected refund independently. Correction remains revision when the transaction is real. Restoring historical external snapshots remains unsupported.

Next: dedicated PR/review/green merge, sync main, then C5 Detail cancellation/restore and explicit boundary presentation. Full verification remains C6.
