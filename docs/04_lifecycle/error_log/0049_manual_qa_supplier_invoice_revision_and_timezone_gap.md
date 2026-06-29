# 0049 - Manual QA Supplier Invoice Revision Reason and Timestamp Display Gap

## Status

Open.

## Source

Manual QA after closing targeted owner-facing Indonesian language cleanup and reason visibility slices from 0047/0048.

Owner performed manual checks after full verification passed.

## Summary

Automated verification passed for the targeted cleanup, but manual QA found new follow-up anomalies:

1. Product edit reason flow appears correct.
2. Supplier invoice edit does not behave like product edit:
   - edit succeeds/fails unexpectedly from manual perspective,
   - no visible revision/version is created,
   - latest reason does not appear,
   - owner expects supplier invoice edit to show version/reason like product edit.
3. Closed/paid note correction history manual checks 11-15 were reported failed or not working as expected and need reproduction.
4. Refund flow from item 16 onward can succeed, but displayed time is wrong:
   - owner local time during test: around 10:11 Asia/Makassar.
   - displayed time: around 02:07.
   - this suggests a UTC/local timezone presentation gap, likely 8 hours behind local time.

## Manual QA Result Reported By Owner

### Product Edit

Result:

- Test 1: OK, edit product reason exists and contains value.
- Test 2: failed validation as expected when reason is missing.
- Test 3: failed validation as expected when reason is only spaces.
- Tests 4-6: failed validation as expected for invalid/too short/too long/invalid retry cases.
- Test 7: OK, repeated product edit reason flow works.

Interpretation:

Product edit reason behavior appears correct and should not be the main target of this issue.

### Supplier Invoice Edit

Result:

- Test 8: failed.
- Editing supplier invoice does not show version/revision or reason.
- Owner expected supplier invoice revision/reason behavior to be similar to product edit.
- Test 9: failed because the first supplier invoice edit/revision flow already failed.
- Test 10: failed because supplier invoice without version does not show revision behavior like product.

Interpretation:

The prior source-level patch only made detail page capable of reading/displaying latest supplier invoice version reason if such version exists.

Manual QA suggests the supplier invoice edit/update flow may not actually be creating the supplier invoice version/revision record or may not be bumping `last_revision_no`.

Need inspect:

- supplier invoice edit form,
- update request,
- update controller,
- update handler/service,
- `supplier_invoice_versions` writer,
- `supplier_invoices.last_revision_no`,
- detail query/view behavior.

Important: do not assume display layer is the only issue. The likely gap may be update-side revision creation.

### Reason Display / XSS / Note History Checks

Result:

- Tests 11-15 reported failed.

From the manual checklist, these corresponded to:

- supplier invoice reason with HTML/XSS text,
- closed/paid note correction reason display,
- correction reason with HTML/XSS text,
- note detail without correction history,
- note detail with both revision timeline and correction history.

Interpretation:

Needs reproduction. Do not assume all failures share one root cause.

Potential branches:

1. Supplier invoice reason HTML cannot be tested because supplier invoice reason flow itself is broken.
2. Closed/paid note correction history may be unavailable in the owner’s manual data because the tested note does not have `note_mutation_events`.
3. Shared note detail may display correction history only when `note['correction_history'] !== []`, so data setup matters.
4. If correction history exists but does not show, inspect shared note detail payload and view include again.

### Refund Flow and Timestamp

Result:

- Tests 16 onward can succeed.
- Owner noticed displayed time mismatch:
  - actual local time: around 10:11 Asia/Makassar,
  - displayed time: around 02:07.
- Difference is approximately 8 hours, matching UTC vs Asia/Makassar offset.

Interpretation:

Likely timezone display/config issue, not a financial amount issue.

Need inspect:

- `config/app.php` timezone,
- `.env` `APP_TIMEZONE`,
- DB timestamp storage expectations,
- use of `now()`,
- use of `date()`,
- `ViewDateFormatter`,
- note/refund/audit timestamp display,
- any raw `created_at`, `updated_at`, `occurred_at`, `refunded_at` display.

This issue must distinguish:

- date-only business dates such as `refunded_at`,
- timestamp display such as audit/mutation/history `created_at` or `occurred_at`.

