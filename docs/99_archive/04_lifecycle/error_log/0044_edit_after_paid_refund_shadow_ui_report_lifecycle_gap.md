# 0044 Edit After Paid Refund Shadow UI Report Lifecycle Gap

## Status

Patched with automated proof and residual manual/audit gaps.

Current verification:

- `make verify` PASS on 2026-06-25.
- Full Pest summary: `1416 passed, 8405 assertions`.
- Relevant automated coverage now includes:
  - `tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php`
  - `tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php`
  - `tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php`
  - `tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php`
  - `tests/Feature/Note/RefundAfterRevisionCurrentRowBoundaryFeatureTest.php`
  - `tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php`
  - `tests/Feature/Note/ClosedPaidNoteEditPaymentSettlementPreviewFeatureTest.php`
  - `tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php`
  - `tests/Feature/Note/EditTransactionWorkspacePackageAutoSplitCharacterizationTest.php`
  - `tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php`

Do not mark this issue fully fixed until the residual manual/browser and audit
gaps below are either proven or explicitly accepted as deferred.

## Scope

Manual/domain review menemukan lifecycle edit/refund/payment/report yang harus dikunci sebelum patch UI atau report besar.

Target policy:

- Nota hutang dan lunas tetap boleh diedit lewat revision path yang resmi.
- Refund tidak boleh diperlakukan sebagai line biasa yang ikut tertimpa edit.
- Refund harus tetap menjadi ledger/shadow historical truth.
- Edit setelah refund tidak boleh menghapus, reset, atau menggandakan efek refund, stok, payment, allocation, cash ledger, atau projection.
- Jika nota sudah lunas, sudah ada refund, lalu diedit turun atau seluruh line aktif dihapus, uang lebih harus menjadi status eksplisit:
  - overpaid_pending
  - refund_due
  - refund_paid
  - atau future customer credit setelah customer identity contract stabil
- UI harus menjelaskan status uang, status refund, status stok, status transaksi, dan action yang tersedia/tidak tersedia.
- Browser refresh, Ctrl+R, dan Ctrl+Shift+R tidak boleh membuat UI menampilkan state/action yang bertentangan dengan backend.
- Laporan screen, PDF, dan Excel harus membaca source resmi yang sama dan menampilkan efek lifecycle secara presisi.

## FACT

Existing docs sudah mencatat sebagian arah:

- Edit/refund target architecture adalah Ledger + Revision Snapshot + Current Projection.
- Payment/refund adalah financial ledger events.
- Inventory movements adalah stock ledger events.
- UI/API adalah transport adapters.
- Surplus/refund_due/refund_paid harus eksplisit dan tidak boleh hilang diam-diam.
- Full browser UI masih menjadi manual/browser gap.
- Report/export after edit/refund/revision has automated proof through focused
  tests and full `make verify`.
- Edit/revision package auto split has automated proof through focused tests and
  full `make verify`.

## GAP

Residual gaps after current automated proof:

1. Real browser/manual QA is not closed.
2. Browser refresh and hard-refresh behavior are not proven by a real browser runner.
3. Console errors, responsive visual behavior, modal focus, and real double-click
   timing remain manual/browser-only checks unless Dusk/Playwright or equivalent
   is introduced.
4. Broader audit lifecycle redesign remains transitional.

## DECISION

Automated backend/render/report coverage is now the accepted proof for the
non-browser parts of this issue.

Do not patch reports to hide lifecycle state. Reports must keep reading official
domain records.

Do not start broader audit redesign under this issue without a new active scope.

## NEXT SAFE STEP

Either:

1. close or defer the remaining browser/manual QA gap by owner decision; or
2. introduce a real browser runner and prove refresh/hard-refresh behavior.

## Workflow Control

Canonical workflow:

- docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md

Active handoff:

- docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md

Rule:

- Every implementation session must update the active handoff checklist.
- Do not mark this error log fixed until the workflow DoD is satisfied.
- Do not execute direct GitHub connector write actions for this workflow unless owner explicitly overrides in the same session.
