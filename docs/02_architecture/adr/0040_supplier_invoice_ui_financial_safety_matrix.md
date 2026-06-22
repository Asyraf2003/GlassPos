# ADR-0040: Supplier Invoice UI Financial Safety Matrix

Status: Proposed

Date: 2026-06-23

## Context

ADR-0037 and ADR-0038 fixed the core backend behavior for editable received supplier invoices, explicit inventory cost revaluation, and exact supplier invoice values with rounding residue.

The verified backend state after ADR-0037/0038:

- `make verify` passed with 1324 tests and 7872 assertions.
- Received supplier invoices can be edited after receipt.
- Unit-cost changes no longer fail with the old received-unit-cost blocker.
- Cost changes are represented through explicit `cost_revaluation` inventory movements.
- `qty_delta = 0` is valid only for `cost_revaluation`.
- `rounding_residue_rupiah` preserves supplier document values that do not divide evenly by qty.
- Inventory value now preserves exact supplier totals, including rounding residue.
- The old hard validation `total rincian harus habis dibagi qty` is no longer allowed to block valid real-world supplier invoice values.

However, backend correctness is not enough for a live financial system.

A UI mismatch can still cause:

- valid real-world cases to fail at submit time;
- stale hidden inputs to send wrong values;
- displayed totals to differ from submitted backend values;
- user confirmation to be skipped or stuck;
- draft/localStorage values to overwrite fresh values;
- line tax/header tax mode to submit stale disabled fields;
- show/edit/detail pages to display values from a different basis than backend storage.

For financial systems, a "UI-only bug" is still a financial correctness bug.

## Risk framing

If there are 50 remaining bugs around supplier invoices, procurement UI, inventory value, and reporting, the estimated severity split is:

- Critical 500 / transaction fails for valid real-world cases: 8-15.
- Critical money drift / stored amount wrong without crash: 8-12.
- High UI-backend mismatch / user guided into wrong input: 10-18.
- Medium reporting/display mismatch: 8-12.
- Low label/layout/cosmetic issues: 5-10.

The most dangerous class is not HTTP 500. A 500 is visible. The more dangerous class is a successful transaction with incorrect financial values.

## Existing UI coverage found

Existing UI tests cover the basic supplier invoice tax UI contract:

- `SupplierInvoiceTaxUiFeatureTest`
  - create page renders supplier tax input;
  - edit page renders supplier tax input with existing value;
  - show page renders supplier tax summary;
  - edit page renders line tax input with existing value;
  - show page renders line tax details;
  - edit page uses before-tax line total for existing line-tax invoice;
  - edit page uses before-tax line total for existing header-tax invoice.

- `SupplierInvoiceTaxRoundingResidueUiFeatureTest`
  - create page renders `tax_rounding_residue_confirmed`;
  - edit page renders `tax_rounding_residue_confirmed`;
  - create/edit pages render `data-tax-rounding-residue-confirmed-input`;
  - create/edit pages render `data-tax-rounding-residue-message`;
  - residue warning message is present.

- `CreateSupplierInvoicePageFeatureTest`
  - create page includes line fields, product search, supplier search, and JS asset.

- `EditSupplierInvoicePageFeatureTest`
  - edit page includes line fields and JS asset.

- `EditSupplierInvoiceRevisionContractFeatureTest`
  - edit page includes `expected_revision_no`;
  - edit page includes `change_reason`;
  - edit page includes `previous_line_id`.

## Current UI financial field map

Supplier invoice blade files contain the following financial contracts.

### Create page

File:

- `resources/views/admin/procurement/supplier_invoices/create.blade.php`

Important fields/contracts:

- `tax_rounding_residue_confirmed`
- `data-tax-rounding-residue-confirmed-input`
- `data-tax-rounding-residue-message`
- `lines[*][line_total_rupiah]`
- `data-money-raw`
- `data-money-display`
- `data-tax-line-input`
- `data-tax-header-input`

### Edit page

File:

- `resources/views/admin/procurement/supplier_invoices/edit.blade.php`

Important fields/contracts:

- `tax_rounding_residue_confirmed`
- `data-tax-rounding-residue-confirmed-input`
- `data-tax-rounding-residue-message`
- `lines[*][previous_line_id]`
- `lines[*][line_no]`
- `lines[*][product_id]`
- `lines[*][qty_pcs]`
- `lines[*][line_total_rupiah]`
- `data-money-raw`
- `data-money-display`
- `data-tax-line-input`
- `data-tax-header-input`