## Risk Assessment

### Financial Report Risk

Low from the previous 0047/0048 patches because they mainly affected labels, reason metadata, display, and audit payload.

### New Risk From Manual QA

Medium for user trust and audit traceability:

- Supplier invoice edit may not have reliable visible revision reason.
- Timestamp display may mislead owner/cashier about when actions happened.

### Financial Data Integrity

No direct evidence yet that amounts, allocations, refunds, or reports are wrong.

Need explicitly verify after fixes that:

- supplier invoice totals do not change incorrectly,
- payments/receipts/tax are unaffected,
- refund amount/report output stays unchanged,
- only revision metadata/time display is changed.

## Non-Goals

Do not reopen 0047 language cleanup broadly.

Do not rename:

- DB enum/internal values,
- route names,
- request field names,
- DTO keys,
- audit event names,
- public API contracts.

Do not touch `docs/99_archive`.

Do not redesign the full supplier invoice lifecycle unless reproduction proves it is necessary.

Do not convert all timestamps globally without proving impact.

## Required Investigation

### Supplier Invoice Revision Flow

Find the update path:

- route for supplier invoice update,
- controller,
- request validation,
- handler/service,
- persistence adapter,
- revision writer.

Answer:

1. Does update require or accept reason?
2. Does update create `supplier_invoice_versions`?
3. Does update increment/bump `supplier_invoices.last_revision_no`?
4. Does detail page query the same version number that update creates?
5. Does edit form preserve/display reason errors correctly?
6. Is reason escaped in detail display?

### Note Correction History Manual Failure

Reproduce using deterministic seeded data or existing tests:

1. Note with no correction history must not show empty correction card.
2. Note with correction history must show:
   - `Riwayat Mutasi Nota`,
   - mutation label,
   - `Alasan:`,
   - escaped reason text.
3. HTML reason must render as plain text, not HTML.

If automated tests already cover this but manual data does not show it, record as documentation/data mismatch, not code bug.

### Timestamp Mismatch

Reproduce exact mismatch:

1. Perform a refund or correction at local time Asia/Makassar.
2. Capture DB stored values:
   - `created_at`,
   - `updated_at`,
   - `occurred_at`,
   - `refunded_at`,
   - audit log timestamp if available.
3. Capture UI displayed value.
4. Determine whether UI displays UTC, server time, or local business timezone.

Expected owner-facing behavior:

- timestamps should be understandable in owner’s local operational timezone,
- date-only fields should remain date-only and must not be shifted across days.

## Recommended Tests

Add focused tests only after reproducing the real path.

Candidate test areas:

- `tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php`
- `tests/Feature/Note/CashierNoteCorrectionHistoryReasonViewFeatureTest.php`
- timestamp formatter/unit test around `ViewDateFormatter` if needed
- focused HTTP test for refund/correction timestamp display if the bug is presentation-level

## Acceptance Criteria

### Supplier Invoice

- Editing supplier invoice with reason creates a revision/version record.
- `supplier_invoices.last_revision_no` points to the latest version.
- Supplier invoice detail displays the latest revision reason.
- Re-editing supplier invoice displays the newest reason, not an older one.
- Supplier invoice with no version/reason does not crash and does not show empty misleading reason block.
- Reason HTML is escaped.

### Note Correction History

- Note with correction history displays reason.
- Note without correction history does not show empty correction history block.
- Reason HTML is escaped.
- Existing automated tests still pass.

### Timestamp

- Owner-facing timestamp display matches Asia/Makassar operational expectation.
- No unwanted date shifting for date-only fields.
- Refund/report amount behavior remains unchanged.

## Proof Required Before Close

## 2026-06-29 Slice 1 Update - Supplier Invoice Revision Reason

Status: supplier invoice reason path patched and focused tests passed.

Source map result:

