# HANDOFF / ADR — SeederNew Finance Correctness Blueprint

Repo: Asyraf2003/bengkelnativejs  
Tanggal: 2026-04-26  
Area: migration + seeder precision + finance correctness  
Nama pendek dokumen: SeederNew Finance Blueprint  
Status: ACTIVE DECISION RECORD + NEXT SESSION HANDOFF

---

## 1. Executive Summary

Dokumen ini mengunci arah baru untuk migration + seeder + validasi keuangan.

Keputusan utama:

1. Seeder tidak boleh dipakai sebagai satu-satunya bukti logic bisnis benar.
2. Seeder harus menjadi deterministic scenario fixture.
3. Finance correctness harus dibuktikan dengan:
   - scenario matrix,
   - invariant tests,
   - reconciliation audit command,
   - DB constraints / migration audit,
   - accounting-grade hardening bertahap.
4. Tujuan akhirnya bukan sekadar "banyak data", tapi data yang bisa dipakai untuk:
   - analisis manual,
   - audit matematika bisnis,
   - laporan,
   - regression test,
   - validasi logic uang/stok/hutang/refund.

Working direction yang dipilih user:

- Opsi B: deterministic scenario matrix seeder.
- Opsi C: finance invariant test suite.
- Opsi D: reconciliation audit command.
- Opsi E / Opsi 3: accounting-grade hardening bertahap.

---

## 2. Source Data / Evidence From Current Session

### 2.1 Existing make target wiring

Makefile sudah punya target:

~~~text
make 1 -> SeedLevel1Seeder
make 2 -> SeedLevel2Seeder
make 3 -> SeedLevel3Seeder
~~~

### 2.2 Patch wiring yang sudah dilakukan sesi ini

Files patched:

~~~text
database/seeders/DatabaseSeeder.php
database/seeders/DatabaseLoadSeeder.php
database/seeders/SeedLevel2Seeder.php
database/seeders/SeedLevel3Seeder.php
database/seeders/Transaction/CustomerTransactionBaselineSeeder.php
~~~

Current intended wiring:

~~~text
DatabaseSeeder     -> SeedLevel2Seeder
DatabaseLoadSeeder -> SeedLevel3Seeder
~~~

### 2.3 Syntax proof already passed

Command already run:

~~~bash
php -l database/seeders/DatabaseSeeder.php
php -l database/seeders/DatabaseLoadSeeder.php
php -l database/seeders/SeedLevel2Seeder.php
php -l database/seeders/SeedLevel3Seeder.php
php -l database/seeders/Transaction/CustomerTransactionBaselineSeeder.php
~~~

Result:

~~~text
No syntax errors detected
~~~

### 2.4 make 1 proof

make 1 ran twice and audit stayed stable.

Observed audit:

~~~text
users total: 2
admin user count: 1
kasir user count: 1
actor_accesses total: 2
admin actor_access count: 1
kasir actor_access count: 1
admin cashier area active count: 1
admin transaction capability active count: 1
~~~

Conclusion:

~~~text
make 1 = PASS + idempotent for account/access scope
~~~

### 2.5 make 2 first run proof

make 2 first run passed after wiring patch.

Observed audit:

~~~text
SI-BL invoices: 69
SI-BL versions: 69
SI-BL projections: 69
scenario invoices active: 5
void scenario invoices total: 3
SI-VOID-001 voided: 1
SI-VOID-REUSE-001 voided: 1
SI-VOID-REUSE-001 active: 1

baseline notes: 240
baseline customer payments: 216
baseline payment allocations: 216
baseline refunds: 12

baseline expenses: 120
expense categories: 6

orphan supplier invoice lines: 0
orphan supplier receipt lines: 0
orphan payment allocations: 0
duplicate active supplier invoice normalized no: 0
~~~

Conclusion:

~~~text
make 2 first run = PASS
make 2 idempotency = NOT YET PASS
~~~

### 2.6 make 2 rerun failure

make 2 rerun failed at:

~~~text
Database\Seeders\Transaction\CustomerTransactionBaselineSeeder
~~~

Error:

~~~text
SQLSTATE[23000]: Integrity constraint violation: 1451
Cannot delete or update a parent row
fk_nme_note
note_mutation_events.note_id -> notes.id
~~~

Root cause:

~~~text
CustomerTransactionBaselineSeeder::purgeSeededTransactions()
deleted notes before deleting note_mutation_events / note_mutation_snapshots.
~~~

