# Blueprint 0001 - CreateOnly Seed Scale Profiles

## Metadata

- Date: 2026-06-02
- Scope: CreateOnly seed scale profiles for owner-readable QA and reporting proof
- Status: ACTIVE BLUEPRINT / 100M IMPLEMENTED / PEAK-STRESS PLANNED
- Primary implementation target: CreateOnly transaction seeders
- Primary proof target: operational profit report sanity and PDF/XLSX export
- Source-of-truth rule: local command output wins over blueprint expectation

## Problem

The CreateOnly seed system needs several deterministic monthly profiles instead of one overloaded create-all target.

The project already has:

- small owner-readable sanity profile;
- monthly normal 100-200 juta profile.

The next scale targets are:

- peak 500 juta/month;
- stress 6-8 miliar/month;
- optional stress ceiling 10 miliar/month;
- future refund scaffold.

The profiles must stay separate so each one can be run, audited, compared, and reported without silently changing earlier proof.

## Locked Facts

### Small owner-readable sanity profile

Target:

- `create-all-v3`

Latest proven aggregate:

```text
notes = 34
work_items = 34
customer_payments = 31
note_history_projection = 34
notes_total_sum = 28125000
customer_payments_sum = 26650000
cash_operational_profit_rupiah = 6863252
```

Purpose:

small owner-readable monthly sanity dataset;
fast create-only QA;
report/export baseline.

### Monthly normal 100-200 juta profile

Target:

- `create-all-month-normal-100m`

Files:

```text
database/seeders/CreateOnly/CreateTransactionMonthNormal100MSeeder.php
database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MPayloadFactory.php
database/seeders/CreateOnly/Support/CreateTransactionMonthNormal100MItemFactory.php
```

Latest proven aggregate:

```text
notes = 124
work_items = 124
customer_payments = 115
note_history_projection = 124
notes_total_sum = 182925000
customer_payments_sum = 167250000
cash_in_rupiah = 167250000
product_purchase_cost_rupiah = 37329472
cash_operational_profit_rupiah = 112083028
```

Export proof:

```text
PDF exists true
PDF header %PDF
XLSX exists true
sheet Ringkasan
period 01 Juni 2026 s/d 30 Juni 2026
profit_B14 = 112083028
```

Purpose:

normal monthly operational dataset;
owner-readable 100-200 juta report sanity;
positive operational profit proof.

## Locked Decisions

Keep create-all-v3 unchanged as the small sanity profile.
Keep create-all-month-normal-100m unchanged as the 100-200 juta profile.
New scale profiles must use separate seeder classes and separate make targets.
Transaction seeders must use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler.
Do not raw-insert:

- notes;
- work_items;
- customer_payments;
- projections.

Use deterministic idempotency keys.
Spread transaction dates across the active month.
Use only proven payment methods unless a new method is separately proven:

- cash;
- transfer.

Cash partial payments must include:

- amount_paid_rupiah;
- amount_received_rupiah.

Refund scaffold must not be mixed into create-only scale profiles.
Report query must not be patched just to make seed numbers look good.

## Profile Ladder

| Level | Target | Status | Purpose |
|---|---|---|---|
| L0 | create-all-v3 | implemented/proven | small sanity |
| L1 | create-all-month-normal-100m | implemented/proven | 100-200 juta normal month |
| L2 | create-all-month-peak-500m | planned | peak 500 juta/month |
| L3 | create-all-month-stress-8b | planned | stress 6-8 miliar/month |
| L4 | create-all-month-stress-10b | planned optional ceiling | upper-bound 10 miliar/month |
| L5 | refund scaffold | planned separately | refund/report boundary |

## Peak 500 Juta Profile Blueprint

### Target

Target aggregate after:

```text
php artisan migrate:fresh --seed
make create-all-month-peak-500m
```

Expected aggregate:

```text
notes = 314
work_items = 314
customer_payments = 295
note_history_projection = 314
notes_total_sum = 604125000
customer_payments_sum = 550250000
cash_in_rupiah = 550250000
refunded_rupiah = 0
```

Basis:

existing create-all-v3:

```text
notes = 34
customer_payments = 31
notes_total_sum = 28125000
customer_payments_sum = 26650000
```

new peak seeder:

```text
notes = 280
customer_payments = 264
notes_total_sum = 576000000
customer_payments_sum = 523600000
```

aggregate:

```text
notes = 314
customer_payments = 295
notes_total_sum = 604125000
customer_payments_sum = 550250000
```

### Proposed Files

```text
database/seeders/CreateOnly/CreateTransactionMonthPeak500MSeeder.php
database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MPayloadFactory.php
database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MItemFactory.php
```

### Proposed Make Targets

```text
seed-transaction-month-peak-500m
seed-create-all-month-peak-500m
create-all-month-peak-500m
```

Target dependency:

```text
seed-create-all-month-peak-500m: seed-create-all-v3 seed-transaction-month-peak-500m
create-all-month-peak-500m: seed-create-all-month-peak-500m
    $(MAKE) seed-audit-baseline
    php artisan projection:rebuild-indexes all
```

Do not depend on seed-create-all-month-normal-100m.

### Transaction Mix

New peak seeder planned count: 280 notes.