- Update route: `PUT /admin/procurement/supplier-invoices/{supplierInvoiceId}` via `UpdateSupplierInvoiceController`.
- Request: `UpdateSupplierInvoiceRequest` requires `expected_revision_no` and `change_reason`.
- Handler path: `UpdateSupplierInvoiceHandler` -> `UpdateSupplierInvoiceTransactionalRunner` -> `UpdateSupplierInvoiceOperation` -> `DatabaseVersionedSupplierInvoiceWriterAdapter`.
- Persistence path: `PersistsVersionedSupplierInvoiceWrites::persistUpdatedInvoice()` creates `supplier_invoice_versions`, increments `supplier_invoices.last_revision_no`, and writes `supplier_invoice_lines` for the new revision.
- Detail path: `ProcurementInvoiceDetailSummaryQuery` reads the version whose `revision_no` equals `supplier_invoices.last_revision_no`; `resources/views/admin/procurement/supplier_invoices/show.blade.php` renders latest reason only when present.

Root cause proven from source:

- Revision/version creation and `last_revision_no` bump already existed.
- The submitted owner-facing `change_reason` was not passed into `SupplierInvoiceChangeContext`.
- `UpdateSupplierInvoiceTransactionalRunner` previously stored the literal event-like text `supplier_invoice_updated` as the context reason, so the latest visible reason could not show the owner-entered edit reason.

Patch:

- `app/Application/Procurement/UseCases/UpdateSupplierInvoiceHandler.php`
- `app/Application/Procurement/Services/UpdateSupplierInvoiceTransactionalRunner.php`
- `tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php`

Proof:

```text
php artisan test tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php

PASS  Tests\Feature\Procurement\UpdateSupplierInvoiceFeatureTest
PASS  Tests\Feature\Procurement\ProcurementInvoiceDetailPageFeatureTest
Tests: 14 passed (116 assertions)
Duration: 6.31s
```

Verified behavior:

- Supplier invoice update still creates revision/version records.
- `supplier_invoices.last_revision_no` remains tied to latest revision.
- `supplier_invoice_versions.change_reason` stores the submitted `change_reason`.
- `audit_events.reason` stores the submitted `change_reason`.
- Detail page displays latest supplier invoice reason.
- Supplier invoice reason HTML is escaped on detail display.

Remaining open items:

- Note correction history manual failures still need reproduction.
- Timestamp UTC vs Asia/Makassar display still needs focused reproduction and patch.
- Full `make verify` not yet run for 0049.

## 2026-06-29 Slice 2 Update - Note Correction History

Status: note correction history path reproduced with focused tests; no app patch required in this slice.

Source map result:

- History source: `DatabaseNoteCorrectionHistoryReaderAdapter` reads only `note_mutation_events` for the current `note_id`.
- Snapshot source: the same reader reads `note_mutation_snapshots` for before/after totals and refund metadata.
- Payload path: `NoteCorrectionHistoryBuilder` -> `NoteDetailPageDataBuilder` -> `NoteDetailNotePayloadBuilder` as `note['correction_history']`.
- View path: `resources/views/cashier/notes/partials/correction-history.blade.php` renders only when `note['correction_history'] !== []`.

Conclusion from focused reproduction:

- If a note has no `note_mutation_events`, the correction history block is intentionally not rendered.
- If a note has a native mutation event, the detail page renders `Riwayat Mutasi Nota`, the mapped mutation label, and `Alasan:`.
- Correction reason HTML is escaped by Blade.
- Revision timeline and correction history can render together on the same note detail page.

Patch:

- No application behavior patch.
- Added focused characterization coverage in `tests/Feature/Note/CashierNoteCorrectionHistoryReasonViewFeatureTest.php`.

Proof:

```text
php artisan test tests/Feature/Note/NoteCorrectionHistoryBuilderFeatureTest.php tests/Feature/Note/CashierNoteCorrectionHistoryReasonViewFeatureTest.php tests/Feature/Note/NoteCorrectionHistoryPageFeatureTest.php tests/Feature/Note/NoteDetailPageShowsNativeCorrectionHistoryFeatureTest.php

PASS  Tests\Feature\Note\NoteCorrectionHistoryBuilderFeatureTest
PASS  Tests\Feature\Note\CashierNoteCorrectionHistoryReasonViewFeatureTest
PASS  Tests\Feature\Note\NoteCorrectionHistoryPageFeatureTest
PASS  Tests\Feature\Note\NoteDetailPageShowsNativeCorrectionHistoryFeatureTest
Tests: 5 passed (35 assertions)
Duration: 6.03s
```

