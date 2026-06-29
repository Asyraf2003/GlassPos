# HyperPOS Technical README

HyperPOS is a Laravel/MySQL workshop POS and operations system built around a dense transactional domain: cashier notes, service jobs, spare parts, supplier invoices, inventory movements, payment allocation, refunds, revision history, audit trails, and operational reporting.

This repository is intentionally not shaped like a simple CRUD demo. The core problem is state coherence: every UI action that changes money, stock, payment, refund, supplier payable, or report output must remain explainable across database rows, read models, UI pages, PDF exports, Excel exports, and audit history.

## Production Context

HyperPOS has been operated as a live Laravel/MySQL application for a real workshop environment.

Owner-reported production operation metadata:

| Signal | Value |
|---|---:|
| Live runtime | Laravel + MySQL |
| Production style | Shared hosting / constrained deployment environment |
| MySQL update cycles while live | 12 |
| File update cycles while live | 31 |
| User-visible downtime during those update cycles | 0 reported |
| Production data visibility in repository | metadata only, no production dump |
| Production repair policy | read-only diagnostic first, no blind data mutation |

Important boundary: this repository does not contain the production database. Production claims in this file are limited to source code, metadata, documented lifecycle notes, owner-reported operation evidence, and local verification evidence.

## Repository Density Snapshot

Snapshot source: `make audit-git`.

Latest local audit snapshot:

| Area | Count |
|---|---:|
| Total files | 21,739 |
| Total directories | 3,009 |
| PHP files | 2,088 |
| Blade files | 132 |
| Markdown docs | 444 |
| Migrations | 95 |
| Test files | 498 |
| Route files | 23 |
| Total commits | 3,385 |
| Unique commit days | 102 |

LOC snapshot:

| Area | LOC |
|---|---:|
| `app/` PHP | 70,482 |
| `tests/` PHP | 77,205 |
| `database/` PHP | 15,440 |
| `resources/` Blade | 17,265 |
| `docs/` Markdown | 125,869 |

Commit overview:

| Signal | Value |
|---|---|
| First commit | `aa732343 chore: fresh start laravel installation` |
| Last commit at snapshot | `e69eea9a commit 3385` |

Commit distribution:

| Month | Commits |
|---|---:|
| 2026-03 | 397 |
| 2026-04 | 1,096 |
| 2026-05 | 947 |
| 2026-06 | 945 |

Commit weekday distribution:

| Weekday | Commits |
|---|---:|
| Friday | 629 |
| Saturday | 517 |
| Sunday | 499 |
| Monday | 488 |
| Thursday | 473 |
| Wednesday | 471 |
| Tuesday | 308 |

Most changed files at snapshot:

| Changes | File |
|---:|---|
| 103 | `app/Providers/HexagonalServiceProvider.php` |
| 61 | `resources/views/admin/dashboard/index.blade.php` |
| 45 | `routes/web/note.php` |
| 43 | `resources/views/cashier/notes/workspace/create.blade.php` |
| 42 | `resources/views/layouts/partials/sidebar-admin.blade.php` |
| 41 | `resources/views/admin/procurement/supplier_invoices/show.blade.php` |
| 40 | `resources/views/admin/procurement/supplier_invoices/create.blade.php` |
| 39 | `app/Application/Note/Services/NoteDetailPageDataBuilder.php` |
| 38 | `resources/views/cashier/notes/show.blade.php` |
| 35 | `resources/views/cashier/notes/partials/note-rows-table.blade.php` |

This density exists because the application touches mutable operational state. A wrong change can silently corrupt money, stock, debt, payable, payment allocation, refund status, or report totals.

## Migration Timeline Snapshot

Snapshot source: `make audit-git`.

| Date | Migration count |
|---|---:|
| 0001-01-01 | 3 |
| 2026-03-10 | 3 |
| 2026-03-11 | 1 |
| 2026-03-12 | 8 |
| 2026-03-13 | 1 |
| 2026-03-14 | 7 |
| 2026-03-15 | 7 |
| 2026-03-16 | 4 |
| 2026-04-02 | 4 |
| 2026-04-04 | 1 |
| 2026-04-06 | 8 |
| 2026-04-07 | 2 |
| 2026-04-09 | 1 |
| 2026-04-10 | 2 |
| 2026-04-11 | 1 |
| 2026-04-17 | 1 |
| 2026-04-18 | 4 |
| 2026-04-19 | 4 |
| 2026-04-22 | 3 |
| 2026-04-23 | 4 |
| 2026-04-27 | 4 |
| 2026-05-13 | 3 |
| 2026-05-15 | 8 |
| 2026-05-23 | 1 |
| 2026-05-25 | 1 |
| 2026-05-29 | 1 |
| 2026-06-04 | 2 |
| 2026-06-17 | 2 |
| 2026-06-18 | 1 |
| 2026-06-19 | 1 |
| 2026-06-22 | 1 |
| 2026-06-23 | 1 |

