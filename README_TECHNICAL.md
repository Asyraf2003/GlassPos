# App Kasir Hexagonal - Technical README

Dokumen ini adalah README teknis untuk repository App Kasir Hexagonal.

README publik ada di `README.md`. File ini sengaja lebih padat, lebih teknis, dan lebih eksplisit karena repository ini bukan sekadar demo CRUD Laravel. Ini sistem operasional bengkel dengan transaksi, stok, supplier procurement, payment lifecycle, refund, audit trail, reporting, dan workflow documentation yang cukup besar untuk membuat README biasa menyerah lebih dulu.

## Latest lifecycle pointer

Latest closed lifecycle:

- `docs/99_archive/04_lifecycle/error_log/0049_manual_qa_supplier_invoice_revision_and_timezone_gap.md`
- `docs/99_archive/04_lifecycle/handoff/0050_legacy_timestamp_repair_handoff.md`

Status:

- 0049 final closed.
- 0050 final closed.
- Production timestamp repair: not recommended.
- Production diagnostic: read-only SQL only.
- Owner-facing timestamp display: fixed via display timezone.
- Date-only business fields: not shifted.

## Repository density snapshot

Snapshot dari `make audit-git`:

| Area | Count |
|---|---:|
| Total files | 21,726 |
| Total dirs | 3,008 |
| PHP files | 2,088 |
| Blade files | 132 |
| Markdown docs | 443 |
| Migrations | 95 |
| Test files | 498 |
| Route files | 23 |
| Total commits | 3,363 |
| Unique commit days | 102 |

LOC snapshot:

| Area | LOC |
|---|---:|
| `app/` PHP | 70,482 |
| `tests/` PHP | 77,205 |
| `database/` PHP | 15,440 |
| `resources/` Blade | 17,265 |
| `docs/` Markdown | 125,637 |

Commit distribution:

| Month | Commits |
|---|---:|
| 2026-03 | 397 |
| 2026-04 | 1,096 |
| 2026-05 | 947 |
| 2026-06 | 923 |

The repository is documentation-heavy and test-heavy by design. This is not accidental bloat. This app touches mutable operational state, money, stock, payment settlement, refunds, supplier invoices, and reports.

## Architecture position

The project follows a Hexagonal / Ports and Adapters direction.

High-level split:

| Layer | Role |
|---|---|
| Core | Domain rules, invariants, value objects, domain entities |
| Application | Use cases, orchestration, transactional flows |
| Ports | Contracts between application and infrastructure |
| Adapters/In | HTTP controllers, web entry points, UI-facing adapters |
| Adapters/Out | Persistence, read models, external implementation details |
| Resources/Blade | Presentation only, no embedded PHP directives |
| Database | Migrations, seeders, persistence schema |
| Tests | Feature, characterization, regression, domain and support coverage |
| Docs | ADR, blueprint, lifecycle, audit evidence, handoff/archive |

Current structural signal from `make audit-git`:

| Group | Count |
|---|---:|
| Ports | 133 |
| Adapters/In | 288 |
| Adapters/Out | 295 |
| Core | 91 |
| Application | 590 |
| test:src ratio | 498:1418 |

Strict typing:

| Signal | Value |
|---|---:|
| `strict_types` coverage | 1418 / 1418 PHP source files |
| `final class` usage | 1111 |
| Interfaces | 133 |
| `readonly` property occurrences | 1370 |
| `DateTimeImmutable` uses | 305 |

## Main business domains

### Note / Transaction domain

The Note domain is the heaviest part of the system.

It covers:

- cashier transaction workspace;
- multi-line note creation;
- store-stock product lines;
- service lines;
- external product/case-cost lines;
- service package pricing;
- inline payment lifecycle;
- closed/paid note correction;
- note revision;
- settlement carry-forward;
- surplus disposition;
- refund due;
- refund paid;
- note current projection;
- payment allocation;
- reporting consistency;
- audit timeline.

Test count signal:

- Note: 139 test files.
- Payment: 12 test files.
- Cashier: 1 test file.
- Related reporting and refund characterization tests are also spread under Reporting and Feature/Note.

### Procurement domain

Procurement covers supplier invoices and inventory receipt/costing.

It includes:

- supplier invoice creation;
- supplier invoice update/revision;
- supplier invoice version writer;
- supplier invoice version timeline;
- tax input and tax amount handling;
- rounding residue confirmation;
- received invoice cost revaluation;
- inventory movement effects;
- negative stock guard;
- supplier invoice payment proof;
- supplier payable/reporting surfaces.

Relevant recent hardening:

- supplier invoice edit reason propagation fixed;
- supplier invoice version timeline added;
- tax-only revision false negative-stock blocker fixed;
- edit draft key isolated by expected revision number;
- Blade PHP directive removed from supplier invoice version timeline;
- oversized procurement view/service files split to satisfy audit-lines.

Test count signal:

- Procurement: 56 test files.
- Biggest procurement tests include supplier invoice financial invariant and create/update/detail regression suites.

### Product / Inventory domain

Product and inventory concerns include:

- product catalog;
- stock projection;
- stock in/out movement;
- inventory value;
- stock value reporting;
- product versions;
- product edit reason;
- threshold/dashboard signal.

The system treats stock as a sensitive operational ledger, not a casual integer column.

### Reporting domain

Reporting covers:

- operational profit summary;
- transaction reports;
- refund reports;
- stock value export;
- dashboard reports;
- owner-facing report labels;
- export safety such as formula injection prevention.

Test count signal:

- Reporting: 48 test files.
- ReportingExports: 17 test files.