Manual QA interpretation:

- Tests 12-15 should be verified with a note that actually has native correction mutation rows in `note_mutation_events`.
- If owner tested an ordinary closed/paid note without correction mutations, the absence of `Riwayat Mutasi Nota` is expected and not a code bug.

Remaining open items:

- Timestamp UTC vs Asia/Makassar display still needs focused reproduction and patch.
- Full `make verify` not yet run for 0049.

## 2026-06-29 Slice 3 Update - Owner-Facing Timestamp Display

Status: UTC vs Asia/Makassar presentation gap patched and focused tests passed.

Source map result:

- Application timezone remains UTC in `config/app.php`; this is the storage/source timezone for DB and audit timestamps.
- Owner-facing timestamp labels flow through `App\Support\ViewDateFormatter`.
- Note correction history, note revision timeline, refund due/paid audit timeline, audit log rows, product detail history, and service product template detail use `ViewDateFormatter::display(..., true)` for timestamps.
- Refund business date fields such as `refunded_at` remain date-only in request/storage/reporting paths and are not changed by this patch.

Root cause:

- `ViewDateFormatter` parsed DB timestamp strings and formatted them in the source timezone, so a UTC timestamp such as `2026-06-29 02:07:45` rendered as `02:07` instead of local `10:07` Asia/Makassar.

Patch:

- Added `app.display_timezone` config with default `Asia/Makassar`.
- Added `APP_DISPLAY_TIMEZONE=Asia/Makassar` to `.env.example`.
- Updated `ViewDateValueParser` to parse non-legacy DB/date strings with source timezone `app.timezone` fallback `UTC`.
- Updated `ViewDateFormatter` to convert timestamp displays to `app.display_timezone` only when time is requested.
- Preserved legacy slash date text as already-local display input.
- Preserved date-only business values without timezone shifting.

Proof:

```text
php artisan test tests/Unit/Support/ViewDateFormatterTest.php tests/Feature/Note/CashierNoteCorrectionHistoryReasonViewFeatureTest.php tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php

PASS  Tests\Unit\Support\ViewDateFormatterTest
PASS  Tests\Feature\Note\CashierNoteCorrectionHistoryReasonViewFeatureTest
PASS  Tests\Feature\Note\AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest
PASS  Tests\Feature\Note\AdminNoteSurplusRefundPaidUiFeatureTest
Tests: 14 passed (68 assertions)
Duration: 6.08s
```

Verified behavior:

- `2026-06-29 02:07:45` displays as `29 Juni 2026 10:07` for owner-facing timestamps.
- `2026-06-29` remains `29 Juni 2026`.
- `01/06/2026 14:30` remains `01 Juni 2026 14:30` as legacy local text.
- Refund/audit UI tests still pass.

Remaining open items:

- Run combined focused tests for all 0049 slices.
- Run full `make verify`.

## 2026-06-29 Slice 4 Update - Supplier Invoice Version Timeline

Status: supplier invoice detail now has a version timeline similar to product detail, while preserving the existing supplier invoice actor visibility boundary.

Owner follow-up:

- Product detail has `Riwayat Versi Produk` with per-revision snapshots and reasons.
- Supplier invoice detail previously only showed `Perubahan Terakhir` and `Alasan Perubahan Terakhir`.
- Owner expected the invoice to show v1/v2 history with previous/current invoice data and reason.

Source comparison:

- Product detail reads `product_versions` timeline and renders each version snapshot.
- Supplier invoice already writes `supplier_invoice_versions.snapshot_json`, but detail did not read or render the full version timeline.

Patch:

- Added `ProcurementInvoiceDetailVersionTimelineQuery` to read `supplier_invoice_versions`.
- Extended procurement invoice detail payload with `version_timeline`.
- Added view mapping for supplier invoice version snapshots.
- Added `Riwayat Versi Nota Pemasok` card on supplier invoice detail.
- The card renders revision label, event name, changed timestamp, reason, invoice header snapshot, totals, and line snapshots for each version.
- Actor id remains hidden on supplier invoice detail to preserve the prior detail-page visibility contract.

