# CreateOnly Seed System Stabilization Handoff

## Status

Active handoff.

This handoff records CreateOnly seed system stabilization for manual QA and owner-readable reporting preparation.

This handoff does not close the full seed system.

## Source Of Truth

- Active seed scale blueprint: docs/03_blueprints/seeder/0001_create_only_seed_scale_profiles.md

Local command output is the highest source of truth.

Do not claim a seed, report, projection, test, or verify status without local proof.

Do not discuss git workflow unless explicitly requested.

## Scope

### In Scope

- Stabilize human-facing create-all seed command.
- Ensure source seed rows appear in read-model projections.
- Move existing CreateOnly seed dates into the active month.
- Add a minimal weekly create transaction seed through the real create transaction use case.
- Prepare next report sanity step before scaling dataset.

### Out Of Scope For Current State

- Edit/revision seeding.
- Soft delete seeding.
- Refund seeding, except future disabled/pending scaffold.
- Monthly 100-200 juta dataset.
- Peak 500 juta/month dataset.
- Stress 6-8 miliar/month dataset.
- Report wording patch.
- Full lifecycle closure.

## Completed Work

### SEED-INFRA-001 - Human-facing create-all projection rebuild

Human-facing create-all-v3 now runs:

- source seed
- audit baseline
- projection rebuild all

Relevant target behavior was proven by dry-run output containing:

- Database\Seeders\CreateOnly\CreateTransactionWeekSeeder
- Database\Seeders\CreateOnly\CreateAuditBaselineSeeder
- php artisan projection:rebuild-indexes all

Raw seed-create-all-v3 remains source-only/debug style.

### SEED-PROJECTION-001 - Procurement projection drift resolved

Problem:

- supplier_invoices source rows existed.
- supplier_invoice_list_projection had only partial/manual rows.
- procurement/faktur UI read projection, not source table.

Manual proof showed projection rebuild fixed source-to-projection visibility.

Final create-all-v3 now rebuilds projections automatically.

### SEED-DATE-001 - CreateOnly active-month calendar helper

Added helper:

- database/seeders/CreateOnly/Support/CreateOnlySeedCalendar.php

Purpose:

- avoid hardcoded 2026-05 dates.
- use active current month dates.
- allow next-month due dates where appropriate.

Applied to:

- database/seeders/CreateOnly/CreateSupplierProcurementSeeder.php
- database/seeders/CreateOnly/CreateOperationalExpenseSeeder.php
- database/seeders/CreateOnly/CreatePayrollDisbursementSeeder.php
- database/seeders/CreateOnly/CreateEmployeeDebtSeeder.php
- database/seeders/CreateOnly/CreateEmployeeDebtPaymentSeeder.php
- database/seeders/CreateOnly/CreateEmployeeDebtAdjustmentSeeder.php

### SEED-DATE-002 - Non-transaction active-month sanity proof

Local sanity output after the date fixes:

supplier_invoices_june = 24
supplier_payments_june = 24
operational_expenses_june = 45
payroll_june = 6
employee_debts_june = 13
employee_debt_payments_june = 6
employee_debt_adjustments_june = 3
notes = 0

Meaning:

- non-transaction source seed is now aligned with June 2026.
- create transaction seed was still absent at that proof point.

### SEED-TXN-001 - Minimal weekly create transaction seed

Added file:

- database/seeders/CreateOnly/CreateTransactionWeekSeeder.php

Wired make target:

- seed-transaction-week

Wired into:

- seed-create-all-v3

Implementation decision:

- Create note seed must use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler.
- Do not raw-insert notes/work_items/payments/projections.

Reason:

CreateTransactionWorkspaceHandler performs the real mutation path:

- idempotency replay/start/succeed
- transaction begin/commit/rollback
- note creation
- work item persistence
- inventory issue for store stock lines
- note total update
- inline payment recording
- payment component allocations
- audit log
- note history projection sync

### SEED-TXN-002 - Weekly transaction seed final local proof

Command:

php artisan migrate:fresh --seed
make create-all-v3

Then tinker count proof.

Final local output:

create-only transaction week notes: planned=6 created=6 replayed=0

Projection rebuild output:

Procurement projection: 24/24
Supplier projection: 78/78
Note projection: 6/6

Final counts:

notes = 6
work_items = 6
work_item_service_details = 6
work_item_store_stock_lines = 3
work_item_external_purchase_lines = 2
customer_payments = 5
payment_component_allocations = 9
inventory_stock_out_for_work_items = 3
note_history_projection = 6
transaction_note_totals.total_notes = 6
transaction_note_totals.total_rupiah = 1225000
external_package_note.customer_name = Seed Customer Mingguan 006
external_package_note.total_rupiah = 275000

Interpretation:

- weekly transaction seed is GREEN at minimal level.
- 6 notes were created through the real create transaction handler.
- note projection rebuild is GREEN.
- store stock inventory issue path is covered.
- external purchase path is covered.
- package auto split store-stock multi-product path is covered.
- external package path is covered.
- inline payment path is covered.
- 5 payments are expected because one note intentionally skips payment.

### SEED-TXN-003 - Monthly-normal create transaction seed

Added files:

- `database/seeders/CreateOnly/CreateTransactionMonthNormalSeeder.php`
- `database/seeders/CreateOnly/Support/CreateTransactionMonthNormalPayloadFactory.php`
- `database/seeders/CreateOnly/Support/CreateTransactionMonthNormalItemFactory.php`

Changed file:

- `mk/seed.mk`

Implementation decision:

- Keep `CreateTransactionWeekSeeder` as minimal weekly characterization seed.
- Add separate monthly-normal transaction seed instead of overloading weekly seed.
- Monthly-normal seed must still use `App\Application\Note\UseCases\CreateTransactionWorkspaceHandler`.
- Do not raw-insert notes, work_items, customer_payments, or projections.

Local syntax and line-count proof:

```text
No syntax errors detected in database/seeders/CreateOnly/CreateTransactionMonthNormalSeeder.php
No syntax errors detected in database/seeders/CreateOnly/Support/CreateTransactionMonthNormalPayloadFactory.php
No syntax errors detected in database/seeders/CreateOnly/Support/CreateTransactionMonthNormalItemFactory.php
  92 database/seeders/CreateOnly/CreateTransactionMonthNormalSeeder.php
  71 database/seeders/CreateOnly/Support/CreateTransactionMonthNormalPayloadFactory.php
  56 database/seeders/CreateOnly/Support/CreateTransactionMonthNormalItemFactory.php
 219 total
```

Make target proof:

```text
116:.PHONY: seed-transaction-month-normal
117:seed-transaction-month-normal:
118:    php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthNormalSeeder'
141:seed-create-all-v3: ... seed-transaction-week seed-transaction-month-normal
171:    @echo "  make seed-transaction-month-normal Source-only transaction notes monthly normal seed"
```

Standalone local seed proof:

```text
php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthNormalSeeder'

INFO  Seeding database.

create-only transaction month-normal notes: planned=28 created=28 replayed=0
```

Standalone operational profit proof after monthly-normal seed:

```text
notes_count = 34
notes_total_sum = 28125000
customer_payments_sum = 26650000
external_purchase_lines_sum = 1720000
store_stock_cogs_stock_out_sum = 229248
cash_operational_profit_rupiah = 6863252
```

Interpretation:

Monthly-normal seed changes operational profit from negative to positive without patching the reporting query.
Monthly-normal seed is owner-readable for June 2026 report sanity.
Weekly seed remains available as small characterization coverage.

### REPORT-SANITY-001 - Clean create-all-v3 aggregate proof after monthly-normal seed

Local command sequence:

```text
php artisan migrate:fresh --seed
make create-all-v3
php artisan tinker --execute='... operational profit sanity query ...'
```

Visible local output from make create-all-v3:

```text
create-only transaction week notes: planned=6 created=6 replayed=0
create-only transaction month-normal notes: planned=28 created=28 replayed=0
Procurement projection: 24/24
Supplier projection: 78/78
Note projection: 34/34
Projection rebuild selesai.
```

Aggregate sanity output:

```text
notes = 34
work_items = 34
customer_payments = 31
note_history_projection = 34

notes_total_sum = 28125000
customer_payments_sum = 26650000
external_purchase_lines_sum = 1720000
store_stock_cogs_stock_out_sum = 229248

cash_in_rupiah = 26650000
refunded_rupiah = 0
external_purchase_cost_rupiah = 1720000
store_stock_cogs_rupiah = 229248
product_purchase_cost_rupiah = 1949248
operational_expense_rupiah = 3262500
payroll_disbursement_rupiah = 7525000
employee_debt_cash_out_rupiah = 7050000
cash_operational_profit_rupiah = 6863252
```

Interpretation:

make create-all-v3 now creates weekly + monthly-normal transaction seeds.
Note projection rebuild is aligned: 34/34.
Operational profit sanity is positive after aggregate seed.

### REPORT-EXPORT-001 - Operational profit PDF/XLSX proof

Local generated files:

```text
storage/app/report-proof/laporan-laba-kas-operasional-2026-06-01-sampai-2026-06-30.pdf
storage/app/report-proof/laporan-laba-kas-operasional-2026-06-01-sampai-2026-06-30.xlsx
```

PDF proof:

```text
exists = true
size_bytes = 19695
header = %PDF
html_contains_profit_label = true
html_contains_positive_profit_value = true
```

XLSX proof:

```text
exists = true
size_bytes = 6607
sheet_names = ["Ringkasan"]
title_A1 = "Laporan Laba Kas Operasional"
period_B2 = "01 Juni 2026 s/d 30 Juni 2026"
profit_B14 = 6863252
```

Dataset row used by export:

```text
from_date = 2026-06-01
to_date = 2026-06-30
cash_in_rupiah = 26650000
refunded_rupiah = 0
external_purchase_cost_rupiah = 1720000
store_stock_cogs_rupiah = 229248
product_purchase_cost_rupiah = 1949248
operational_expense_rupiah = 3262500
payroll_disbursement_rupiah = 7525000
employee_debt_cash_out_rupiah = 7050000
cash_operational_profit_rupiah = 6863252
```

Interpretation:

Operational profit report is positive in raw query, PDF artifact, and XLSX artifact.
PDF header proves file is a rendered PDF.
XLSX cell B14 proves workbook contains the positive profit amount as a numeric cell.

### VERIFY-001 - Full verification after monthly-normal seed and report proof

Local command:

```text
make verify
```

Final local output:

```text
Tests:    2 skipped, 1140 passed (6466 assertions)
Duration: 83.84s
```

Interpretation:

Current seed/report changes pass the project verification target.
No verification failure is known after monthly-normal seed and report export proof.


### SEED-TXN-004 - Monthly-normal 100M transaction seed profile

Added files:

- `database/seeders/CreateOnly/CreateTransactionMonthNormal100MSeeder.php`
- `database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MPayloadFactory.php`
- `database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MItemFactory.php`