### Show page

File:

- `resources/views/admin/procurement/supplier_invoices/show.blade.php`

Important display values:

- `subtotal_before_tax_label`
- `tax_amount_label`
- `grand_total_label`
- line `unit_cost_label`
- line `line_subtotal_before_tax_label`
- line `tax_amount_label`

Potential gap:

- show page may not explicitly surface `rounding_residue_rupiah` or explain when unit cost is rounded while total remains exact.

## Current JS financial behavior map

Supplier invoice JS files contain shared financial submit behavior.

Files:

- `public/assets/static/js/pages/admin-procurement-create.js`
- `public/assets/static/js/pages/admin-procurement-edit.js`

Important JS contracts:

- reads `data-tax-rounding-residue-confirmed-input`;
- reads `data-tax-rounding-residue-message`;
- calculates line tax and header tax client-side for confirmation prompt;
- checks residue with `(line.total + tax) % line.qty !== 0`;
- shows `window.confirm(...)` before submit when residue exists;
- sets `tax_rounding_residue_confirmed = 1` after user confirms;
- resets confirmation back to `0` when qty, total, header tax, line tax, or money raw/display changes;
- header tax hides/disables line tax;
- line tax hides/disables header tax;
- draft/localStorage stores and restores:
  - header tax input;
  - line qty;
  - line total raw;
  - line total display;
  - line tax input.

Potential gaps:

- no strong test that changing qty/total/tax after confirmation resets confirmation to `0`;
- no browser-level test that canceling confirmation prevents submit;
- no test that draft restore preserves hidden raw value exactly;
- no test that disabled tax fields cannot submit stale values;
- no test that UI and backend calculate the same residue for multi-line header tax allocation;
- no test that no-tax base residue also triggers confirmation in UI.

## Existing backend HTTP coverage found

Backend HTTP tests already cover many real submit payloads:

- create supplier invoice with standard lines;
- create supplier invoice with header tax;
- create supplier invoice with line tax;
- create/update rounding residue with confirmation;
- update without rounding confirmation rejected without 500;
- received invoice tax revisions create cost revaluation;
- non-divisible base line total preserves exact inventory value;
- fixed tax residue preserves exact inventory value;
- paid invoice cannot be revised below paid total;
- paid invoice can be revised upward;
- received invoice qty increase/decrease;
- received invoice negative stock guard;
- audit/version snapshots for cost revaluation.

## Dangerous absence check

The prior hard blocker is expected to stay absent:

- `validateLineTotalDivisibleByQty`
- `total rincian harus habis dibagi qty`

These should not reappear in app/tests as active validation behavior.

## P0 test matrix for next execution session

The next session should not patch UI blindly. It must first add characterization or RED tests for the following matrix.

### UI-001: create page residue contract remains rendered

Goal:

- protect create page contract from accidental removal.

Assertions:

- page is 200;
- hidden input `name="tax_rounding_residue_confirmed"` exists;
- default `value="0"` exists;
- `data-tax-rounding-residue-confirmed-input` exists;
- `data-tax-rounding-residue-message` exists;
- warning text exists.

Existing coverage:

- partially present in `SupplierInvoiceTaxRoundingResidueUiFeatureTest`.

Decision:

- keep existing test, extend only if needed.

### UI-002: edit page residue contract remains rendered

Goal:

- protect edit page contract from accidental removal.

Assertions:

- page is 200;
- hidden input exists;
- default value is `0`;
- message is present.

Existing coverage:

- partially present in `SupplierInvoiceTaxRoundingResidueUiFeatureTest`.

Decision:

- keep existing test, extend only if needed.

### UI-003: edit page displays before-tax line total for header-tax invoice

Goal:

- prevent double-tax edits.

Known risk:

- if edit page displays after-tax line total while header tax is still set, submit can double tax.

Existing coverage:

- present in `SupplierInvoiceTaxUiFeatureTest`.

Decision:

- keep test, ensure it still covers both raw hidden value and visible display value.

### UI-004: edit page displays exact supplier total for no-tax residue invoice

Goal:

- protect ADR-0038 no-tax base residue.

Scenario:

- invoice line qty = 3;
- line total = 155000;
- unit cost = 51666;
- rounding residue = 2;
- no tax.

Assertions:

- edit page hidden `line_total_rupiah` value is `155000`;
- visible display is `155.000`;
- no old divisibility error appears;
- submit payload with confirmation remains valid.

### UI-005: show page explains rounded unit cost and exact line total

Goal:

- prevent user confusion when unit cost * qty does not visually equal line total.

Scenario:

- qty = 3;
- unit cost = 51666;
- residue = 2;
- line total = 155000.

Assertions:

- show page displays `Rp 155.000`;
- show page displays `Rp 51.666`;
- show page displays residue or explanatory note if `rounding_residue_rupiah > 0`.

Decision needed:

- If product owner does not want residue visible, show page must at least not imply wrong math.

### UI-006: UI submit with no-tax base residue succeeds

Goal:

- protect valid real supplier document values.

Scenario:

- edit received invoice;
- qty changed to 3;
- `line_total_rupiah = 155000`;
- `tax_input = null`;
- `tax_rounding_residue_confirmed = true`.

Assertions:

- redirect to show;
- line stores `line_total_rupiah = 155000`;
- line stores `unit_cost_rupiah = 51666`;
- line stores `rounding_residue_rupiah = 2`;
- inventory value = 155000.

Existing coverage:

- backend HTTP coverage exists in `SupplierInvoiceTaxFinancialInvariantFeatureTest`.

Decision:

- keep backend test, add UI read-model/display tests.

### UI-007: UI submit with fixed tax residue succeeds

Goal:

- protect header fixed tax residue case.

Scenario:

- qty = 3;
- subtotal = 150000;
- header tax = 5000;
- final line total = 155000;
- confirmation true.

Assertions:

- redirect to show;
- subtotal before tax = 150000;
- tax = 5000;
- grand total = 155000;
- line total = 155000;
- unit cost = 51666;
- residue = 2;
- inventory value = 155000.

Existing coverage:

- backend HTTP coverage exists in `SupplierInvoiceTaxFinancialInvariantFeatureTest`.

Decision:

- keep backend test, add UI read-model/display tests.

### UI-008: submit without residue confirmation returns controlled error, not 500

Goal:

- residue confirmation must be a safe validation path.

Scenario:

- no-tax or fixed-tax residue exists;
- `tax_rounding_residue_confirmed = false`.

Assertions:

- redirect back to create/edit;
- session error exists;
- no 500;
- database unchanged.

Existing coverage:

- update residue rejection exists in `UpdateSupplierInvoiceTaxRoundingResidueFeatureTest`;
- create residue rejection likely covered by unit allocator tests and create feature tests.

Decision:

- verify create and update both covered.

### UI-009: changing qty/total/tax resets confirmation to 0

Goal:

- prevent stale hidden confirmation after user changes numbers.

Scenario:

- user confirms residue once;
- then changes qty, total, header tax, or line tax;
- hidden input must reset to 0.

Implementation target:

- JS unit-style test if JS test harness exists;
- otherwise add contract doc and manual QA script.

Risk:

- current PHP feature tests cannot execute browser JS.

Decision:

- if no JS test runner exists, document manual QA checklist and consider adding a small frontend test harness later.

### UI-010: header tax disables line tax and clears stale line tax

Goal:

- prevent mixed header/line tax hidden stale values.

Assertions:

- header tax input with value hides/disables line tax fields;
- line tax values are cleared when header tax is selected;
- submitted payload cannot contain stale line tax.

Existing backend coverage:

- mixed header and line tax is rejected.

Gap:

- UI behavior itself is not strongly tested by PHP unless rendered HTML/default values are inspected.

### UI-011: line tax disables header tax and clears stale header tax

Goal:

- prevent mixed tax mode in the other direction.

Assertions:

- line tax input with value hides/disables header tax;
- header tax value is cleared;
- submitted payload cannot contain stale header tax.

Existing backend coverage:

- mixed header and line tax is rejected.

Gap:

- UI behavior itself is not strongly tested by PHP.

### UI-012: draft restore does not overwrite hidden money raw incorrectly

Goal:

- protect localStorage/draft from corrupting submitted totals.

Risks:

- display value differs from hidden raw value;
- hidden raw is empty while display has money;
- stale draft from old version overwrites fresh server-rendered values;
- edit draft key does not include invoice id correctly.

Decision:

- if no JS/browser test harness exists, create manual QA script first.

## P0 backend-finance matrix still open

### FI-001: sold-stock supplier invoice correction

Goal:

- finish ADR-0037 policy C.

Scenario:

- supplier invoice received;
- stock is sold through `work_item_store_stock_line`;
- supplier invoice cost is revised after sale.

Open decision:

- Do not rewrite historical sale COGS unless explicitly authorized.
- Create separate sold-stock correction ledger/effect.
- Reporting must decide whether correction affects Operational Profit, supplier correction report, or both.

Current state:

- On-hand revaluation is verified.
- Sold-stock correction is not completed.

Priority:

- P0, because it can affect profit reports.

### FI-002: multi-line header fixed tax residue

Goal:

- ensure backend and UI allocate tax residue identically.

Scenario:

- multiple products;
- header fixed tax;
- allocation has remainders;
- one or more lines have rounding residue.

Assertions:

- sum line totals equals grand total;
- sum line tax equals invoice tax;
- each line stores correct residue;
- inventory value per product equals exact allocated line total;
- UI confirmation agrees with backend allocator.

Priority:

- P1 after sold-stock correction.

### FI-003: qty decrease with residue

Goal:

- ensure stock_out plus revaluation preserves exact remaining value.

Scenario:

- received invoice qty 3 / total 155000 / residue 2;
- revise to qty 2 or qty 1 with a non-divisible total.

Assertions:

- no 500;
- stock_out total cost correct;
- cost_revaluation correct;
- final inventory qty/value exact.

Priority:

- P1.

### FI-004: product change with residue

Goal:

- ensure product replacement keeps both old and new product inventory exact.

Scenario:

- received invoice product A;
- revise line to product B;
- new line has residue.

Assertions:

- product A stock_out;
- product B stock_in;
- product B line stores residue;
- inventory value exact;
- negative stock guard still applies.

Priority:

- P1.

### FI-005: negative revaluation larger than current on-hand value

Goal:

- decide whether current clamping behavior is acceptable.

Scenario:

- invoice value is revised downward heavily;
- current inventory value is lower than correction delta.

Open decision:

- reject?
- clamp and record residual correction elsewhere?
- allow negative value?
- sold-stock correction ledger?

Priority:

- P0/P1, depending on sold-stock design.

## Execution protocol for next session

Start next session with this prompt:

```text
Kita lanjut dari ADR-0037/0038 yang sudah make verify GREEN: 1324 tests / 7872 assertions.

Jangan patch besar dulu. Fokus ke docs ADR-0040 Supplier Invoice UI Financial Safety Matrix.

Tujuan sesi:
1. Baca docs/02_architecture/adr/0040_supplier_invoice_ui_financial_safety_matrix.md
2. Verifikasi ulang source map UI supplier invoice create/edit/show, JS create/edit, dan tests procurement terkait.
3. Buat RED tests kecil untuk UI financial safety, mulai dari UI-004 dan UI-005:
   - edit page displays exact supplier total for no-tax residue invoice
   - show page displays/explains rounded unit cost + residue/exact total
4. Jangan sentuh sold-stock correction dulu kecuali diminta eksplisit.
5. Jangan sentuh payment proof/mobile API/refund policy.
6. Setelah RED jelas, patch minimal.
7. Run focused tests lalu make verify.

Output wajib pakai format:
FACT
GAP
DECISION
COMMAND
PROOF

Repo root lokal: /home/asyraf/Code/laravel/bengkel2/app
Jangan git add/commit/push/stash/reset/checkout.
```

## Commands for next session source-map refresh