Proof:

```text
php artisan test tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php

PASS  Tests\Feature\Procurement\ProcurementInvoiceDetailPageFeatureTest
PASS  Tests\Feature\Procurement\UpdateSupplierInvoiceFeatureTest
Tests: 15 passed (128 assertions)
Duration: 6.25s
```

Verified behavior:

- Detail page displays `Riwayat Versi Nota Pemasok`.
- Detail page displays `Revisi 1` and `Revisi 2`.
- Version snapshots show invoice number, supplier, dates, subtotal, tax, total, and line items.
- Added/changed lines are visible through the per-version line snapshot table.
- Version reason HTML is escaped.
- `actor-admin` is not shown on supplier invoice detail.

Remaining open items:

- Run combined focused tests for all 0049 slices.
- Run full `make verify`.

Run targeted tests for any changed areas.

Then run:

```bash
make verify
```

Record:

- focused test output,
- full verify output,
- `git status --short`,
- `git diff --stat`.

## Closure Rule

Do not close this issue only because automated tests pass.

Close only after:

- supplier invoice manual QA succeeds,
- note correction manual QA is either fixed or proven data/setup-specific,
- timestamp mismatch is fixed or explicitly documented with acceptable business decision,
- `make verify` passes.

## Session Update - 2026-06-29 Supplier Invoice Revision Negative Stock, Draft Slot, and Version Timeline UX Fixed

### Slice

- Active issue: supplier invoice revision edit path.
- Trigger: manual QA revisi faktur supplier.
- User-facing symptom:
  - Revisi faktur supplier yang hanya mengubah pajak dari 15% ke 20% gagal.
  - Error yang muncul: `Revisi faktur akan membuat stok product lama menjadi negatif.`
  - Alasan perubahan sebelumnya tidak terlihat jelas pada alur gagal.
  - Histori versi menampilkan snapshot penuh, tetapi tidak menjelaskan perubahan secara ringkas.
  - Halaman edit otomatis menambahkan slot rincian kosong di atas, yang berisiko ikut tersubmit sebagai line baru.

### Root Cause

- Backend delta movement builder terlalu percaya pada `previous_line_id`.
- Jika `previous_line_id` kosong/stale/tidak cocok:
  - line request dianggap sebagai line baru dan dibuat `stock_in`;
  - old line yang tidak referenced dianggap dihapus dan dibuat `stock_out`;
  - negative stock guard menolak revisi jika stok product lama sudah habis/berkurang.
- Pada halaman edit, JS juga otomatis membuat top working line kosong:
  - setelah draft dipulihkan;
  - saat halaman edit dibuka tanpa draft.
- Slot kosong ini aman untuk create form, tetapi fatal untuk edit form karena hidden `previous_line_id` kosong dapat masuk ke request lifecycle.
- Histori versi sebelumnya hanya menampilkan snapshot penuh per versi, sehingga user harus membandingkan versi secara manual.

### Files Changed

- `app/Application/Procurement/Services/SupplierInvoiceRevisionDeltaMovementsBuilder.php`
- `public/assets/static/js/pages/admin-procurement-edit.js`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/Concerns/BuildsProcurementInvoiceDetailViewData.php`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`
- `tests/Feature/Procurement/SupplierInvoiceTaxFinancialInvariantFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php`

### Backend Fix

- Added safe fallback pairing in `SupplierInvoiceRevisionDeltaMovementsBuilder`.
- If `previous_line_id` is missing/stale, backend may still pair old/new lines only when all safe identity checks match:
  - same `line_no`;
  - same `product_id`;
  - same `qty_pcs`;
  - old line has not already been referenced.
- This allows tax-only/cost-only revisions to create cost revaluation instead of false stock movement.
- Product/qty changes still use normal delta stock behavior.

### UI Draft/Edit Fix

- Removed automatic top working line creation on supplier invoice edit page load.
- Removed automatic top working line creation after draft restore.
- Manual add line remains available through the add button and explicit shortcuts.
- Empty lines are still pruned before submit as a guardrail.
- Result: edit form no longer opens with an extra blank row that has empty `previous_line_id`.

### Version Timeline UX Fix