Changed file:

- `mk/seed.mk`

Implementation decision:

- Keep `CreateTransactionMonthNormalSeeder` as the existing small owner-readable sanity profile.
- Add separate `CreateTransactionMonthNormal100MSeeder` as the monthly normal 100-200 juta profile.
- Do not mutate `seed-create-all-v3`.
- Add separate make targets:
  - `seed-transaction-month-normal-100m`
  - `seed-create-all-month-normal-100m`
  - `create-all-month-normal-100m`
- 100M profile still uses `App\Application\Note\UseCases\CreateTransactionWorkspaceHandler`.
- Do not raw-insert notes, work_items, customer_payments, or projections.

Local syntax and line-count proof:

```text
No syntax errors detected in database/seeders/CreateOnly/CreateTransactionMonthNormal100MSeeder.php
No syntax errors detected in database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MPayloadFactory.php
No syntax errors detected in database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MItemFactory.php
  91 database/seeders/CreateOnly/CreateTransactionMonthNormal100MSeeder.php
  97 database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MPayloadFactory.php
  80 database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MItemFactory.php
```

Make target proof:

```text
120:.PHONY: seed-transaction-month-normal-100m
121:seed-transaction-month-normal-100m:
122:    php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthNormal100MSeeder'
152:.PHONY: seed-create-all-month-normal-100m
153:seed-create-all-month-normal-100m: seed-create-all-v3 seed-transaction-month-normal-100m
155:.PHONY: create-all-month-normal-100m
156:create-all-month-normal-100m: seed-create-all-month-normal-100m
194:    @echo "  make create-all-month-normal-100m Run dataset v3 plus monthly normal 100M, then audit baseline and rebuild projections once"
```

Standalone local seed proof:

```text
notes_total = 90
notes_100m_by_customer = 90
notes_100m_by_note = 90
work_items_total = 90
customer_payments_total = 84
store_stock_lines_total = 45
external_purchase_lines_total = 18
notes_total_sum = 154800000
customer_payments_sum = 140600000
```

Aggregate local proof after create-all-month-normal-100m:

```text
notes = 124
work_items = 124
customer_payments = 115
note_history_projection = 124
notes_total_sum = 182925000
customer_payments_sum = 167250000
```

Interpretation:

Monthly normal 100-200 juta profile is now implemented and locally proven.
Aggregate cash-in is Rp 167.250.000.
Note projection is aligned: 124/124.
Existing create-all-v3 remains intact as the smaller owner-readable sanity profile.

### REPORT-SANITY-002 - Operational profit sanity for monthly-normal 100M profile

Local aggregate report sanity output:

```text
success = true
notes = 124
work_items = 124
customer_payments = 115
note_history_projection = 124
from_date = 2026-06-01
to_date = 2026-06-30
cash_in_rupiah = 167250000
refunded_rupiah = 0
external_purchase_cost_rupiah = 34120000
store_stock_cogs_rupiah = 3209472
product_purchase_cost_rupiah = 37329472
operational_expense_rupiah = 3262500
payroll_disbursement_rupiah = 7525000
employee_debt_cash_out_rupiah = 7050000
cash_operational_profit_rupiah = 112083028
```

Interpretation:

Operational profit report is positive for the monthly normal 100M profile.
Cash operational profit is Rp 112.083.028.
Refund remains zero for create-only profile.

### REPORT-EXPORT-002 - Operational profit PDF/XLSX proof for monthly-normal 100M profile

Local generated files:

```text
storage/app/report-proof/laporan-laba-kas-operasional-100m-2026-06-01-sampai-2026-06-30.pdf
storage/app/report-proof/laporan-laba-kas-operasional-100m-2026-06-01-sampai-2026-06-30.xlsx
```

PDF/XLSX proof:

```text
success = true
from_date = 2026-06-01
to_date = 2026-06-30
pdf_exists = true
pdf_size_bytes = 19703
pdf_header = %PDF
html_contains_profit_value = true
xlsx_exists = true
xlsx_size_bytes = 6608
sheet_title = Ringkasan
title_A1 = Laporan Laba Kas Operasional
period_B2 = 01 Juni 2026 s/d 30 Juni 2026
profit_B14 = 112083028
```

Interpretation:

PDF artifact exists and has a valid %PDF header.
XLSX artifact exists.
XLSX cell B14 contains the numeric profit amount 112083028.
Export proof matches the 100M operational profit sanity value.

### VERIFY-002 - Full verification after monthly-normal 100M seed and report proof

Local command:

```text
make verify
```

Visible final local output:

```text
Tests:    2 skipped, 1140 passed (6466 assertions)
Duration: 83.03s
```

Interpretation:

Test suite remains green after the monthly normal 100M seed/report changes.
This proof records only the visible final output pasted in the session.

## Bugs Encountered And Resolved

### BUG-001 - Role constant mismatch

Failure:

Undefined constant App\Core\IdentityAccess\Role\Role::CASHIER

Cause:

Role class has Role::KASIR, not Role::CASHIER.

Fix:

Use Role::KASIR.

### BUG-002 - External package component amount zero

Failure:

Create transaction week seed failed: Amount komponen harus > 0.

Cause:

External package auto-split composer expects external_purchase_lines.0.total_rupiah for package mode.
Payload using qty/unit_cost without total_rupiah did not trigger package residual composition.

Fix:

External package payload must provide total_rupiah for package auto split external purchase.

Final proof after fix is GREEN.

## Current Progress Estimate

Existing CreateOnly seed stabilization:

99%