| Segment | Count | Gross per note | Gross total |
|---|---:|---:|---:|
| Service-only | 80 | 1200000 | 96000000 |
| Store-stock | 90 | 1800000 | 162000000 |
| External purchase | 70 | 2600000 | 182000000 |
| Package auto-split | 40 | 3400000 | 136000000 |
| Total | 280 | | 576000000 |

### Payment Mix

| Segment | Full | Partial | Skip/unpaid | Payment rows |
|---|---:|---:|---:|---:|
| Service-only | 66 | 10 | 4 | 76 |
| Store-stock | 74 | 12 | 4 | 86 |
| External purchase | 54 | 10 | 6 | 64 |
| Package auto-split | 32 | 6 | 2 | 38 |
| Total | 226 | 38 | 16 | 264 |

Partial payment targets:

```text
service-only partial = 900000
store-stock partial = 1400000
external purchase partial = 2000000
package auto-split partial = 2700000
```

Peak seeder cash-in:

```text
service-only cash-in = 88200000
store-stock cash-in = 150000000
external purchase cash-in = 160400000
package auto-split cash-in = 125000000
total peak cash-in = 523600000
```

Aggregate cash-in with create-all-v3:

```text
523600000 + 26650000 = 550250000
```

### Item Shape

Service-only:

```text
entry_mode = service
part_source = none
service price = 1200000
product_lines = blank
external_purchase_lines = blank
```

Store-stock:

```text
entry_mode = service
part_source = none
service price = 1200000
product line:
  qty = 2
  unit_price_rupiah = 300000
total = 1800000
```

External purchase:

```text
entry_mode = service
part_source = none
service price = 1200000
external purchase:
  qty = 1
  unit_cost_rupiah = 1400000
total = 2600000
```

Package auto-split store-stock:

```text
entry_mode = service
part_source = none
pricing_mode = package_auto_split
package_total_rupiah = 3400000

product A:
  qty = 1
  unit_price_rupiah = 800000

product B:
  qty = 1
  unit_price_rupiah = 600000

expected service residual = 2000000
```

### Inventory Pressure

Peak estimated store-stock units:

```text
normal store-stock:
90 notes * 2 qty = 180 units

package store-stock:
40 notes * 2 product lines = 80 units

total = 260 units
```

Implementation requirement:

rotate across many stocked products;
require enough stocked products before running;
do not drain one product repeatedly.

Recommended product query target:

```text
products with qty_on_hand >= 20
limit 80
minimum 24
```

### Cost Expectation

Known base fixed cash-out from small sanity proof:

```text
operational_expense_rupiah = 3262500
payroll_disbursement_rupiah = 7525000
employee_debt_cash_out_rupiah = 7050000
fixed_cash_out_total = 17837500
```

Expected peak external purchase cost:

```text
base external = 1720000
peak external = 98000000
expected external_purchase_cost_rupiah = 99720000
```

Expected store-stock COGS:

```text
8000000 to 25000000
```

Expected product purchase cost:

```text
108000000 to 125000000
```

Expected operational profit:

```text
cash_in = 550250000
minus product_purchase_cost = 108000000 to 125000000
minus fixed_cash_out_total = 17837500

expected cash_operational_profit_rupiah = 407412500 to 424412500
```

Do not lock exact store-stock COGS before local proof.

## Stress 6-8 Miliar Blueprint Placeholder

Status: planned, not implemented.

Target:

```text
create-all-month-stress-8b
```

Expected high-level target:

```text
gross notes total = 7 to 8.5 miliar
cash-in = 6 to 8 miliar
notes = 2500 to 4000
payments = 2200 to 3600
unpaid = 8% to 15%
partial = 15% to 25%
```

Purpose:

projection rebuild stress;
report query stress;
payment allocation volume;
inventory stock-out volume;
audit baseline behavior;
idempotency replay behavior.

Do not implement before peak 500M is proven.

## Stress Ceiling 10 Miliar Blueprint Placeholder

Status: planned optional ceiling, not implemented.

Target:

```text
create-all-month-stress-10b
```

Expected high-level target:

```text
gross notes total = 10.5 to 11.5 miliar
cash-in = 10 to 10.8 miliar
notes = 4000 to 5500
payments = 3600 to 5000
```

Purpose:

upper-bound arithmetic proof;
large projection rebuild proof;
large payment allocation proof;
large inventory stock-out proof;
large export/report behavior proof.

Do not mix with 6-8B target.

## Refund Scaffold Placeholder

Status: planned separately, not implemented.

Reason:

Refund profile changes report semantics:

- gross payment;
- refunded amount;
- net cash;
- operational profit;
- selected-row refund boundary.

Do not mix refund scaffold with create-only scale profiles.

## Proof Workflow For Each Scale Profile

For each new profile, run:

1. syntax + line count proof
2. standalone seed proof
3. make target dry-run proof
4. aggregate create-all proof
5. projection count proof
6. operational profit sanity proof
7. PDF/XLSX proof when relevant
8. make verify proof
9. handoff update proof

## Next Implementation Step

Patch only:

```text
database/seeders/CreateOnly/CreateTransactionMonthPeak500MSeeder.php
database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MPayloadFactory.php
database/seeders/CreateOnly/Support/CreateTransactionMonthPeak500MItemFactory.php
```

Do not patch mk/seed.mk until the 3 files pass syntax and line-count proof.