- Added `Ringkasan Perubahan` to supplier invoice version timeline.
- Diff summary currently includes important owner-facing changes such as:
  - invoice number change;
  - shipment date change;
  - grand total change;
  - qty change;
  - added line;
  - removed line.
- Full version snapshot is preserved but hidden behind Bootstrap collapse.
- Owner-facing timeline now shows summary first, with full detail available through `Lihat Detail Versi`.
- Version 1/initial version shows `Versi awal nota pemasok.`

### Tests Added / Updated

- Added regression test:
  - `SupplierInvoiceTaxFinancialInvariantFeatureTest::test_received_invoice_tax_only_revision_with_missing_previous_line_id_pairs_by_same_line_product_and_qty`
- The test proves:
  - tax-only revision with missing `previous_line_id` succeeds;
  - cost revaluation movement is created;
  - false `stock_out` movement is not created.
- Updated procurement invoice detail page test to assert:
  - `Ringkasan Perubahan`;
  - diff text for changed invoice number/date/total/qty/added line;
  - `Lihat Detail Versi`;
  - Bootstrap collapse attributes.

### Verification

- Targeted regression initially failed with:
  - `Revisi faktur akan membuat stok product lama menjadi negatif.`
- After backend fallback pairing, targeted regression passed.
- `SupplierInvoiceTaxFinancialInvariantFeatureTest` passed.
- `ProcurementInvoiceDetailPageFeatureTest` passed.
- Combined targeted runs passed after JS and timeline UX changes.

### Final Decision

- Keep full version data for audit and debugging.
- Show owner-facing concise change summary by default.
- Hide full snapshot behind expandable detail.
- Treat missing/stale `previous_line_id` as recoverable only for safe same-line/same-product/same-qty revision cases.
- Do not auto-create blank working line on edit page load.

### Remaining Follow-up

- Consider adding browser-level/manual QA notes for:
  - edit page opens with no extra blank line;
  - add button still creates a new blank line intentionally;
  - stale localStorage draft no longer causes false stock-out on tax-only revision;
  - version timeline collapse works on mobile and desktop.


## Session Update - 2026-06-29 Supplier Invoice Edit Draft Revision Scope Hardened

### Slice

- Follow-up hardening for supplier invoice edit draft lifecycle.
- Trigger: after fixing negative stock revision bug and removing automatic blank edit row.
- Scope: edit supplier invoice draft key isolation.

### Problem

- Edit form draft key previously used only invoice id:
  - `admin.procurement.edit-supplier-invoice.{invoiceId}.draft.v1`
- This allowed stale localStorage draft from an older revision to be restored after a successful revision.
- In supplier invoice edit lifecycle, stale draft data is dangerous because line identity depends on `previous_line_id`.
- Old draft lines may contain stale/empty `previous_line_id`, which can cause backend delta builder to interpret tax-only edits as delete+insert lines.

### Fix

- Changed supplier invoice edit draft key to include expected revision number:
  - `admin.procurement.edit-supplier-invoice.{invoiceId}.revision.{expectedRevisionNo}.draft.v1`
- Result:
  - draft for revision N is not reused for revision N+1;
  - after successful revision, opening edit page uses a new draft namespace;
  - stale draft restore risk is reduced without removing local draft convenience.

### Files Changed

- `resources/views/admin/procurement/supplier_invoices/edit.blade.php`

### Verification

- Manual browser console confirmed key format:
  - `admin.procurement.edit-supplier-invoice.e8f5fd4c-f0cc-4698-86b7-ade9d1130b40.revision.4.draft.v1`
- Targeted tests passed:
  - `SupplierInvoiceTaxFinancialInvariantFeatureTest`
  - `ProcurementInvoiceDetailPageFeatureTest`

### Final Decision

- Keep localStorage draft support for edit form.
- Scope edit draft by invoice id and expected revision number.
- Do not reuse drafts across supplier invoice revisions.


## Session Update - 2026-06-29 Note Correction History Manual Failure Reclassified

### Slice

- Follow-up for manual QA tests 11-15 from error log 0049.
- Scope: cashier note correction history reason display, empty history behavior, and HTML/XSS escaping.

### Investigation Result