Reason:

human-facing create-all-v3 is wired and locally proven.
weekly transaction seed remains GREEN.
monthly-normal transaction seed is added and locally proven.
operational profit sanity is positive for June 2026.
PDF/XLSX operational profit report artifacts are locally proven.
full make verify is GREEN.

Monthly normal 100-200 juta profile:

100%

Reason:

standalone monthly-normal 100M transaction seed is locally proven.
aggregate create-all-month-normal-100m is locally proven.
cash-in is Rp 167.250.000.
note projection is aligned at 124/124.
operational profit sanity is positive at Rp 112.083.028.
PDF/XLSX export proof is locally proven.
test suite remains GREEN after the change.

Full serious create-all seed system:

84-86%

Reason full system is not higher:

peak 500 juta/month dataset is not implemented.
stress 6-8 miliar/month dataset is not implemented.
refund scaffold is not implemented.
full lifecycle closure is not done.

## GAP

No peak 500 juta/month dataset.
No stress 6-8 miliar/month dataset.
No refund scaffold.
No full lifecycle closure.

## DECISION

Do not patch operational profit reporting query.

Reason:

operational profit arithmetic was locally proven consistent.
the earlier negative result was caused by transaction seed size being too small versus monthly fixed/cash-out seed.
monthly-normal transaction seed made the report positive without modifying report logic.
monthly-normal 100M profile also produced positive report output without modifying report logic.

Keep CreateTransactionWeekSeeder as minimal weekly characterization seed.

Keep CreateTransactionMonthNormalSeeder as the June 2026 owner-readable small sanity profile.

Use CreateTransactionMonthNormal100MSeeder as the June 2026 monthly normal 100-200 juta profile.

Keep create-all-v3 unchanged.

Use create-all-month-normal-100m for the monthly normal 100-200 juta aggregate profile.

Do not start peak, stress, or refund scaffold in the same step as the 100M profile.

## Peak 500M Interim Proof And Open PR

### SEED-TXN-005 - Peak 500M standalone and aggregate proof

Files added in current session:

- `database/seeders/CreateOnly/CreateTransactionMonthPeak500MSeeder.php`
- `database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MPayloadFactory.php`
- `database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MItemFactory.php`

Make targets added:

- `seed-transaction-month-peak-500m`
- `seed-create-all-month-peak-500m`
- `create-all-month-peak-500m`

Local syntax and line-count proof:

```text
No syntax errors detected in database/seeders/CreateOnly/CreateTransactionMonthPeak500MSeeder.php
No syntax errors detected in database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MPayloadFactory.php
No syntax errors detected in database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MItemFactory.php
  88 database/seeders/CreateOnly/CreateTransactionMonthPeak500MSeeder.php
  90 database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MPayloadFactory.php
  80 database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MItemFactory.php
```

Standalone seed replay proof:

```text
create-only transaction month-peak-500m notes: planned=280 created=280 replayed=0
create-only transaction month-peak-500m notes: planned=280 created=0 replayed=280
```

Standalone count proof:

```text
notes_total = 280
notes_peak_500m_by_customer = 280
notes_peak_500m_by_note = 280
work_items_total = 280
customer_payments_total = 264
store_stock_lines_total = 170
external_purchase_lines_total = 70
notes_total_sum = 576000000
customer_payments_sum = 523600000
```

Aggregate projection proof:

```text
Supplier projection: 78/78
Note projection: 200/314
Note projection: 314/314
Projection rebuild selesai.
```

Aggregate count proof:

```text
notes = 314
work_items = 314
customer_payments = 295
note_history_projection = 314
notes_peak_500m_by_customer = 280
notes_peak_500m_by_note = 280
store_stock_lines_total = 181
external_purchase_lines_total = 78
notes_total_sum = 604140000
customer_payments_sum = 550265000
```

Interpretation:

Peak 500M transaction seed is implemented.
Standalone seed is replay-safe after removing mutable qty_on_hand >= 20 product selection.
Aggregate profile reaches Rp 550.265.000 cash-in.
Note projection is aligned at 314/314.
Blueprint expected values differed by Rp 15.000; local output is source of truth.

### REPORT-SANITY-003 - Peak 500M operational profit sanity, not closed

Local handler-based output:

```text
success = true
notes = 314
work_items = 314
customer_payments = 295
note_history_projection = 314
from_date = 2026-06-01
to_date = 2026-06-30
cash_in_rupiah = 550265000
refunded_rupiah = 0
external_purchase_cost_rupiah = 99720000
store_stock_cogs_rupiah = 13954444
product_purchase_cost_rupiah = 113674444
operational_expense_rupiah = 0
payroll_disbursement_rupiah = 0
employee_debt_cash_out_rupiah = 0
cash_operational_profit_rupiah = 436590556
```

Interpretation:

Peak report handler returns positive cash operational profit.
Peak cash-in is above 500 juta.
However, non-transaction cash-out values are zero:

```text
operational_expense_rupiah = 0
payroll_disbursement_rupiah = 0
employee_debt_cash_out_rupiah = 0
```

This differs from the earlier 100M profile proof, where fixed cash-out values were present.
Peak report sanity is therefore not fully closed until this ambiguity is investigated.

### VERIFY-003 - Full verification after peak 500M changes

Local command:

```text
make verify
```

Visible local output:

```text
phpstan: [OK] No errors
audit-lines: SUCCESS
Blade PHP audit: SUCCESS
Contract audit passed
Tests: 2 skipped, 1140 passed (6466 assertions)
Duration: 82.39s
```