```bash
if [ ! -f artisan ]; then
    echo "ERROR: jalankan dari root Laravel yang punya artisan"
    echo "SKIP: ADR-0040 source map tidak dijalankan"
else
    echo "=== ADR-0040 SOURCE MAP ==="

    echo
    echo "=== DOC ==="
    sed -n '1,260p' docs/02_architecture/adr/0040_supplier_invoice_ui_financial_safety_matrix.md

    echo
    echo "=== UI TESTS ==="
    rg -n "SupplierInvoiceTaxUiFeatureTest|SupplierInvoiceTaxRoundingResidueUiFeatureTest|line_total_rupiah|rounding_residue_rupiah|tax_rounding_residue_confirmed|assertSee|assertMatchesRegularExpression" tests/Feature/Procurement -g'*.php'

    echo
    echo "=== BLADE ==="
    rg -n "tax_rounding_residue_confirmed|data-tax-rounding-residue|line_total_rupiah|line_total_raw|line_total_display|line_subtotal_before_tax|unit_cost|rounding_residue|subtotal_before_tax_label|tax_amount_label|grand_total_label|data-money-raw|data-money-display" resources/views/admin/procurement/supplier_invoices -g'*.blade.php'

    echo
    echo "=== JS ==="
    rg -n "taxRoundingResidue|requiresTaxRoundingResidueConfirmation|confirmTaxRoundingResidueBeforeSubmit|resetTaxRoundingResidueConfirmation|localStorage|draft|line_total_rupiah|line_total_display|data-money-raw|data-money-display|data-tax-header-input|data-tax-line-input" public/assets/static/js/pages/admin-procurement-create.js public/assets/static/js/pages/admin-procurement-edit.js

    echo
    echo "=== BACKEND EXACT VALUE TESTS ==="
    rg -n "non_divisible_base_line_total|fixed_tax_residue|inventory_value_rupiah|rounding_residue_rupiah|cost_revaluation|exact inventory value" tests/Feature/Procurement tests/Feature/Inventory tests/Unit -g'*.php'

    echo
    echo "=== OLD HARD VALIDATION ABSENCE ==="
    rg -n "validateLineTotalDivisibleByQty|total rincian harus habis dibagi qty" app tests || true
fi
```

## Definition of done for ADR-0040 slice

This slice is complete only when:

- UI source-map proof exists.
- At least UI-004 and UI-005 are covered by tests.
- Existing UI rounding residue tests still pass.
- Existing backend ADR-0037/0038 focused tests still pass.
- `make verify` passes.
- No old hard validation reappears.
- No unrelated payment proof/mobile API/refund policy changes are included.

## Implemented slice: UI-004, UI-005, UI-006

Date: 2026-06-23

Status: Implemented locally and verified.

### UI-004: edit page exact no-tax residue invoice

The edit page now has regression coverage for a no-tax supplier invoice line where:

- `qty_pcs = 3`
- `line_total_rupiah = 155000`
- `unit_cost_rupiah = 51666`
- `rounding_residue_rupiah = 2`
- `tax_input = null`
- `tax_mode = none`
- `tax_amount_rupiah = 0`
- `grand_total_rupiah = 155000`

The UI contract requires the edit page to preserve the exact supplier document value:

- hidden `lines[0][line_total_rupiah]` remains `155000`;
- visible money display shows `155.000`;
- the old divisibility blocker text does not appear;
- `tax_rounding_residue_confirmed` remains rendered.

### UI-005: show page rounding explanation

The show page now has regression coverage for no-tax residue lines.

When `rounding_residue_rupiah > 0`, the detail table keeps both values visible:

- rounded unit cost, for example `Rp 51.666`;
- exact line total, for example `Rp 155.000`.

The page also explains the behavior with the user-facing note:

> Modal per pcs dibulatkan. Selisih pembulatan disimpan agar total nota tetap sesuai dokumen supplier.

This prevents the show page from implying that `unit_cost * qty` is the exact final supplier document value when a residue exists.

### UI-006: system-standard confirmation UI

The create/edit procurement invoice JavaScript no longer uses native browser confirmation dialogs for tax rounding residue confirmation.

The prior `window.confirm(...)` behavior was replaced with the system-standard SweetAlert2 confirmation flow.

The JS contract now requires:

- no `window.confirm` in procurement create/edit invoice JS;
- `Swal.fire(...)` for the confirmation dialog;
- `tax_rounding_residue_confirmed` is set to `1` only after the user confirms;
- confirmation resets back to `0` when financial inputs change.

The submit flow remains guarded so the form is not submitted before the SweetAlert2 confirmation is resolved.

### Verification proof

Latest local verification pasted by operator:

- `1326 tests passed`
- `7884 assertions`
- duration around `96.58s`

The old hard blocker remains absent from `app` code. The phrase `total rincian harus habis dibagi qty` is retained only as a negative UI assertion to prevent regression.
