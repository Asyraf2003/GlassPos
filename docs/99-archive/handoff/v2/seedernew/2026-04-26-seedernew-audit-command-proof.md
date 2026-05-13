# SeederNew Audit Command Proof

Date: 2026-04-26  
Repo: Asyraf2003/bengkelnativejs  
Local root: /home/asyraf/Code/laravel/bengkel2/app

## Purpose

This document records the official local proof for SeederNew Finance Correctness Strategy after audit commands were added and verified.

Target strategy:

- make 1: account/access only
- make 2: deterministic normal 1 month and idempotent
- make 3: deterministic extreme 1 year and idempotent
- scenario matrix exists
- audit command exists
- finance invariant tests planned
- make verify passes

## Official Commands Verified

### 1. Seed Level Audit

Command:

~~~bash
php artisan audit:seed-level 2
~~~

Proof output:

~~~text
== SEED LEVEL AUDIT ==
level: 2

== LEVEL 2 CORE COUNTS ==
users total: 2
products total: 382
active products: 338
products missing threshold active: 0
suppliers total: 100
employees total: 12

== SUPPLIER INVOICE LEVEL 2 COUNTS ==
SI-BL invoices: 69
SI-BL versions: 69
SI-BL projections: 69
scenario invoices active: 5
void scenario invoices total: 3
SI-VOID-001 voided: 1
SI-VOID-REUSE-001 voided: 1
SI-VOID-REUSE-001 active: 1

== CUSTOMER BASELINE COUNTS ==
baseline notes: 240
baseline customer payments: 216
baseline payment allocations: 216
baseline refunds: 12

== EXPENSE BASELINE COUNTS ==
baseline expenses: 120
expense categories: 6

== ORPHAN / DUPLICATE CHECKS ==
orphan supplier invoice lines: 0
orphan supplier receipt lines: 0
orphan payment allocations: 0
duplicate active supplier invoice normalized no: 0

== RESULT ==
failures: 0
~~~

Status:

- PASS
- Official audit command is usable for level 2 seed checks.
- Supplier invoice audit uses `supplier_invoices.nomor_faktur`, not `supplier_invoices.id`.

## 2. Finance Audit

Command:

~~~bash
php artisan audit:finance
~~~

Proof output:

~~~text
== SCENARIO COVERAGE ==
defined scenarios: 60
missing scenario definitions: 0

== CUSTOMER MONEY ==
note total: 5357185750
work item subtotal: 5357242750
note/work item mismatch: 57000
payment total: 3907965022
payment allocation total: 3907965022
payment component allocation total: 3907965022
refund total: 21285695
refund component allocation total: 21285695
outstanding: 1470506423
overallocated payments: 0
refund overflow: 0

== SUPPLIER MONEY ==
invoice total: 12151223310
current invoice line total: 12151223310
supplier payment total: 6936597936
payable: 5214625374
voided active payable leaks: 0
payment proof attachment count: 2

== INVENTORY ==
computed qty: 114848
stored qty: 116459
qty mismatch: 1611
computed value: 8432435869
stored value: 8930736697
value mismatch: 498300828

== EMPLOYEE FINANCE ==
debt total: 6300000
debt paid: 2850000
remaining balance: 3450000
remaining mismatch: 0

== CASH / LEDGER ==
cash in: 3907965022
cash out: 7508579881
net: -3600614859
ledger mismatch: NOT IMPLEMENTED

== ORPHAN / DUPLICATE ==
orphan payment component allocations: 0
orphan refund component allocations: 0
duplicate active business keys: SEE audit:seed-level 2

== RESULT ==
failures: 0
~~~

Status:

- PASS
- Scenario definitions are complete: 60 defined, 0 missing.
- Command currently treats the following as metrics, not failures:
  - note/work item mismatch: 57000
  - inventory qty mismatch: 1611
  - inventory value mismatch: 498300828
  - ledger mismatch: NOT IMPLEMENTED

Decision:

- Do not patch these mismatches blindly.
- Treat them as candidates for scoped finance invariant tests.
- Do not claim domain bug until each mismatch is traced to exact seeded scenario, table contract, and intended invariant.

## 3. Full Verification

Command:

~~~bash
make verify
~~~

Proof output:

~~~text
Tests:    740 passed (3856 assertions)
Duration: 33.57s
~~~

Status:

- PASS
- Under the current project rule, `Tests: xxx passed (...)` means `make verify` is accepted as full PASS because `make verify` runs serial verification.


## 4. Finance Invariant Test

File:

~~~text
tests/Feature/Reporting/SeederNewFinanceInvariantFeatureTest.php
~~~

Targeted command:

~~~bash
php artisan test tests/Feature/Reporting/SeederNewFinanceInvariantFeatureTest.php
~~~

Targeted proof output:

~~~text
PASS  Tests\Feature\Reporting\SeederNewFinanceInvariantFeatureTest
✓ core finance money invariants hold for seed like fixture

Tests:    1 passed (5 assertions)
Duration: 4.41s
~~~

Covered invariants:

- customer payment total equals payment allocation total
- customer payment total equals payment component allocation total
- customer refund total equals refund component allocation total
- active supplier invoice total equals active supplier invoice current line total
- voided supplier invoice payment does not leak into active payable calculation

Excluded from this first minimal invariant test:

- note/work item mismatch
- inventory qty/value mismatch
- ledger invariant

Reason:

Those are currently audit metrics, not audit command failures. They need exact scenario tracing before becoming hard tests.

## 5. Full Verification After Finance Invariant Test

Command:

~~~bash
make verify
~~~

Proof output:

~~~text
Tests:    741 passed (3861 assertions)
Duration: 33.12s
~~~

Status:

- PASS
- Finance invariant test is now included in the full suite.

## Files Related To Audit Command Work

Created:

- routes/console_audit.php
- docs/handoff/v2/seedernew/2026-04-26-seedernew-audit-command-proof.md

Changed:

- routes/console.php

Previously changed during make 2 stabilization:

- database/seeders/SupplierSeeder.php
- database/seeders/Product/ProductScenarioSoftDeletedSeeder.php

Existing docs:

- docs/handoff/v2/seedernew/2026-04-26-seedernew-finance-blueprint-adr.md
- docs/handoff/v2/seedernew/2026-04-26-seedernew-make2-idempotency-proof.md
- docs/handoff/v2/seedernew/2026-04-26-seedernew-scenario-matrix.md

## Known Risks Not To Cleanup Yet

Do not cleanup yet:

- duplicate historical suppliers
- duplicate historical PRD-DEL soft-deleted products
- /tmp helper files

Known helper files still retained until final replacement is confirmed:

- /tmp/audit_seed_level2.php
- /tmp/audit_finance_schema.php
- /tmp/audit_finance_exact_tables.php
- /tmp/audit_supplier_invoice_keys.php

## Next Safe Step

Design minimal finance invariant tests.

Recommended first test scope:

1. Scenario matrix coverage test
   - scenario matrix has 60 rows
   - audit scenario definitions report 60 defined and 0 missing

2. Money allocation parity test
   - customer_payments amount equals payment_allocations amount
   - customer_payments amount equals payment_component_allocations amount
   - customer_refunds amount equals refund_component_allocations amount

3. Supplier invoice parity test
   - active supplier invoice grand total equals current supplier invoice line total
   - voided supplier invoice does not leak into active payable

Do not include yet:

- note/work item mismatch invariant
- inventory qty/value invariant
- ledger invariant

Reason:

Those are currently visible metrics but not command failures. They need exact scoped tracing before becoming hard tests.