Patch already applied:

~~~text
Before deleting notes:
- collect note_mutation_events.id by note_id
- delete note_mutation_snapshots by note_mutation_event_id
- delete note_mutation_events by id
- then delete notes
~~~

Status:

~~~text
Syntax pass only.
Runtime rerun proof still pending.
~~~

### 2.7 Additional idempotency risks found

From audit after failed rerun:

~~~text
suppliers total: 50 -> 75
products total: 358 -> 366
products missing threshold active: 0 -> 4
baseline customer payments: 216 -> 0
baseline refunds: 12 -> 0
~~~

Interpretation:

1. SupplierSeeder is likely not idempotent because it creates suppliers using generated UUID through writer.
2. ProductSeeder has likely idempotency gaps, especially soft-deleted/recreated scenarios.
3. Failed rerun leaves partial state because payment/refund purge already happened before transaction baseline failed.
4. make 2 must not be considered final until rerun is stable.

### 2.8 Supplier invoice versioning seeder strategy from previous handoff

Already completed before this document:

~~~text
SupplierInvoiceBaselineSeeder:
- create via CreateSupplierInvoiceFlowHandler
- autoRec=false
- PASS + idempotent

SupplierInvoiceScenarioSeeder:
- invoice creation via CreateSupplierInvoiceFlowHandler
- autoRec=false
- receipt/payment/proof still direct insert temporarily
- PASS + idempotent

SupplierInvoiceVoidedScenarioSeeder:
- invoice creation via CreateSupplierInvoiceFlowHandler
- void via VoidSupplierInvoiceHandler
- active reuse via CreateSupplierInvoiceFlowHandler
- PASS + idempotent
~~~

Deferred:

~~~text
Full versioned void lifecycle
Receipt/payment/proof full system path
~~~

---

## 3. Problem Statement

User needs seeders and migrations that support manual and automated analysis of finance logic.

The risk is not merely missing data.

The real risks are:

1. Business math is wrong.
2. Seeder creates misleading data.
3. Seeder rerun corrupts or duplicates rows.
4. Migration constraints allow invalid money states.
5. Reports show numbers that do not reconcile with source of truth.
6. Manual analysis cannot distinguish:
   - logic bug,
   - seed bug,
   - report query bug,
   - stale partial data from failed rerun.

For finance, this is unacceptable.

---

## 4. Decision

### ADR-2026-04-26-SEEDERNEW-001

Adopt SeederNew Finance Correctness Strategy.

SeederNew is not a single file. It is a layered strategy:

~~~text
Layer 1: deterministic scenario matrix seeder
Layer 2: finance invariant tests
Layer 3: reconciliation audit command
Layer 4: migration / constraint audit
Layer 5: accounting-grade hardening roadmap
~~~

Seeder remains useful, but only as fixture generator.

The source of truth for correctness must be:

~~~text
domain usecases
database constraints
finance invariant tests
reconciliation audit
report sanity checks
~~~

---

## 5. Non-Negotiable Rules

### 5.1 Work process rules

1. Zero assumption.
2. One active step per reply.
3. No direct GitHub modification.
4. User runs terminal commands from repo root.
5. No `git diff`.
6. No `set -euo pipefail`.
7. Patches must be sent as terminal commands.
8. For files, use `cat > file` or small scripted patch with explicit target.
9. Do not claim done without:
   - syntax proof,
   - runtime proof,
   - idempotency proof,
   - audit proof,
   - relevant test proof.

### 5.2 Seeder rules

1. make 1 must only seed account/access.
2. make 2 must seed deterministic normal 1 month data.
3. make 3 must seed deterministic extreme 1 year data.
4. All official seeders must be idempotent.
5. No random UUID for rerunnable master data unless deterministic key resolves existing row.
6. No random data for finance scenarios unless explicitly isolated as stress-only.
7. Scenario IDs must be stable and human-readable.
8. Seeder data must be traceable to scenario matrix.
9. Seeders must not silently skip required scenario unless audit reports the skip.
10. Failed rerun must not leave partial financial state as "passing".

### 5.3 Finance correctness rules

