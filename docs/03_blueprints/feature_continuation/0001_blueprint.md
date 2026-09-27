# Feature Continuation Blueprint

## Metadata

- Repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Branch baseline when the blueprint was created: `audit-1461-selective-patch`
- Baseline HEAD: `c0ce90a6`
- Source context:
  - Audit 1461 selective patch is closed.
  - Cash payment detail has been persisted.
  - Push notification infrastructure already exists for due-note / customer-note reminders.
  - Supplier payable report already exists.
  - Dashboard operational performance already exists.
  - PDF / printed notes / reports are not yet in active scope.
- UI label stash outside audit:
  - `stash@{0}: temp-ui-refund-label-outside-audit`

## Locked Workflow

Every case must be handled one by one.

Required order:
1. Snapshot the repo.
2. Inspect related files.
3. Lock FACT, GAP, DECISION.
4. Prepare a minimum blueprint.
5. Implement a small patch.
6. Run focused tests.
7. Run `make verify`.
8. Make a small commit.
9. Create a case handoff in `docs/99_archive/handoff/v2/feature_continuation/`.
10. Update the status ledger in this file.

Do not increase progress without command-output proof.

Do not mix different features into one commit unless a blocking refactor, such as `audit-lines`, requires it.

Do not pop or stage the UI refund-label stash unless there is an explicit decision about UI wording.

## Priority Rules

### P0

Problems that can cause direct financial risk, late payment, wrong reports, wrong domain lifecycle behavior, or broken critical operational features.

P0 must be finished before P1 unless a technical blocker makes P1 a dependency.

### P1

Important operational problems that improve cashier/admin accuracy, reduce delay, or clarify the dashboard, but do not directly change the main financial lifecycle.

### P2

Enhancements, convenience, print/export, UI polish, or features whose contract has not yet been discussed enough.

P2 must not interfere with P0/P1.

## Status Ledger

| ID | Priority | Case | Status | Last Proof | Handoff |
|---|---:|---|---|---|---|
| FC-000 | P0 | System ambiguity inventory after abandoned feature work | CLOSED | Repo snapshot mapped cash change, dashboard, supplier payable notification, PDF, and UI stash ambiguity | `docs/99_archive/handoff/v2/feature_continuation/01-system-ambiguity-inventory.md` |
| FC-001 | P0 | Supplier payable push notification H-5 until paid off | IMPLEMENTED / MERGE GATE OPEN | Dedicated reader, handler, payload and command exist; current-position/report/deep-link verification recorded 2026-09-27 | `docs/04_lifecycle/handoff/0037_supplier_payable_current_position.md` |
| FC-002 | P1 | Change-money potential on the monthly operational performance dashboard | OPEN | Snapshot found `change_rupiah`, but no related dashboard field/metric yet | Pending |
| FC-003 | P1 | Change-money denomination calculator | OPEN/PARTIAL | Cash change is persisted, but no denomination-calculator proof yet | Pending |
| FC-004 | P2 | PDF/printed notes/reports | OPEN | Snapshot only found supplier PDF attachment proof, not PDF generation for notes/reports | Pending |
| FC-005 | P2 | UI refund-label stash | DEFERRED | Stash still exists, outside the audit scope | Pending |

## FC-001 - Supplier Payable Push Notification H-5 Until Paid Off

### Priority

P0

### Problem

The system needs to send reminders or notifications if a supplier payable is approaching due date H-5, is already due, or remains unpaid until it is paid off.

### Verified implementation (2026-09-27)

- DatabaseSupplierPayableReminderReaderAdapter selects non-void invoices with positive outstanding and due date <= today + 5 days, without a shipment-month filter.
- Active payments exclude supplier_payment_reversals.
- GetSupplierPayableRemindersHandler, SendSupplierPayableReminderPushHandler and SupplierPayableReminderPushPayloadFactory exist.
- routes/console.php registers push-notifications:send-supplier-payable-reminders with --today, --invoice-limit and --subscription-limit.
- Payload deep link targets the named procurement supplier invoice index with outstanding / due_date / asc, without shipment-date bounds.
- Default supplier report is all periods; the dashboard uses current supplier balance independent of its selected month.
- Focused, regression and isolated-browser evidence is in the current handoff. The old claims that these components were absent are superseded by source inspection and executed tests.

### Operational gaps and merge gate

- Repeat invocation on the same date can resend. The daily notification tag does not provide server-side deduplication. Owner deferred scheduling/deduplication policy to production cron planning.
- Default limits remain 100 invoices and 500 subscriptions. Excess rows are omitted without a truncation warning; there is no automatic batch pagination.
- No production cron configuration was performed. Production PHP path, deployment path, environment and push delivery remain deployment-specific verification.
- Global Blade audit fails on the unchanged customer note status badge, also reproduced on origin/main. Customer work is outside this target; merge remains gated.

### References

- Blueprint: docs/03_blueprints/reporting/0005_supplier_payable_current_position.md
- Handoff: docs/04_lifecycle/handoff/0037_supplier_payable_current_position.md
- Issue: https://github.com/Asyraf2003/GlassPos/issues/30

