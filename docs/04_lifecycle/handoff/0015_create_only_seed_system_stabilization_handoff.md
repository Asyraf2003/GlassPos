# CreateOnly Seed System Stabilization Handoff

## Status

Active handoff.

This handoff records CreateOnly seed system stabilization for manual QA and owner-readable reporting preparation.

This handoff does not close the full seed system.

## Source Of Truth

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

Full serious create-all seed system:

65-67%

Reason full system is not higher:

monthly normal 100-200 juta dataset is not implemented.
peak 500 juta/month dataset is not implemented.
stress 6-8 miliar/month dataset is not implemented.
refund scaffold is not implemented.
full lifecycle closure is not done.

## GAP

No monthly normal 100-200 juta dataset.
No peak profile.
No stress profile.
No refund scaffold.
No handoff closure after this update until local verification confirms the handoff patch.

## DECISION

Do not patch operational profit reporting query.

Reason:

operational profit arithmetic was locally proven consistent.
the negative result was caused by transaction seed size being too small versus monthly fixed/cash-out seed.
monthly-normal transaction seed made the report positive without modifying report logic.

Keep CreateTransactionWeekSeeder as minimal weekly characterization seed.

Use CreateTransactionMonthNormalSeeder as the June 2026 owner-readable report sanity profile.

Do not scale to monthly 100-200 juta, peak, stress, or refund scaffold in this step.

## NEXT ACTIVE STEP

Verify the handoff patch and inspect changed file summary.

Command:

```text
rg -n "SEED-TXN-003|REPORT-SANITY-001|REPORT-EXPORT-001|VERIFY-001|Current Progress Estimate|99%|65-67%|No monthly normal 100-200 juta|NEXT ACTIVE STEP" docs/04_lifecycle/handoff/0015_create_only_seed_system_stabilization_handoff.md

wc -l docs/04_lifecycle/handoff/0015_create_only_seed_system_stabilization_handoff.md
```

Expected rough shape:

SEED-TXN-003 exists.
REPORT-SANITY-001 exists.
REPORT-EXPORT-001 exists.
VERIFY-001 exists.
progress shows CreateOnly seed stabilization 99%.
full serious create-all seed system shows 65-67%.

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

- Local command output adalah source of truth tertinggi.
- Jangan mengarang file, status repo, hasil test, atau hasil command.
- Gunakan struktur FACT / GAP / DECISION / ACTIVE STEP / PROOF / NEXT.
- Blueprint-first sebelum implementasi.
- Satu response hanya satu active step.
- Jangan patch sebelum active scope jelas.
- Jangan bahas git kecuali diminta eksplisit.

Mulai dari NEXT ACTIVE STEP di handoff:
Verify the handoff patch and inspect changed file summary.