1. No finalized money mutation should be destructive without audit trail.
2. Refund must not exceed allocated payment.
3. Payment allocation must not exceed payment amount.
4. Note total must reconcile with work item components.
5. Supplier invoice total must reconcile with invoice lines.
6. Supplier payable must reconcile with invoice total minus supplier payments.
7. Inventory quantity must reconcile with stock movement source.
8. Inventory value / costing must reconcile with locked costing formula.
9. Voided supplier invoice must not create new stock or payable effect.
10. Paid supplier invoice must not imply received stock.
11. Reports must read from defined source of truth, not duplicate competing math.
12. Audit command must independently recompute key totals.

### 5.4 Migration / constraint rules

Migration cleanup must support:

1. FK integrity.
2. Unique constraints for active business keys where applicable.
3. Nullable fields only where domain permits.
4. No migration change that breaks existing finalized public contract without ADR.
5. No finance table delete/update policy change without audit impact.
6. If lifecycle is event-like, prefer append/reversal over destructive mutation.

---

## 6. Blueprint

### 6.1 Target make levels

#### make 1

Purpose:

~~~text
Minimal access/login dataset
~~~

Expected scope:

~~~text
users
actor_accesses
admin_cashier_area_access_states
admin_transaction_capability_states
~~~

Must not seed:

~~~text
products
suppliers
notes
payments
refunds
expenses
supplier invoices
inventory movements
~~~

Current status:

~~~text
PASS + idempotent for account/access scope
~~~

#### make 2

Purpose:

~~~text
Normal 1 month deterministic dataset for manual review and business-math analysis.
~~~

Expected domains:

~~~text
users/access
products
suppliers
employee finance
supplier invoice baseline/scenarios/void
expense baseline
customer transaction baseline
product inventory thresholds
customer payment baseline
customer refund baseline
customer correction baseline
~~~

Required condition:

~~~text
make 2 must run repeatedly with stable counts and no corruption.
~~~

Current status:

~~~text
First run PASS.
Rerun NOT PASS yet.
~~~

#### make 3

Purpose:

~~~text
Extreme 1 year deterministic dataset for stress reports, scale checks, and edge behavior.
~~~

Expected domains:

~~~text
users/access
products baseline + load
suppliers
employee finance
procurement load
inventory projection
expense load
customer transaction load
customer payment load
customer refund load
customer correction load
~~~

Required condition:

~~~text
make 3 must run repeatedly with stable counts and no corruption.
~~~

Current status:

~~~text
Not runtime-proven in this session.
Do not trust yet.
~~~

---

## 7. Scenario Matrix Blueprint

Create a future file:

~~~text
docs/handoff/v2/seedernew/2026-04-26-seedernew-scenario-matrix.md
~~~

Recommended matrix columns:

~~~text
Domain
Scenario ID
Scenario Name
Purpose
make level
Seeder class
Tables involved
Expected status
Expected money effect
Expected stock effect
Expected report effect
Invariant checks
Manual review path
Status
~~~

### 7.1 Customer transaction scenarios

Minimum required scenarios:

~~~text
CUST-001 note unpaid
CUST-002 note full paid single payment
CUST-003 note partial payment
CUST-004 note multi-payment full paid
CUST-005 note service only
CUST-006 note store stock only
CUST-007 note service + store stock
CUST-008 note service + external purchase
CUST-009 note mixed service + store stock + external purchase
CUST-010 partial refund
CUST-011 full refund
CUST-012 paid note status correction
CUST-013 paid service nominal correction
CUST-014 unpaid note remains outstanding
CUST-015 refund does not exceed allocated amount
~~~

### 7.2 Supplier / procurement scenarios

Minimum required scenarios:

~~~text
SUP-001 invoice draft/editable
SUP-002 invoice received unpaid
SUP-003 invoice paid pending proof
SUP-004 invoice paid uploaded proof
SUP-005 invoice received + paid full cycle
SUP-006 invoice voided before domain effect
SUP-007 invoice number reused after void
SUP-008 payment without receipt does not affect stock
SUP-009 receipt without payment affects stock but not paid status
SUP-010 supplier payable remains correct
SUP-011 annual dense invoice load
SUP-012 proof attachment scenario
~~~

### 7.3 Inventory / costing scenarios

Minimum required scenarios:

~~~text
INV-001 stock in from supplier receipt
INV-002 stock out from customer note
INV-003 average cost changes after procurement
INV-004 inventory value reconciles with qty and avg cost
INV-005 low stock threshold
INV-006 critical stock threshold
INV-007 no negative stock unless explicitly allowed by domain
INV-008 refund does not incorrectly restore stock unless policy says so
INV-009 void supplier invoice does not create inventory movement
INV-010 correction does not corrupt stock movement history
~~~