Interpretation:

Static analysis, audits, and test suite remain green after peak 500M changes.

### PR-PEAK-001 - Investigate zero non-transaction cash-out in peak report sanity

Problem:

Peak 500M operational profit sanity returned:

```text
operational_expense_rupiah = 0
payroll_disbursement_rupiah = 0
employee_debt_cash_out_rupiah = 0
```

But earlier 100M profile proof returned non-zero fixed cash-out values.

This may mean one of:

- peak aggregate command did not include non-transaction source seed in the actual local run;
- non-transaction rows exist but their dates do not fall into 2026-06-01 through 2026-06-30;
- report handler filters a different operational date field than expected;
- local DB state used for report sanity does not match the intended full aggregate state.

Required next proof:

Count and sum operational expenses, payroll disbursements, and employee debt payments by date.
Compare source-table values against GetOperationalProfitSummaryHandler output.
Decide whether seed date alignment or aggregate target dependency needs patching.

### PR-PEAK-002 - Clarify productless notes / service-only seed shape

Question raised:

Create nota may be understood as always taking products, but current create transaction seed supports productless notes for service-only and external-purchase flows.

Current interpretation:

A note with service-only work item and blank product_lines is technically valid.
A note with external purchase may have blank store-stock product_lines but non-empty external_purchase_lines.
Store-stock and package rows are the product-backed flows.

Open decision:

Confirm whether owner-readable peak dataset may contain productless service-only notes.
If productless notes confuse UI/report review, adjust seed mix or add clearer owner-readable labeling.
Do not treat productless notes as a runtime bug until the domain expectation is explicitly locked.

Required next proof:

Query peak notes by item shape:

- service-only count;
- store-stock count;
- external purchase count;
- package count;
- notes with no store-stock product line;
- notes with neither store-stock nor external purchase.

Decide whether the peak profile remains valid or needs seed-shape adjustment.

## Active Blueprint Promotion

Peak/stress scale planning was promoted from chat-only planning into:

docs/03_blueprints/seeder/0001_create_only_seed_scale_profiles.md

Purpose:

keep create-only seed scale profiles auditable outside the chat session;
preserve existing small and 100M profile proof;
define peak 500 juta/month as the next implementation target;
reserve stress 6-8 miliar/month and 10 miliar/month as separate future targets;
avoid mixing refund scaffold into create-only scale profile work.

Current blueprint status:

L0 create-all-v3: implemented/proven
L1 create-all-month-normal-100m: implemented/proven
L2 create-all-month-peak-500m: planned next
L3 create-all-month-stress-8b: planned later
L4 create-all-month-stress-10b: planned later
L5 refund scaffold: planned separately


## NEXT ACTIVE STEP

Investigate peak 500M report and seed-shape ambiguity.

Goal:

Close PR-PEAK-001 by proving why non-transaction cash-out is zero in peak report sanity.
Close or classify PR-PEAK-002 by proving whether productless notes are valid owner-readable seed rows.
Do not continue to PDF/XLSX export proof until the ambiguity is classified.

Scope:

Query source tables for operational expenses, payroll disbursements, and employee debt payments by date.
Query peak note/work item shape distribution.
Compare source rows against GetOperationalProfitSummaryHandler output.
Decide whether peak seed shape or aggregate target needs patching.

Do not start stress 6-8 miliar/month, 10 miliar/month, refund scaffold, or report wording patch in the same step.

## Opening Prompt For Next Session

Baca rules dulu sebelum jawab atau patch:

docs/04_lifecycle/handoff/README.md
docs/01_standards/0005_handoff_template.md
docs/01_standards/core/0010_scope_and_facts.md
docs/01_standards/core/0011_blueprint_first.md
docs/01_standards/core/0012_step_by_step_execution.md
docs/01_standards/core/0013_proof_and_progress.md
docs/01_standards/workflow/0020_response_structure.md
docs/01_standards/workflow/0021_active_step_policy.md
docs/01_standards/output/0033_terminal_command_delivery.md

Baca handoff aktif:

docs/04_lifecycle/handoff/0015_create_only_seed_system_stabilization_handoff.md

Cara kerja wajib:

Local command output adalah source of truth tertinggi.
Jangan mengarang file, status repo, hasil test, atau hasil command.
Gunakan struktur FACT / REFERENCES / SCOPE-IN / SCOPE-OUT / GAP / DECISION / BLUEPRINT / WORKFLOW / ACTIVE STEP / PROOF / NEXT / PROGRESS.
Blueprint-first sebelum implementasi.
Satu response hanya satu active step.
Jangan patch sebelum active scope jelas.
Jangan bahas git kecuali diminta eksplisit.

Status terakhir yang sudah terbukti dan tercatat:

CreateOnly seed stabilization progress: 99%.
Monthly normal 100-200 juta profile: 100%.
Full serious create-all seed system progress: 80-82%.
Existing create-all-v3 remains the small owner-readable sanity profile.

New 100M profile files:

database/seeders/CreateOnly/CreateTransactionMonthNormal100MSeeder.php
database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MPayloadFactory.php
database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MItemFactory.php

New make targets:

seed-transaction-month-normal-100m
seed-create-all-month-normal-100m
create-all-month-normal-100m

Standalone 100M seed proof:

notes_total = 90
work_items_total = 90
customer_payments_total = 84
notes_total_sum = 154800000
customer_payments_sum = 140600000

Aggregate 100M proof:

notes = 124
work_items = 124
customer_payments = 115
note_history_projection = 124
notes_total_sum = 182925000
customer_payments_sum = 167250000