### Employee Finance / Expense

The app includes finance-adjacent internal modules:

- employee finance;
- payroll/debt lifecycle;
- operational expense;
- expense reporting boundaries.

Test count signal:

- EmployeeFinance: 29 test files.
- Expense: 22 test files.

### Audit / Security / Access

The project carries multiple audit/security workflows:

- audit event write path;
- transactional outbox audit runtime;
- role/capability boundaries;
- cashier/admin access separation;
- public storage helper hardening;
- XSS hardening;
- JavaScript URL hardening;
- login/rate-limit analysis;
- seeder credential boundary;
- export formula injection hardening.

Test count signal:

- AuditLog: 12 test files.
- IdentityAccess: 3 test files.
- Seeder: 2 test files.
- Auth: 2 test files.

## Documentation map

Root documentation currently includes:

- `docs/01_standards/`
- `docs/02_architecture/adr/`
- `docs/03_blueprints/`
- `docs/04_lifecycle/`
- `docs/05_audits/`
- `docs/99_archive/`

Document categories:

| Path | Purpose |
|---|---|
| `docs/01_standards/` | engineering standards, workflow rules, AI/operator rules |
| `docs/02_architecture/adr/` | architecture decisions |
| `docs/03_blueprints/` | implementation plans, matrices, source maps |
| `docs/04_lifecycle/` | active lifecycle work only |
| `docs/05_audits/` | audit reports |
| `docs/99_archive/` | closed workflow, historical handoff, old proof |

Policy:

- active lifecycle folders must not become graveyards;
- closed error logs must move to archive;
- closed runbooks and diagnostic SQL must move to archive;
- README public must not start with handoff metadata;
- technical lifecycle pointers belong here, not in public README.

## Guardrails

The project uses Makefile audits and strict workflow checks.

Important checks include:

- line limit audit;
- Blade PHP/directive audit;
- contract audit;
- static analysis;
- test suite;
- verification target.

Recent guardrail fixes:

- oversized `BuildsProcurementInvoiceDetailViewData.php` split into smaller view traits;
- oversized `SupplierInvoiceRevisionDeltaMovementsBuilder.php` split by extracting previous-line resolver;
- `@php` directive removed from supplier invoice Blade;
- collapse detail ID moved from Blade logic into view data;
- active error log archive cleanup started.

## Blade boundary

Blade is presentation only.

Forbidden:

- `@php`
- raw PHP directive
- business logic
- data assembly
- formatting logic that belongs in view-data builder

Allowed:

- rendering prepared data;
- Blade control flow for display;
- escaped output;
- simple conditional visibility.

The supplier invoice version timeline is a recent example: version detail collapse IDs must be prepared in the view data layer, not generated with `@php` in Blade.

## Timestamp policy

Application timezone remains UTC for source/storage interpretation.

Owner-facing display timezone:

- `APP_DISPLAY_TIMEZONE`
- default: `Asia/Makassar`

Rules:

- timestamp display converts to owner-facing display timezone;
- date-only business values must not be shifted;
- production timestamp repair must not run without proof;
- production diagnostic must be read-only first;
- UTC-like rows should not be repaired;
- unknown rows should not be bulk-shifted.

Recent production diagnostic result:

- MySQL runtime appeared WIB-like / UTC+7.
- Owner operational timezone is WITA / UTC+8.
- Recent audit/supplier invoice rows were UTC-like.
- Several note/refund/mutation candidate tables were empty.
- No legacy timestamp repair write is recommended.

## Current closure state

Latest resolved workflows:

### 0048

Owner-facing Indonesian cleanup and reason visibility/audit cleanup.

Status:

- archived;
- closed;
- previous closure pointer.

### 0049

Manual QA supplier invoice revision and timestamp display gap.

Closed scope:

- supplier invoice edit reason propagation;
- latest reason display;
- supplier invoice version timeline;
- tax-only revision false negative-stock blocker;
- edit draft lifecycle hardening;
- note correction history manual failure reclassification;
- timestamp display fix;
- file split for line audit;
- Blade PHP directive cleanup.

Status:

- archived;
- closed;
- latest manual QA follow-up closure.

### 0050

Legacy timestamp repair handoff.

Closed scope:

- production read-only diagnostic;
- schema timestamp type classification;
- data candidate classification;
- no repair recommendation.

Status:

- archived;
- closed;
- production timestamp repair decision record.

## Verification practice

Typical final verification pattern:

```bash
make audit-lines
make audit-blade
make verify
```

For focused procurement work:

```bash
php artisan test \
  tests/Feature/Procurement/SupplierInvoiceTaxFinancialInvariantFeatureTest.php \
  tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php \
  tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php
```

For timestamp support:

```bash
php artisan test tests/Unit/Support/ViewDateFormatterTest.php
```

For note correction history:

```bash
php artisan test --filter=CashierNoteCorrectionHistoryReasonViewFeatureTest
```

## Known tooling note

`make audit-git` currently prints the report successfully but shows an arithmetic syntax error around the final class / interface section:

```text
scripts/git_report.sh: line 119: 0
0: arithmetic syntax error in expression
```

The report still completes afterward, but the script should be cleaned later so audit output is not noisy.

## Operator rule

Public-facing explanation goes in:

- `README.md`

Technical density, lifecycle closure, audit stats, and AI/operator context go in:

- `README_TECHNICAL.md`
- `docs/`

Do not put handoff metadata at the top of public README.

Do not reopen archived lifecycle work without new concrete failing test, production bug evidence, or explicit owner instruction.