### 7.4 Expense scenarios

Minimum required scenarios:

~~~text
EXP-001 cash expense
EXP-002 transfer expense
EXP-003 category snapshot preserved
EXP-004 deleted expense excluded from report
EXP-005 1 month daily expense distribution
EXP-006 1 year heavy expense distribution
~~~

### 7.5 Employee finance scenarios

Minimum required scenarios:

~~~text
EMP-001 active weekly employee
EMP-002 active monthly employee
EMP-003 active daily employee
EMP-004 manual salary employee
EMP-005 inactive employee
EMP-006 employee debt unpaid
EMP-007 employee debt partially paid
EMP-008 employee debt fully paid
EMP-009 payroll disbursement report
EMP-010 debt remaining balance reconciliation
~~~

### 7.6 Cash / ledger scenarios

Minimum required scenarios:

~~~text
CASH-001 customer payment cash-in
CASH-002 refund cash-out
CASH-003 operational expense cash-out
CASH-004 payroll cash-out
CASH-005 supplier payment cash-out
CASH-006 correction event audit
CASH-007 cash ledger balance reconciles
~~~

---

## 8. Finance Invariant Test Blueprint

Future recommended test files:

~~~text
tests/Feature/Finance/CustomerMoneyInvariantTest.php
tests/Feature/Finance/SupplierMoneyInvariantTest.php
tests/Feature/Finance/InventoryCostInvariantTest.php
tests/Feature/Finance/EmployeeFinanceInvariantTest.php
tests/Feature/Finance/CashLedgerInvariantTest.php
tests/Feature/Finance/ReportReconciliationTest.php
~~~

### 8.1 Customer money invariants

Required checks:

~~~text
note total = sum payable components
payment allocation total per payment <= payment amount
payment allocation total per note <= note total unless explicit overpayment policy exists
refund total <= allocated amount
refund component allocation total = refund amount
outstanding = note total - allocated + refunded adjustment, based on locked policy
paid status matches allocation/refund lifecycle
~~~

### 8.2 Supplier money invariants

Required checks:

~~~text
supplier invoice grand total = sum supplier invoice lines
supplier payment total <= invoice total unless explicit overpayment policy exists
supplier payable = invoice total - supplier payment total
voided invoice is excluded from active payable
received stock does not mean paid
paid invoice does not mean received stock
payment proof status matches attachment state
~~~

### 8.3 Inventory/costing invariants

Required checks:

~~~text
product_inventory.qty_on_hand = sum inventory_movements.qty_delta by product
stock_in movement source exists
stock_out movement source exists
inventory_value_rupiah reconciles with costing policy
avg_cost_rupiah remains non-negative
stock_out cannot exceed available stock unless domain explicitly allows backorder/negative stock
~~~

### 8.4 Employee finance invariants

Required checks:

~~~text
employee debt remaining_balance = total_debt - sum debt payments
paid debt has remaining_balance = 0
unpaid debt has remaining_balance > 0
payroll disbursement amount is non-negative
inactive employee scenario is reportable but not treated as active by active-only reports
~~~

### 8.5 Cash / ledger invariants

Required checks:

~~~text
cash in total = customer payments
cash out total = refunds + supplier payments + expenses + payroll + debt disbursement if applicable
net cash = cash in - cash out
report totals match source tables
no orphan ledger source rows
~~~

---

## 9. Reconciliation Audit Command Blueprint

Future recommended artisan command:

~~~bash
php artisan audit:seed-level 1
php artisan audit:seed-level 2
php artisan audit:seed-level 3
php artisan audit:finance
~~~

Minimum output:

~~~text
== SCENARIO COVERAGE ==
missing scenarios: 0

== CUSTOMER MONEY ==
note total:
allocated payment:
refund total:
outstanding:
overallocated notes:
refund overflow:

== SUPPLIER MONEY ==
invoice total:
supplier payment total:
payable:
voided active payable leaks:
payment proof mismatch:

== INVENTORY ==
computed qty:
stored qty:
qty mismatch:
computed value:
stored value:
value mismatch:

== EMPLOYEE FINANCE ==
debt total:
debt paid:
remaining balance:
remaining mismatch:

== CASH / LEDGER ==
cash in:
cash out:
net:
ledger mismatch:

== ORPHAN / DUPLICATE ==
orphan rows:
duplicate active business keys:
~~~

Rules:

1. Audit command should recompute from source tables.
2. Audit command should not reuse report query blindly.
3. Audit command should fail non-zero when invariant violation exists.
4. Audit command should print human-readable numbers for manual analysis.

---

## 10. Accounting-Grade Hardening Roadmap

This is not immediate patch scope. This is future hardening.

### 10.1 Policy direction

For finalized finance events:

~~~text
Prefer append-only correction/reversal over destructive update.
~~~

Examples:

~~~text
payment recorded -> correction event
refund recorded -> reversal / adjustment event
supplier payment -> immutable payment event
expense finalization -> correction event instead of silent update
inventory movement -> reversal movement instead of delete
~~~

### 10.2 Future ledger direction

Potential future tables / projections:

~~~text
financial_events
financial_event_lines
cash_ledger_entries
inventory_ledger_entries
payable_ledger_entries
receivable_ledger_entries
~~~

Do not implement blindly.

Minimum future design required:

1. Define event types.
2. Define source aggregate.
3. Define debit/credit or in/out direction.
4. Define reversal policy.
5. Define migration constraints.
6. Define report source of truth.
7. Define backfill from existing tables.
8. Define tests before migration.

### 10.3 Decision

Accounting-grade hardening is accepted as strategic direction, but not immediate implementation until make 2 and make 3 are stable.

---

## 11. Definition of Done

### 11.1 DoD for make 1

Required:

~~~bash
make 1
make 1
php /tmp/audit_seed_level1.php
~~~

Pass criteria:

~~~text
admin user count: 1
kasir user count: 1
admin actor_access count: 1
kasir actor_access count: 1
admin cashier area active count: 1
admin transaction capability active count: 1
counts stable after rerun
~~~

Current status:

~~~text
PASS
~~~

### 11.2 DoD for make 2

Required:

~~~bash
make 2
php /tmp/audit_seed_level2.php
make 2
php /tmp/audit_seed_level2.php
./vendor/bin/phpstan analyze --memory-limit=-1
php artisan test
~~~

Pass criteria:

~~~text
make 2 first run passes
make 2 rerun passes
key counts stable after rerun
SI-BL invoices = 69
SI-BL versions = 69
SI-BL projections = 69
scenario invoices active = 5
void scenario invoices total = 3
baseline notes = 240
baseline payments > 0 and stable
baseline refunds = 12 and stable
baseline expenses = 120
orphan supplier invoice lines = 0
orphan supplier receipt lines = 0
orphan payment allocations = 0
duplicate active supplier invoice normalized no = 0
products do not grow unexpectedly
suppliers do not grow unexpectedly
products missing threshold active = 0
~~~

Current status:

~~~text
NOT PASS
First run pass.
Rerun failed before latest patch.
Latest patch syntax passed but runtime proof pending.
~~~

### 11.3 DoD for make 3

Required:

~~~bash
make 3
php /tmp/audit_seed_level3.php
make 3
php /tmp/audit_seed_level3.php
./vendor/bin/phpstan analyze --memory-limit=-1
php artisan test
~~~

Pass criteria:

~~~text
make 3 first run passes
make 3 rerun passes
1 year data count is stable
no orphan rows
no duplicate active business keys
inventory projection reconciles
customer payments/refunds/corrections reconcile
supplier invoices/payments/receipts reconcile
expense 1 year data exists
reports load with expected totals
~~~

Current status:

~~~text
NOT PROVEN
Do not claim readiness.
~~~

### 11.4 DoD for finance correctness

Required:

~~~text
scenario matrix exists
finance invariant tests exist
audit command exists
make 2 audit passes
make 3 audit passes
make verify passes
~~~

Pass criteria:

~~~text
No overallocated payment
No refund overflow
No supplier payable mismatch
No inventory qty mismatch
No inventory cost mismatch
No employee debt remaining mismatch
No cash ledger mismatch
Reports reconcile with source of truth
~~~

Current status:

~~~text
NOT YET IMPLEMENTED
~~~

---

## 12. Workflow For Next Session

### Step 1 — Resume from current blocker

Do not jump to make 3.

Run make 2 again after latest CustomerTransactionBaselineSeeder patch.

Command target:

~~~bash
make 2
php /tmp/audit_seed_level2.php
~~~

Expected:

- FK note_mutation_events blocker should be gone.
- If new blocker appears, fix one blocker only.

### Step 2 — Fix SupplierSeeder idempotency

Known risk:

~~~text
suppliers total grew 50 -> 75
~~~

Audit needed:

- Does SupplierWriterPort create duplicate by name?
- Is there unique constraint on normalized supplier name?
- Should SupplierSeeder use deterministic update-or-create behavior?
- Should supplier seeder purge/rebuild seed-owned suppliers?

Preferred direction:

~~~text
SupplierSeeder must not create new suppliers on rerun.
~~~

### Step 3 — Fix ProductSeeder idempotency

Known risk:

~~~text
products total grew 358 -> 366
~~~

Audit needed:

- ProductScenarioSoftDeletedSeeder rerun behavior.
- ProductScenarioRecreatedSeeder rerun behavior.
- Product usecase duplicate rules.
- soft-deleted product recreation policy.
- seed scenario naming and deterministic IDs.

Preferred direction:

~~~text
ProductSeeder must not grow unexpectedly on rerun.
~~~

### Step 4 — Stabilize make 2

Run:

~~~bash
make 2
php /tmp/audit_seed_level2.php
make 2
php /tmp/audit_seed_level2.php
~~~

Pass required before make 3.

### Step 5 — Create scenario matrix file

Create:

~~~text
docs/handoff/v2/seedernew/2026-04-26-seedernew-scenario-matrix.md
~~~

Fill all domain scenarios and status.

### Step 6 — Create audit command

Start with level 2.

Potential file:

~~~text
app/Console/Commands/AuditSeedLevelCommand.php
~~~

Only after checking current Laravel command registration structure.

### Step 7 — Add invariant tests

Start small:

~~~text
tests/Feature/Finance/CustomerMoneyInvariantTest.php
tests/Feature/Finance/SupplierMoneyInvariantTest.php
tests/Feature/Finance/InventoryCostInvariantTest.php
~~~

### Step 8 — Only then make 3

After make 2 is stable and audit tooling exists.

### Step 9 — make verify

Required final gate:

~~~bash
make verify
~~~

---

## 13. Current Progress Estimate

Current honest readiness:

~~~text
make 1: 90%
make 2 single run: 75%
make 2 idempotent: 50%
make 3 single run: 40%
make 3 idempotent: 25% - 35%
finance correctness framework: 20% - 30%
overall final goal: 65%
~~~

Do not claim 80%+ until make 2 rerun passes.

Do not claim 90%+ until make 3 rerun + audit + make verify pass.

---

## 14. Next Session Opening Prompt

Use this in the next chat:

~~~text
Kita lanjut dari repo Asyraf2003/bengkelnativejs.

Saya sudah berada di root repo.

Baca dulu:
docs/handoff/v2/seedernew/2026-04-26-seedernew-finance-blueprint-adr.md

Tujuan sesi:
lanjutkan SeederNew finance correctness strategy.

Aturan:
- Jangan ubah GitHub langsung.
- Kirim command terminal dari root repo.
- Jangan pakai set -euo pipefail.
- Jangan pakai git diff.
- Zero assumption.
- Satu active step per balasan.
- Jangan lompat ke make 3 sebelum make 2 idempotency pass.
- Mulai dari rerun make 2 setelah patch CustomerTransactionBaselineSeeder.
- Jika make 2 masih gagal, fix satu blocker saja.
- Setelah make 2 stabil, lanjut audit SupplierSeeder/ProductSeeder idempotency.
~~~

---

## 15. Immediate Next Command For Next Session

~~~bash
printf '\n== RERUN MAKE 2 AFTER CUSTOMER TRANSACTION BASELINE PATCH ==\n'
if make 2; then
    printf '\n== MAKE 2 PASS, AUDIT ==\n'
    php /tmp/audit_seed_level2.php
else
    printf '\n== MAKE 2 FAILED, STOP HERE ==\n'
fi
~~~

If `/tmp/audit_seed_level2.php` is missing, recreate it from previous session before audit.

---

## 16. Closing Decision

SeederNew direction is accepted.

Final architecture stance:

~~~text
Seeder creates deterministic scenarios.
Invariant tests prove business math.
Audit commands support manual analysis.
Migration constraints prevent impossible states.
Accounting-grade ledger hardening is strategic future direction.
~~~

Do not rely on seeders alone for finance correctness.