- Existing targeted test was found:
  - `tests/Feature/Note/CashierNoteCorrectionHistoryReasonViewFeatureTest.php`
- Targeted test run passed:
  - `CashierNoteCorrectionHistoryReasonViewFeatureTest`
  - 2 tests passed
  - 19 assertions passed

### Existing Automated Coverage Confirmed

- Correction history reason display is covered.
- `Riwayat Mutasi Nota` is asserted visible when mutation history exists.
- `Alasan:` is asserted visible when reason exists.
- HTML/script reason is asserted escaped:
  - escaped text is visible;
  - raw `<script>` reason is not visible.
- Actor internals are asserted not visible.
- Note without correction history is asserted not to show an empty mutation history block.
- The same page asserts `Riwayat Perubahan Nota` and `Perubahan Aktif`, so the combined note detail view with revision timeline and correction history is covered.

### Decision

- No production patch required for this slice at this time.
- Manual failure is reclassified as likely manual data/setup mismatch unless owner can reproduce with a note that has rows in `note_mutation_events`.
- If the issue reappears, first inspect the tested note id and confirm whether it has related mutation events and snapshots.

### Verification

- `php artisan test --filter=CashierNoteCorrectionHistoryReasonViewFeatureTest` passed.


## Session Update - 2026-06-29 Production Timestamp Repair Risk Assessment

### Slice

- Follow-up for timestamp mismatch / timezone display issue.
- Context:
  - Lab tests passed for `ViewDateFormatterTest`.
  - Formatter now displays database timestamp `2026-06-29 02:07:45` as `29 Juni 2026 10:07` in Asia/Makassar operational timezone.
  - Date-only business values are not shifted.
- Production constraint:
  - Real production is on shared hosting.
  - Lab environment is separate from production.
  - Production data may contain legacy timestamp inconsistencies.

### Current Proof

- `php artisan test --filter=ViewDateFormatterTest` passed:
  - 8 tests passed.
  - 10 assertions passed.
- Existing formatter behavior protects:
  - timestamp display conversion from app timezone/source timezone to `app.display_timezone`;
  - date-only display from unwanted timezone shifting.

### Risk

- Do not blindly update old production timestamps.
- Some legacy rows may be stored as UTC-like values.
- Some legacy rows may already be stored as local Asia/Makassar-like values.
- Blind `+8 hours` or `-8 hours` updates can corrupt audit history and financial timeline labels.
- Date-only business fields must not be shifted.

### Fields That Must Not Be Bulk-Shifted

Treat these as business dates unless a specific table proves otherwise:

- `refunded_at`
- `transaction_date`
- `shipment_date`
- `due_date`
- `expense_date`
- `payment_date`
- report period labels / date range fields

### Candidate Timestamp Fields For Diagnosis Only

These may be audited because they represent event time / audit timeline time:

- `audit_events.occurred_at`
- `audit_events.created_at`
- `note_mutation_events.occurred_at`
- `note_revision_surplus_dispositions.occurred_at`
- `note_revision_surplus_dispositions.created_at`
- `note_revision_surplus_refund_payments.occurred_at`
- `note_revision_surplus_refund_payments.created_at`
- note revision `created_at`
- supplier invoice history `occurred_at` / `created_at`

### Decision

- Do not execute production timestamp repair yet.
- First implement and run lab-only diagnostic.
- Production should be handled later with:
  1. full database backup/export;
  2. read-only diagnostic query;
  3. sample comparison against known real action times;
  4. dry-run repair plan;
  5. maintenance window;
  6. narrow table/row update only if proven necessary;
  7. post-repair UI/report verification.

### Recommended Next Session Scope

- Build a lab-only timestamp diagnostic plan.
- Add a safe artisan command or SQL checklist that can:
  - print config timezone and display timezone;
  - sample recent rows from candidate audit/history tables;
  - classify whether rows look UTC-like or local-like;
  - run in read-only mode by default.
- Do not write production repair command until diagnostic output proves a correction strategy.

### Production Rule

- Lab first.
- No production write without backup.
- No date-only field shifting.
- No global timestamp migration.
- Prefer display fix over data mutation unless legacy data is proven wrong.

