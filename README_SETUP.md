# GlassPos Setup Guide

Use this guide to run GlassPos locally for development, QA, technical review, or portfolio inspection.

- Product overview: [`README.md`](README.md)
- Engineering details: [`README_TECHNICAL.md`](README_TECHNICAL.md)
- Documentation map: [`docs/0001_docs_help.md`](docs/0001_docs_help.md)

## Requirements

Minimum local requirements:

- PHP 8.2+
- Composer
- MySQL or a compatible local database
- Git
- `make`
- `rg` / ripgrep, recommended for repository inspection

Main stack:

- Laravel 12
- Blade
- MySQL
- Pest
- PHPStan
- Laravel Pint
- DomPDF
- PhpSpreadsheet
- Web Push support

The current frontend runtime uses committed assets under `public/assets/compiled` and `public/assets/static`, so Node.js, npm, and a Vite build are not required just to run the application locally.

## Clone

```bash
git clone https://github.com/Asyraf2003/GlassPos.git
cd GlassPos
```

## Install Dependencies

```bash
composer install
```

## Environment

Create the local environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure a local database in `.env`:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=glasspos
DB_USERNAME=root
DB_PASSWORD=
```

Create the database before migrating. Example:

```sql
CREATE DATABASE glasspos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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

This prepares local demo users, master data, operational source data, audit baseline rows, and projection rebuilds.

Useful seed commands:

```bash
make seed-help
make seed-help-full
make seed-load-real
make seed-load-peak
make seed-load-stress
```

The heavier profiles are intended for deliberate load testing, not as a ceremonial way to make a laptop overheat.

## Demo Login

After local user seed data is created:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@gmail.com` | `12345678` |
| Cashier | `kasir@gmail.com` | `12345678` |

These are local/testing credentials only. Do not reuse them in production.

## Run the Application

```bash
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

Frontend CSS and JavaScript are served from the committed compiled/static assets; no separate frontend development server is required for the current setup.

## Verification

List repository commands:

```bash
make help
```

Run the standard verification gates:

```bash
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

## Common Local Reset

For a local-only rebuild:

```bash
php artisan migrate:fresh
make create-all-v3
```

Never use destructive reset commands against production.

## Production Safety

This public repository does not contain production database dumps, private customer data, production credentials, or operational secrets.

For production diagnosis:

1. inspect read-only first;
2. identify the exact affected tables and rows;
3. distinguish display problems from source-data or schema problems;
4. prove the required correction before any write;
5. never run blind mutation queries against production.

Date-only business fields must not be shifted as part of timestamp repair.

## Recommended Reading Order

1. [`README.md`](README.md) — product and portfolio overview.
2. [`README_SETUP.md`](README_SETUP.md) — local setup and verification.
3. [`README_TECHNICAL.md`](README_TECHNICAL.md) — architecture, engineering evidence, domains, and failure classes.
4. [`docs/0001_docs_help.md`](docs/0001_docs_help.md) — standards, ADRs, blueprints, lifecycle evidence, audits, and archive navigation.

Do not start by reading every file under `docs/`. The repository has 491 tracked Markdown files. There are more dignified ways to lose an afternoon.
