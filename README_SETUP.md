# HyperPOS Setup Guide

This guide is for running HyperPOS locally for review, development, QA, and portfolio inspection.

For technical architecture, see:

- [`README_TECHNICAL.md`](README_TECHNICAL.md)

For the public overview, see:

- [`README.md`](README.md)

For the full documentation map, see:

- [`docs/0001_docs_help.md`](docs/0001_docs_help.md)

## Requirements

HyperPOS is a Laravel application.

Minimum local requirements:

- PHP 8.2 or newer
- Composer
- Node.js and npm
- MySQL or compatible local database
- Git
- `make`
- `rg` / ripgrep, recommended for repository inspection

The project uses:

- Laravel 12
- Blade
- MySQL
- Pest
- PHPStan
- Laravel Pint
- DomPDF
- PhpSpreadsheet
- Web Push support

## Clone

```bash
git clone https://github.com/Asyraf2003/hyperpos.git
cd hyperpos
```

## Install Dependencies

```bash
composer install
npm install
```

## Environment

Create local environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure your local database.

Example:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hyperpos
DB_USERNAME=root
DB_PASSWORD=
```

Create the database manually before migrating.

Example:

```sql
CREATE DATABASE hyperpos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Migrate

```bash
php artisan migrate
```

## Seed Local Demo Data

For normal local QA and review, use the audited create-only dataset:

```bash
make create-all-v3
```

This creates local demo users, master data, operational source data, audit baseline rows, and projection rebuilds.

Useful seed help commands:

```bash
make seed-help
make seed-help-full
```

Load-profile aliases:

```bash
make seed-load-real
make seed-load-peak
make seed-load-stress
```

Use heavy load targets only when your local machine and database can handle it. Yes, making your laptop cry is technically a benchmark, but not a useful one.

## Demo Login

After running local user seed data, these demo accounts are available:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@gmail.com` | `12345678` |
| Cashier | `kasir@gmail.com` | `12345678` |

These credentials are for local/testing only. Do not use them in production.

## Run the Application

For backend only:

```bash
php artisan serve
```

For frontend assets:

```bash
npm run dev
```

Or build production-like assets:

```bash
npm run build
```

Then open:

```text
http://127.0.0.1:8000
```

## Verification

Show available commands:

```bash
make help
```

Run repository verification:

```bash
make audit-git
make audit-lines
make audit-blade
make audit-contract
make verify
```

Focused test examples:

```bash
php artisan test tests/Feature/Note
php artisan test tests/Feature/Payment
php artisan test tests/Feature/Procurement
php artisan test tests/Feature/Reporting
php artisan test tests/Feature/ReportingExports
php artisan test tests/Unit
php artisan test tests/Arch
```

Timestamp display support:

```bash
php artisan test tests/Unit/Support/ViewDateFormatterTest.php
```

## Documentation Reading Order

Do not start by reading every file in `docs/`. That is how optimism dies.

Recommended order:

1. `README.md`  
   Public overview of the product and problem.
2. `README_SETUP.md`  
   Local setup, seed data, demo login, and verification.
3. `README_TECHNICAL.md`  
   Architecture, density, domain boundaries, failure classes, and production-operation metadata.
4. `docs/0001_docs_help.md`  
   The entrypoint for navigating standards, ADRs, blueprints, lifecycle logs, and archive.
5. Active lifecycle file only if you are continuing current work:  
   `docs/04_lifecycle/error_log/0051_manual_transaction_reporting_sequential_qa_matrix.md`

## Screenshot Assets

README screenshots are stored under:

```text
.github/assets/readme/
```

Current public README image assets:

| Asset | Purpose |
|---|---|
| `.github/assets/readme/cashier-dashboard.png` | Cashier dashboard |
| `.github/assets/readme/cashier-create-note.png` | Cashier create-note workspace |
| `.github/assets/readme/cashier-note-list.png` | Cashier note list |
| `.github/assets/readme/cashier-note-detail.png` | Cashier note detail |
| `.github/assets/readme/admin-supplier-payment-proof.png` | Admin supplier payment proof |
| `.github/assets/readme/admin-dashboard.png` | Admin dashboard |
| `.github/assets/readme/dashboard-report.png` | Dashboard report surface |
| `.github/assets/readme/admin-product-table.png` | Admin product table |
| `.github/assets/readme/operational-cash-profit-report.png` | Operational cash/profit report screen |
| `.github/assets/readme/report-export-pdf.png` | PDF export preview |
| `.github/assets/readme/report-export-excel.png` | Excel export preview |

Only place screenshots in the public README when they explain product capability. Put deeper proof screenshots in lifecycle docs or audit evidence.

## Production Safety

This repository does not contain production database dumps, private customer data, credentials, or operational secrets.

Production data repair policy:

1. Diagnose read-only first.
2. Identify exact affected tables and rows.
3. Prove whether the issue is display, source data, or schema interpretation.
4. Never run blind write queries against production.

Date-only business fields must not be shifted as timestamp repair.

## Common Local Reset

For local-only rebuild:

```bash
php artisan migrate:fresh
make create-all-v3
```

Do not use destructive reset commands against production.