## Test Coverage by Domain Snapshot

Snapshot source: `make audit-git`.

| Domain | Test files |
|---|---:|
| Note | 139 |
| Procurement | 56 |
| Reporting | 48 |
| EmployeeFinance | 29 |
| Expense | 22 |
| Database | 21 |
| ProductCatalog | 19 |
| ReportingExports | 17 |
| Payment | 12 |
| AuditLog | 12 |
| PushNotification | 7 |
| Inventory | 7 |
| ServiceProductTemplate | 4 |
| IdentityAccess | 3 |
| Seeder | 2 |
| Foundation | 2 |
| Auth | 2 |
| Admin | 2 |
| Support | 1 |
| ServiceCatalog | 1 |
| Http | 1 |
| Cashier | 1 |

## Architecture

HyperPOS follows a Hexagonal / Ports and Adapters direction.

| Layer | Role |
|---|---|
| `app/Core` | Domain entities, invariants, value objects, validation rules |
| `app/Application` | Use cases, orchestration, transactional workflows |
| `app/Ports` | Contracts between application and infrastructure |
| `app/Adapters/In` | HTTP controllers, request boundary, presenter boundary |
| `app/Adapters/Out` | Persistence, projections, reporting queries, external implementation |
| `resources/views` | Presentation rendering only |
| `database` | Migrations, seeders, schema evolution |
| `tests` | Unit, feature, characterization, regression, architecture tests |
| `docs` | ADR, blueprint, lifecycle, audit evidence, runbooks, handoffs |

Structural signal from `make audit-git`:

| Group | Count |
|---|---:|
| Ports | 133 |
| Adapters/In | 288 |
| Adapters/Out | 295 |
| Core | 91 |
| Application | 590 |
| test:src ratio | 498:1418 |

Strictness signal:

| Signal | Value |
|---|---:|
| `strict_types` coverage | 1418 / 1418 PHP source files |
| `final class` usage | 1111 |
| Interfaces | 133 |
| `readonly` property occurrences | 1370 |
| `DateTimeImmutable` uses | 305 |

## Engineering Position

The project is built around a few hard rules:

1. Business rules should not be buried in Blade, controllers, or raw query fragments.
2. Money is stored as integer rupiah.
3. Stock movement must have a source and must be reversible or explainable.
4. Payment and refund allocation must not exceed backend-allocatable component capacity.
5. UI labels must not invent states that the backend cannot execute.
6. Reports must use explicit read models and reconciliation logic.
7. Sensitive mutations must be audited.
8. Revisions must preserve history instead of overwriting meaning.
9. Production repair must be read-only diagnostic first.
10. Manual QA findings must become automated regression tests before closure.

## Main Business Domains

### Note / Transaction

The Note domain is the heaviest part of the system.

It covers:

- cashier transaction workspace;
- multi-line transaction note creation;
- product-only rows;
- service-only rows;
- service with store-stock spare part rows;
- service with external purchase / case-cost rows;
- service package / template auto-fill;
- inline cash and transfer payments;
- partial and full payment;
- edit / revision after transaction creation;
- paid note correction;
- revision settlement carry-forward;
- surplus disposition;
- refund due;
- refund paid;
- selected-row refund;
- full refund lifecycle;
- note current revision pointer;
- note history projection;
- current detail read model;
- reporting consistency.

Representative risk handled:

- edit after payment;
- edit after refund;
- stale current revision;
- payable UI showing a component that backend allocation rejects;
- duplicated submit;
- lost payment after revision;
- report total based on obsolete note value;
- current detail page disagreeing with history page.

### Payment

Payment is treated as allocation, not just "insert payment row".

The system handles:

- customer payments;
- cash detail;
- transfer payment method;
- payment allocation;
- component-level payment allocation;
- selected-row payment;
- retry/concurrency handling;
- over-allocation protection;
- legacy payment allocation synthesis;
- paid note auto-close;
- payment visibility in cash ledger and transaction reports.

Failure class handled:

- double-click payment;
- paying an already-paid component;
- paying a refunded/non-payable component;
- partial payment carry-forward after note revision;
- repeated submit after browser refresh;
- old legacy payment allocation still needing report compatibility.

### Refund

Refund is modeled as a business event and allocation lifecycle, not a negative payment shortcut.

The system handles:

- customer refund;
- selected-row refund plan;
- refundable payment allocation detection;
- refund component allocation;
- refund pair limit guard;
- full refund lifecycle;
- selected product/store-stock refund;
- refunded inventory reversal;
- refund report impact;
- refund visibility in cash ledger;
- post-refund edit/revision edge cases.

Failure class handled:

- refund larger than refundable amount;
- refunding non-refundable service fee by accident;
- refund button visible when backend cannot execute;
- refunded rows re-entering payment flow;
- report showing cash history without explaining current collectible state.

### Product / Inventory

Inventory is treated as operational ledger state.

The system covers:

- product catalog;
- stock adjustment;
- stock adjustment reversal;
- stock projection rebuild;
- inventory costing projection rebuild;
- stock-out movement;
- refund/reversal stock return;
- negative stock policy;
- product threshold;
- product versioning;
- product lifecycle with soft delete and restore.

Failure class handled:

- negative stock caused by revision;
- duplicate product rows;
- stock movement without source;
- stale inventory projection;
- product deletion while still referenced by operational history;
- report mismatch between stock movement and current snapshot.

### Procurement / Supplier Invoice

Procurement covers supplier invoice lifecycle and inventory receipt/cost effects.

It includes:

- supplier invoice creation;
- supplier invoice edit/update;
- received supplier invoice revision;
- supplier invoice version writer;
- version timeline;
- invoice line mapping;
- tax input and tax summary;
- landed-cost tax allocation;
- rounding residue;
- received invoice cost revaluation;
- inventory movement delta;
- negative stock guard;
- supplier payment;
- supplier payment reversal;
- supplier receipt and receipt reversal;
- supplier payment proof upload;
- supplier payable reporting.

Recent hardening includes:

- supplier invoice edit reason propagation;
- latest reason display;
- supplier invoice version timeline;
- tax-only revision no longer triggering false negative-stock blocker;
- edit draft key isolated by expected revision number;
- Blade `@php` removal from supplier invoice version timeline;
- oversized view/service files split for line-count audit.

### Reporting

Reporting is not just a UI table layer. It is a read-model and reconciliation boundary.

Covered reporting surfaces:

- dashboard overview;
- dashboard operational performance;
- transaction summary;
- transaction cash ledger;
- operational profit;
- service package profit breakdown;
- inventory movement;
- inventory stock value;
- supplier payable;
- employee debt;
- payroll;
- operational expense;
- PDF exports;
- Excel exports.

Report-specific hardening includes:

- report source-of-truth boundary;
- cash / transfer split;
- refund visibility;
- surplus refund paid visibility;
- owner-facing label cleanup;
- Excel formula injection hardening;
- PDF readability and table layout;
- screen/PDF/Excel consistency checks.

### Employee Finance / Expense

The system includes internal finance modules:

- employee master;
- employee versioning;
- employee debt;
- employee debt payment;
- employee debt principal adjustment;
- employee debt payment reversal;
- payroll disbursement;
- payroll disbursement reversal;
- operational expense;
- expense category lifecycle;
- employee debt and payroll reporting;
- operational expense reporting.

### Audit / Security / Access

HyperPOS includes hardening work around:

- audit logs;
- audit event writer;
- audit snapshots;
- transactional audit outbox;
- role/capability boundary;
- admin/cashier area separation;
- transaction entry capability guard;
- public surface output storage hardening;
- proof attachment content-type handling;
- XSS hardening;
- JavaScript URL hardening;
- login/rate-limit analysis;
- seeder credential boundary.

## UI / DB / System Coherence

The main design pressure is consistency between:

- UI action availability;
- backend command guard;
- database state;
- current projection;
- revision history;
- payment/refund allocation;
- inventory movement;
- report source;
- PDF export;
- Excel export.

The system explicitly tracks bugs where a UI action appears valid but backend allocation rejects it, or where reports show a value that is mathematically true for cash history but misleading for current collectible state.

This is why lifecycle docs and characterization tests exist. The project keeps evidence of these mismatches instead of pretending a green happy-path test means the domain is safe.

## Operational Failure Classes Covered

The repository includes or tracks protections for:

- double-click create;
- double-click payment;
- double-click refund;
- repeated submit;
- browser refresh after submit;
- browser back after submit;
- partial request completion;
- power-loss style interruption assumption;
- stale modal payload;
- stale current revision pointer;
- duplicate product line;
- malformed numeric input;
- zero amount;
- overpayment;
- over-refund;
- negative stock;
- stale report projection;
- report/export mismatch;
- internal label leakage to owner-facing UI.

## Documentation System

The project uses documentation as an operational control layer.

| Path | Purpose |
|---|---|
| `docs/01_standards/` | Engineering standards, workflow rules, AI/operator rules |
| `docs/02_architecture/adr/` | Permanent architecture and domain decisions |
| `docs/03_blueprints/` | Implementation plans, source maps, matrices |
| `docs/04_lifecycle/` | Active lifecycle work |
| `docs/05_audits/` | Audit reports |
| `docs/99_archive/` | Closed lifecycle work, historical handoff, old proof |

Important documentation rules:

- active lifecycle files should not become graveyards;
- closed lifecycle work moves to archive;
- public README must stay readable for non-technical readers;
- technical stats, closure pointers, and audit density belong here;
- production repair runbooks must not encourage blind writes.

## Current Active Lifecycle Pointer

Current active manual QA scope:

- `docs/04_lifecycle/error_log/0051_manual_transaction_reporting_sequential_qa_matrix.md`

Purpose:

- run ordered manual QA across create, payment, edit, refund, reports, PDF, Excel, inventory, and cash impact;
- catch UI/DB/report drift;
- convert every discovered mismatch into automated regression tests;
- update public and technical documentation after behavior is proven.

Recent closed/archived lifecycle context:

- `docs/99_archive/04_lifecycle/error_log/0049_manual_qa_supplier_invoice_revision_and_timezone_gap.md`
- `docs/99_archive/04_lifecycle/handoff/0050_legacy_timestamp_repair_handoff.md`

Closed 0049/0050 scope:

- supplier invoice edit reason propagation;
- latest reason display;
- supplier invoice version timeline;
- tax-only revision false negative-stock blocker;
- edit draft lifecycle hardening;
- note correction history manual failure reclassification;
- timestamp display fix;
- file split for line audit;
- Blade PHP directive cleanup;
- production read-only timestamp diagnostic;
- no production timestamp repair recommendation.

## Timestamp Policy

Storage/source interpretation remains UTC-oriented.

Owner-facing display timezone:

- `APP_DISPLAY_TIMEZONE`
- default: `Asia/Makassar`

Rules:

- timestamp display converts to owner-facing display timezone;
- date-only business fields must not be shifted;
- production timestamp repair must not run without proof;
- production diagnostic must be read-only first;
- UTC-like rows should not be repaired;
- unknown rows must not be bulk-shifted.

Recent production diagnostic result:

- MySQL runtime appeared WIB-like / UTC+7;
- owner operational timezone is WITA / UTC+8;
- recent audit/supplier invoice rows were UTC-like;
- several note/refund/mutation candidate tables were empty;
- no legacy timestamp repair write is recommended.

## Verification

Main verification commands:

```bash
make help
make audit-git
make audit-lines
make audit-blade
make audit-contract
make verify
```

Focused examples:

```bash
php artisan test tests/Feature/Note
php artisan test tests/Feature/Payment
php artisan test tests/Feature/Procurement
php artisan test tests/Feature/Reporting
php artisan test tests/Feature/ReportingExports
php artisan test tests/Unit
php artisan test tests/Arch
```

Focused timestamp support:

```bash
php artisan test tests/Unit/Support/ViewDateFormatterTest.php
```

Current repository help entrypoint:

```bash
make help
make docs-help
```

## Known Tooling Note

`make audit-git` is expected to generate repository density and architecture statistics.

Current observed noise:

```text
scripts/git_report.sh: line 119: 0
0: arithmetic syntax error in expression
```

The report still reaches `REPORT SELESAI`. Treat the arithmetic message as a tooling cleanup issue, not as source truth for application behavior. The output should be cleaned so repository audit proof is not noisy.

## Reader Boundary

Use `README.md` for broad product explanation.

Use this file for:

- architecture;
- density;
- verification commands;
- production-operation metadata;
- domain failure classes;
- lifecycle pointers;
- audit/QA context.

Do not place production secrets, credentials, database dumps, private customer data, or unredacted operational evidence in this repository.