## FC-002 - Change-Money Potential Dashboard Metric

### Priority

P1

### Problem

The admin dashboard section `Kinerja Operasional Bulan Ini` needs to be replaced or extended with a metric for change-money potential.

### Known Facts

- Cash payment detail is persisted in `customer_payment_cash_details`.
- Available fields:
  - `amount_paid_rupiah`
  - `amount_received_rupiah`
  - `change_rupiah`
- The operational performance dashboard already has a `Kinerja Operasional Bulan Ini` chart.
- There is no proof yet that the chart or dataset uses `change_rupiah`.

### Gaps

- The definition of "change-money potential" is not locked.
- It is not decided whether the metric is:
  - total `change_rupiah` for the month,
  - total cash received minus paid,
  - an estimated cash-drawer denomination breakdown,
  - or a minimum small-change recommendation.
- There is no dashboard test for this metric yet.

### Required Decision Before Patch

Choose one:
- Option A: show the monthly total `change_rupiah` as "Change Potential".
- Option B: show the total plus a denomination breakdown.
- Option C: the dashboard shows only the total, and the breakdown lives in a separate calculator.

### Suggested Default Decision

Option C.

Reason:
- The dashboard only needs to provide an indicator.
- Denominations are better suited to a dedicated calculator/helper.
- The dashboard UI stays less crowded.

### Closure Proof Required

- Dataset/read-model test.
- Dashboard page test.
- `make verify` passes.
- Commit hash.
- Handoff file path.

## FC-003 - Change-Money Denomination Calculator

### Priority

P1

### Problem

Cashier/admin users need a change-money denomination calculator so they can prepare small denominations practically.

### Known Facts

- The change amount is already calculated and persisted.
- There is no proof of a denomination-calculator implementation yet.

### Gaps

- The supported denominations are not decided yet.
- It is not decided whether the calculator is based on:
  - single-transaction change,
  - daily total,
  - monthly total,
  - or manual input.
- The UI location is not decided yet:
  - cashier payment modal,
  - admin dashboard,
  - or cash report page.

### Suggested Contract

Default denominations:
- 100000
- 50000
- 20000
- 10000
- 5000
- 2000
- 1000
- 500

Calculator harus deterministic:
- input integer rupiah
- output list pecahan dan count
- sisa tidak boleh negatif
- jika sisa tidak bisa dipecah oleh denom minimum, tampilkan remainder

### Suggested Implementation Plan

1. Buat pure service/value calculator kecil.
2. Unit test matrix.
3. Integrasi ke UI setelah pure logic locked.
4. Jika dipakai dashboard, ambil input dari aggregate `change_rupiah`.

### Closure Proof Required

- Unit tests denomination matrix.
- Feature/UI test jika di-render.
- `make verify` pass.
- Commit hash.
- Handoff file path.

## FC-004 - PDF/Cetak Nota/Laporan

### Priority

P2

### Problem

Ada kebutuhan cetak/PDF, tapi belum dibahas kontrak final.

### Known Facts

- Search menemukan PDF pada supplier payment proof attachment.
- Belum ada proof generate PDF nota/laporan.
- Belum ada keputusan library/rendering.

### Gaps

- Belum jelas PDF untuk:
  - nota pelanggan,
  - transaksi/kasus,
  - laporan profit,
  - supplier payable,
  - atau semua.
- Belum jelas output:
  - browser print,
  - generated PDF download,
  - stored PDF artifact,
  - atau template HTML printable.
- Belum jelas library:
  - dompdf/barryvdh,
  - browser print,
  - external renderer.

### Suggested Rule

Jangan mulai PDF sebelum P0 supplier payable notification dan P1 cash change dashboard/kalkulator jelas.

### Closure Proof Required

- Separate blueprint.
- Route/controller/view tests.
- Rendering smoke test.
- `make verify` pass.
- Commit hash.
- Handoff file path.

## FC-005 - UI Refund Label Stash

### Priority

P2

### Problem

Ada stash UI-only:
`Catat Refund / Batalkan Line` menjadi `Refund`.

### Known Facts

- Stash sengaja tidak dicampur ke audit 1461.
- Perubahan ini pernah menyebabkan test false negative karena expected label lama.

### Decision

Deferred.

Jangan pop sebelum ada keputusan UI wording dan update test terkait.

## Handoff Template

Setiap selesai satu kasus, buat file:

`docs/99_archive/handoff/v2/feature_continuation/YYYY-MM-DD-FC-XXX-short-name.md`

Template:

### Handoff FC-XXX - Title

## Metadata

- Branch:
- Start HEAD:
- End HEAD:
- Commit:
- Date:
- Scope:

## Final Decision

## Files Changed

## Tests / Proof

## What Was Closed

## What Was Not Closed

## Known Caveats

## Next Safe Step

## Opening Prompt For Next Session

Lanjutkan dari repo `/home/asyraf/Code/laravel/bengkel2/app`.

State terakhir:
- Branch:
- HEAD:
- Last commit:
- Pending:

Aturan:
- Zero assumption.
- Blueprint first.
- One active step.
- Jangan klaim progress tanpa command output.
- Jalankan snapshot dulu sebelum patch.