Operational profit sanity 100M:

cash_in_rupiah = 167250000
refunded_rupiah = 0
product_purchase_cost_rupiah = 37329472
cash_operational_profit_rupiah = 112083028

PDF/XLSX export proof:

PDF exists true, header %PDF, contains Rp 112.083.028
XLSX exists true, sheet Ringkasan, period 01 Juni 2026 s/d 30 Juni 2026, profit_B14 = 112083028

make verify visible final proof:

Tests: 2 skipped, 1140 passed, 6466 assertions.
Duration: 83.03s.

Mulai dari NEXT ACTIVE STEP di handoff:

Investigate peak 500M report and seed-shape ambiguity.

Target sesi berikutnya:

Baca active blueprint:
docs/03_blueprints/seeder/0001_create_only_seed_scale_profiles.md
Jangan patch make target dulu.
Patch hanya tiga file peak 500M seeder/factory:
database/seeders/CreateOnly/CreateTransactionMonthPeak500MSeeder.php
database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MPayloadFactory.php
database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MItemFactory.php
Jalankan syntax dan line-count proof.
Jangan mulai stress 6-8 miliar/month, 10 miliar/month, refund scaffold, atau report wording patch di step yang sama.
Setelah 3 file peak seeder valid, baru next response boleh wire make target.

Additional current-session facts to preserve:

Peak 500M aggregate proof:

notes = 314
work_items = 314
customer_payments = 295
note_history_projection = 314
notes_total_sum = 604140000
customer_payments_sum = 550265000

Peak 500M report sanity:

cash_in_rupiah = 550265000
product_purchase_cost_rupiah = 113674444
operational_expense_rupiah = 0
payroll_disbursement_rupiah = 0
employee_debt_cash_out_rupiah = 0
cash_operational_profit_rupiah = 436590556

make verify:

phpstan [OK] No errors
audit-lines SUCCESS
contract audit passed
Tests: 2 skipped, 1140 passed, 6466 assertions

Open PR:

PR-PEAK-001: investigate zero non-transaction cash-out in peak report sanity.
PR-PEAK-002: clarify productless notes / service-only seed shape.

### PEAK-500M-CLOSURE-001 - Peak 500M PR classification and export proof

Local date: 2026-06-02.

Scope:

- Close PR-PEAK-001.
- Close PR-PEAK-002.
- Prove peak 500M operational profit PDF/XLSX export.
- Do not start stress 6-8B.
- Do not start 10B.
- Do not start refund scaffold.

#### PR-PEAK-001 classification

Problem:

Peak 500M report sanity previously returned zero fixed cash-out:

```text
operational_expense_rupiah = 0
payroll_disbursement_rupiah = 0
employee_debt_cash_out_rupiah = 0
```

Investigation result:

The first probe showed the DB state had transaction rows but no non-transaction source rows:

```text
notes = 314
customer_payments = 295
operational_expenses = 0
payroll_disbursements = 0
employee_debts = 0
employee_debt_payments = 0
```

Make target inspection showed seed-create-all-month-peak-500m already depends on:

```text
seed-create-all-v3
seed-transaction-month-peak-500m
```

and seed-create-all-v3 already depends on the non-transaction seeds:

```text
seed-expense
seed-employee-debt
seed-employee-debt-payment
seed-employee-debt-adjustment
seed-payroll-disbursement
```

Reproduction with full target:

```text
php artisan migrate:fresh --seed
make create-all-month-peak-500m
```

Local output proved non-transaction source rows are present after the correct full target:

```text
operational_expenses = 45
payroll_disbursements = 6
employee_debts = 13
employee_debt_payments = 6
notes = 314
customer_payments = 295
```

June handler-aligned sums:

```text
operational_expense_rupiah = 3262500
payroll_disbursement_rupiah = 7525000
employee_debt_cash_out_rupiah = 7050000
employee_debt_payments_sum_check_only = 1600000
```

Handler output:

```text
success = true
operational_expense_rupiah = 3262500
payroll_disbursement_rupiah = 7525000
employee_debt_cash_out_rupiah = 7050000
cash_operational_profit_rupiah = 418778307
```

Decision:

PR-PEAK-001 CLOSED.
Root cause was prior local DB state/run-path mismatch.
No report handler patch required.
No make dependency patch required.

Important clarification:

The operational profit handler reads employee debt cash-out from employee_debts.created_at and employee_debts.total_debt, not from employee_debt_payments.payment_date.

#### PR-PEAK-002 classification

Problem:

Peak dataset contains service-only / productless notes.

Local shape probe output:

```text
peak_note_total = 280
peak_work_item_total = 280
peak_total_rupiah = 576000000
```

Actual shape distribution:

```text
service_only_actual_productless = 80
store_stock_actual = 90
external_purchase_actual = 70
package_store_stock_actual = 40
```

Expected segment distribution:

```text
service_only_expected = 80
store_stock_expected = 90
external_purchase_expected = 70
package_store_stock_expected = 40
```

Segment-vs-actual mapping:

```text
service_only_expected -> service_only_actual_productless = 80
store_stock_expected -> store_stock_actual = 90
external_purchase_expected -> external_purchase_actual = 70
package_store_stock_expected -> package_store_stock_actual = 40
```

Line totals:

```text
notes_with_store_stock_line = 130
notes_with_external_purchase_line = 70
notes_with_no_store_stock_line = 150
notes_with_no_external_purchase_line = 210
notes_with_neither_store_stock_nor_external_purchase = 80
notes_with_both_store_stock_and_external_purchase = 0
store_stock_line_rows = 170
store_stock_qty = 260
store_stock_total_rupiah = 57875000
external_purchase_line_rows = 70
external_purchase_qty = 70
external_purchase_total_rupiah = 98000000
```

Mismatch result:

```text
mismatches = []
```

Decision:

PR-PEAK-002 CLOSED.
Productless notes are intentional service-only notes.
This is not a runtime bug.
No report handler patch required.
No create transaction runtime patch required.
No seed-shape patch required for technical correctness.

#### Peak 500M export proof

Command proof script:

```text
/tmp/hyperpos_peak_500m_export_proof_v2.php
```

Script sanity:

```text
109 /tmp/hyperpos_peak_500m_export_proof_v2.php
No syntax errors detected in /tmp/hyperpos_peak_500m_export_proof_v2.php
```

Dataset used by export:

```text
success = true
from_date = 2026-06-01
to_date = 2026-06-30
notes = 314
customer_payments = 295
note_history_projection = 314
cash_in_rupiah = 550250000
refunded_rupiah = 0
external_purchase_cost_rupiah = 99720000
store_stock_cogs_rupiah = 13914193
product_purchase_cost_rupiah = 113634193
operational_expense_rupiah = 3262500
payroll_disbursement_rupiah = 7525000
employee_debt_cash_out_rupiah = 7050000
cash_operational_profit_rupiah = 418778307
```

PDF proof:

```text
path = /home/asyraf/Code/laravel/bengkel2/app/storage/app/report-proof/laporan-laba-kas-operasional-peak-500m-2026-06-01-sampai-2026-06-30.pdf
exists = true
size_bytes = 19708
header = %PDF
html_contains_title = true
html_contains_profit_label = true
html_contains_profit_value = true
html_contains_operational_expense_value = true
html_contains_payroll_value = true
html_contains_employee_debt_value = true
```

XLSX proof:

```text
path = /home/asyraf/Code/laravel/bengkel2/app/storage/app/report-proof/laporan-laba-kas-operasional-peak-500m-2026-06-01-sampai-2026-06-30.xlsx
exists = true
size_bytes = 6614
sheet_names = ["Ringkasan"]
title_A1 = Laporan Laba Kas Operasional
period_B2 = 01 Juni 2026 s/d 30 Juni 2026
cash_in_B6 = 550250000
external_purchase_B8 = 99720000
store_stock_cogs_B9 = 13914193
product_purchase_cost_B10 = 113634193
operational_expense_B11 = 3262500
payroll_B12 = 7525000
employee_debt_B13 = 7050000
profit_B14 = 418778307
profit_B14_matches_handler = true
```

Generated artifacts:

```text
storage/app/report-proof/laporan-laba-kas-operasional-peak-500m-2026-06-01-sampai-2026-06-30.pdf
storage/app/report-proof/laporan-laba-kas-operasional-peak-500m-2026-06-01-sampai-2026-06-30.xlsx
```

Decision:

Peak 500M operational profit report sanity and export proof are CLOSED.

#### Progress after this closure

```text
CreateOnly seed stabilization = 99%
Monthly normal 100M profile = 100%
Peak 500M profile = 100%
Full serious create-all seed system = 91-93%
```

#### Next Step

The next valid active step is to decide the next scale target:

```text
Start stress 6-8 miliar/month blueprint execution.
```

Do not start refund scaffold before the stress profile boundary is explicitly opened.

### STRESS-8B-001 - Stress 6-8B blueprint, seeder, standalone proof, and make target wiring

Local date: 2026-06-02.

Scope:

- Start stress 6-8 miliar/month execution after Peak 500M closure.
- Promote stress 8B placeholder into executable blueprint.
- Prove inventory capacity before writing seeder files.
- Patch only stress 8B seeder files.
- Prove standalone stress seeder.
- Patch `mk/seed.mk` only after standalone seeder proof.
- Do not start 10B.
- Do not start refund scaffold.
- Do not claim stress 8B full closure yet.

#### Blueprint update

Blueprint file:

```text
docs/03_blueprints/seeder/0001_create_only_seed_scale_profiles.md
```

Stress 6-8B placeholder was promoted to executable blueprint.

Target:

```text
create-all-month-stress-8b
```

Planned stress seeder incremental target:

```text
notes = 3200
work_items = 3200
customer_payments = 2816
unpaid_notes = 384
partial_payment_notes = 576
gross_total_rupiah = 7820000000
cash_in_rupiah = 6539600000
```

Expected aggregate with seed-create-all-v3 baseline:

```text
notes = 3234
work_items = 3234
customer_payments = 2847
note_history_projection = 3234
notes_total_sum = 7848125000
customer_payments_sum = 6566250000
cash_in_rupiah = 6566250000
refunded_rupiah = 0
```

Stress item mix:

```text
service_only = 800
store_stock = 1000
external_purchase = 900
package_store_stock = 500
```

Stress payment mix:

```text
full = 2240
partial = 576
skip_unpaid = 384
```

Inventory capacity proof

Required:

```text
minimum_products = 40
required_store_stock_units = 3000
```

Local proof:

```text
product_count = 200
total_qty_on_hand = 6547
min_qty_on_hand = 13
max_qty_on_hand = 49
min_avg_cost_rupiah = 12250
max_avg_cost_rupiah = 273000
total_inventory_value_rupiah = 784994275
```

Decision:

```text
Inventory capacity PASS
can_issue_3000_units = true
has_minimum_40_products = true
```

Seeder files patched

Files added:

```text
database/seeders/CreateOnly/CreateTransactionMonthStress8BSeeder.php
database/seeders/CreateOnly/Support/CreateTransactionMonthStress8BPayloadFactory.php
database/seeders/CreateOnly/Support/CreateTransactionMonthStress8BItemFactory.php
```

Syntax proof:

```text
No syntax errors detected in database/seeders/CreateOnly/CreateTransactionMonthStress8BSeeder.php
No syntax errors detected in database/seeders/CreateOnly/Support/CreateTransactionMonthStress8BPayloadFactory.php
No syntax errors detected in database/seeders/CreateOnly/Support/CreateTransactionMonthStress8BItemFactory.php
```

Line-count proof:

```text
94 database/seeders/CreateOnly/CreateTransactionMonthStress8BSeeder.php
99 database/seeders/CreateOnly/Support/CreateTransactionMonthStress8BPayloadFactory.php
73 database/seeders/CreateOnly/Support/CreateTransactionMonthStress8BItemFactory.php
```

Standalone stress seeder proof

```text
php artisan migrate:fresh --seed
make seed-create-all-v3
php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthStress8BSeeder'

create-only transaction month-stress-8b notes: planned=3200 created=3200 replayed=0
```

Aggregate after seed-create-all-v3 + stress:

```text
notes = 3234
work_items = 3234
customer_payments = 2847
notes_total_sum = 7848125000
customer_payments_sum = 6566250000
```

Decision:

```text
Standalone stress 8B seeder proof PASS
Stress 8B make target wiring PASS
```

Make target wiring

```text
mk/seed.mk
seed-transaction-month-stress-8b:
    php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthStress8BSeeder'

seed-create-all-month-stress-8b: seed-create-all-v3 seed-transaction-month-stress-8b

create-all-month-stress-8b: seed-create-all-month-stress-8b
    $(MAKE) seed-audit-baseline
    php artisan projection:rebuild-indexes all
```

Dry-run proof:

```text
make -n create-all-month-stress-8b
```

Decision:

```text
Current Status:
Stress 8B blueprint = done
Inventory capacity proof = done
Seeder files patch = done
Seeder syntax and line-count proof = done
Standalone seeder proof = done
Standalone source aggregate proof = done
mk/seed.mk target wiring = done
make dry-run proof = done
```

Not done yet:

```text
Full aggregate proof via make create-all-month-stress-8b
Projection count proof after full aggregate target
Operational profit sanity proof
PDF/XLSX export proof
make verify
Final handoff closure
```

Next Step:

```text
Run full aggregate stress 8B proof:

php artisan migrate:fresh --seed
make create-all-month-stress-8b

Then prove:

notes = 3234
work_items = 3234
customer_payments = 2847
note_history_projection = 3234
notes_total_sum = 7848125000
customer_payments_sum = 6566250000
cash_in_rupiah = 6566250000
refunded_rupiah = 0

Do not start 10B.
Do not start refund scaffold.
Do not claim stress 8B closed until full aggregate, report sanity, export proof, and verify are complete.
```

### STRESS-8B-PAUSE-001 - Pause extreme stress profile as non-blocking backlog

Local date: 2026-06-02.

#### Context

Stress 8B was implemented as an extreme create-only monthly stress profile after Peak 500M closure.

This profile is intentionally not promoted to normal workflow closure yet.

#### Work completed

Stress 8B completed work:

```text
Stress 8B blueprint promoted to executable profile.
Inventory capacity proof completed.
Seeder files created.
Seeder syntax proof completed.
Seeder line-count proof completed.
Standalone seeder proof completed.
Standalone source aggregate proof completed.
mk/seed.mk target wiring completed.
make dry-run proof completed.
```

#### Latest local execution proof

Executed:

```text
php artisan migrate:fresh --seed
make create-all-month-stress-8b
```

Visible output:

```text
create-only transaction month-stress-8b notes: planned=3200 created=3200 replayed=0
Create-only audit baseline seeded.
Rebuild projection procurement selesai.
Rebuild projection supplier selesai.
Note projection: 3234/3234
Rebuild projection note selesai.
Projection rebuild selesai.
```

#### What this proves

Stress 8B seeder is executable through the human-facing make target.
Stress 8B target can run after a fresh database reset.
Stress 8B target reaches audit baseline and projection rebuild.
Note projection rebuild reached the expected 3234 notes.

#### Still not proven

The following closure proofs were intentionally not completed:

```text
Final aggregate SQL proof after full make target.
Operational profit sanity proof.
PDF export proof.
XLSX export proof.
make verify proof.
Final Stress 8B closure.
```

#### Decision

Stress 8B is paused.
Stress 8B is not closed.
Stress 8B is kept as an extreme-load backlog profile.
Stress 8B must not block return to create transaction, report, edit, or refund work.
Do not start 10B from this branch of work.
Do not start refund scaffold as a continuation of Stress 8B.
Do not remove Stress 8B seeder files.
Do not route normal development through create-all-month-stress-8b.

#### Make target policy

```text
create-all-v3:
    normal small owner-readable sanity profile.

create-all-month-normal-100m:
    monthly normal 100M profile.

create-all-month-peak-500m:
    proven peak 500M profile.

create-all-month-stress-8b:
    extreme stress profile, executable but paused before final closure.
```

#### Next valid work direction

Return focus to application behavior work:

```text
create transaction
reports
edit/refund lifecycle
```

Stress 8B may be resumed only by explicitly reopening this backlog item and completing the missing closure proofs.

