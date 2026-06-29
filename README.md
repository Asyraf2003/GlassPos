# HyperPOS

HyperPOS is a workshop POS and operations system built with Laravel and MySQL.

It is designed for a real automotive workshop workflow where a "simple sale" can actually involve service jobs, spare parts, supplier invoices, stock movement, partial payments, refunds, corrections, audit history, and business reports.

This project exists because many POS systems look clean on the screen while quietly making money, stock, and reports drift apart. HyperPOS is built to make those changes traceable.

## What Problem Does It Solve?

A workshop transaction is rarely just:

> sell item, print receipt, done.

In a real workshop:

- one invoice can include service labor and spare parts;
- parts may come from store stock or outside purchase;
- a customer can pay partially, then pay the rest later;
- a transaction may need correction after it was paid;
- a refund can affect cash, stock, and reports;
- supplier invoices can affect stock cost and payable balance;
- reports must still make sense after all of those changes.

HyperPOS tries to handle that mess explicitly instead of hiding it behind a generic "Save" button.

## Core Capabilities

### Cashier Transaction Workspace

Cashiers can create multi-line workshop transactions that may include:

- product-only sales;
- service-only work;
- service with store-stock spare parts;
- service with external purchase / case-cost parts;
- package/template-based service rows;
- cash or transfer payment;
- partial or full payment.

The workspace is built to keep transaction details structured, because once money and stock are involved, vague data becomes expensive. Humans keep inventing edge cases. Software gets blamed for not reading minds. Naturally.

### Payment Lifecycle

HyperPOS tracks payment as part of the transaction lifecycle, not as a loose number.

It supports:

- full payment;
- partial payment;
- cash payment;
- transfer payment;
- payment allocation;
- paid-note closing;
- payment visibility in transaction and cash reports.

The goal is to prevent the common POS failure where the screen says "paid", the database says "maybe", and the report says "good luck".

### Refund and Correction Flow

Refunds and corrections are treated as business events.

The system is built to preserve context when a transaction changes after creation or after payment. That includes:

- selected-row refunds;
- full refund lifecycle;
- paid transaction correction;
- revision history;
- refund impact on reports;
- refund impact on stock where applicable;
- protection against non-refundable items being treated as refundable.

### Product and Stock Management

Stock is treated as operational ledger data, not just a number in a product table.

HyperPOS covers:

- product catalog;
- stock adjustment;
- stock adjustment reversal;
- stock movement history;
- stock projection rebuild;
- inventory costing projection;
- negative-stock guardrails;
- product versioning;
- soft delete and restore.

This matters because a workshop can survive a messy UI faster than it can survive invisible stock drift.

### Supplier and Procurement

The system includes supplier invoice workflows, including:

- supplier invoice creation;
- supplier invoice edit and revision;
- supplier invoice version timeline;
- supplier receipt;
- supplier payment;
- supplier payment reversal;
- supplier payment proof upload;
- supplier payable reporting;
- tax input handling;
- landed-cost allocation;
- rounding residue handling;
- received invoice cost revaluation.

Supplier invoices affect inventory and payable balance, so they are not treated as isolated documents.

### Reporting

HyperPOS includes reporting surfaces for operational visibility:

- transaction summary;
- transaction cash ledger;
- operational profit;
- inventory stock value;
- supplier payable;
- service package profit breakdown;
- employee debt;
- payroll;
- operational expense;
- dashboard summaries;
- PDF exports;
- Excel exports.

The reporting goal is not just "show a table". The goal is to keep the report explainable after payment, refund, revision, inventory movement, and supplier changes.

### Audit and History

The system keeps audit and history as first-class concerns.

Important changes are expected to be explainable:

- what changed;
- when it changed;
- why it changed, where relevant;
- what the previous state was;
- how the change affected money, stock, and reports.

That is why the repository contains ADRs, lifecycle logs, handoffs, error logs, regression notes, and manual QA matrices. It is paperwork, yes. But the alternative is guessing. Guessing is just debugging with a blindfold and confidence issues.

## Screenshots

### Cashier Flow

<table>
  <tr>
    <td width="33%">
      <img src=".github/assets/readme/cashier-dashboard.png" alt="Cashier dashboard">
    </td>
    <td width="33%">
      <img src=".github/assets/readme/cashier-create-note.png" alt="Cashier create note">
    </td>
    <td width="33%">
      <img src=".github/assets/readme/cashier-note-detail.png" alt="Cashier note detail">
    </td>
  </tr>
</table>

### Admin and Reporting

<img src=".github/assets/readme/admin-dashboard.png" alt="Admin dashboard">

<img src=".github/assets/readme/dashboard-report.png" alt="Dashboard report">

<img src=".github/assets/readme/admin-product-table.png" alt="Admin product table">

### Payment and Export

<table>
  <tr>
    <td width="50%">
      <img src=".github/assets/readme/admin-supplier-payment-proof.png" alt="Supplier payment proof">
    </td>
    <td width="50%">
      <img src=".github/assets/readme/report-export-excel.png" alt="Excel export">
    </td>
  </tr>
</table>

## Production Context

HyperPOS has been operated as a live Laravel/MySQL application for a real workshop environment.

Owner-reported operation metadata:

| Signal | Value |
|---|---:|
| Runtime | Laravel + MySQL |
| Deployment style | Shared hosting / constrained hosting |
| MySQL update cycles while live | 12 |
| File update cycles while live | 31 |
| User-visible downtime during those update cycles | 0 reported |
| Production data in this repository | None, metadata only |

The repository does not contain production database dumps, private customer data, credentials, or operational secrets.

Production repair policy is conservative: diagnose read-only first, then decide. No blind mutation of production data.

## Architecture

HyperPOS follows a Hexagonal / Ports and Adapters direction.

The project separates:

- domain rules;
- use cases;
- ports/contracts;
- HTTP controllers;
- database adapters;
- reporting queries;
- Blade presentation;
- tests;
- documentation.

The point is to keep business rules from being buried inside controllers, views, and random SQL fragments. Revolutionary, apparently.

## Documentation

- [`README_SETUP.md`](README_SETUP.md) — local installation, seed data, demo login, and verification.
- [`README_TECHNICAL.md`](README_TECHNICAL.md) — architecture, domain boundaries, tests, and production metadata.
- [`docs/0001_docs_help.md`](docs/0001_docs_help.md) — entrypoint for standards, ADRs, blueprints, lifecycle logs, and archives.

## Engineering Highlights

HyperPOS includes work around:

- multi-item transaction lifecycle;
- edit-after-payment handling;
- refund and payment allocation;
- inventory movement and reversal;
- supplier invoice versioning;
- supplier tax and landed cost handling;
- service package auto-split;
- owner-facing report labels;
- PDF and Excel report consistency;
- audit trail;
- transactional audit outbox;
- role and access boundary;
- XSS and public-surface hardening;
- idempotency and repeated-submit protection;
- timestamp display for Asia/Makassar;
- read-only production diagnostics.

## Failure Cases Considered

The system is designed and tested around failure modes that happen in real usage:

- double-click create;
- double-click payment;
- double-click refund;
- browser refresh after submit;
- browser back after submit;
- repeated submit;
- malformed numeric input;
- zero or excessive payment input;
- over-refund attempt;
- stale modal data;
- stale report projection;
- negative stock risk;
- UI action visible but backend allocation rejects it;
- screen report disagreeing with PDF or Excel export;
- production timestamp confusion.

These are boring problems until they touch money. Then suddenly everyone becomes a philosopher.

## Testing and Verification

This repository uses a large test and documentation workflow because the domain is interconnected.

Typical verification commands:

```bash
make help
make audit-git
make audit-lines
make audit-blade
make audit-contract
make verify
```

Focused areas include:

```bash
php artisan test tests/Feature/Note
php artisan test tests/Feature/Payment
php artisan test tests/Feature/Procurement
php artisan test tests/Feature/Reporting
php artisan test tests/Feature/ReportingExports
php artisan test tests/Unit
php artisan test tests/Arch
```

## Who This Repository Is For

This repository is useful for:

- recruiters reviewing backend/domain complexity;
- developers studying Laravel beyond CRUD;
- engineers interested in transaction lifecycle design;
- auditors looking at money/stock/report consistency;
- business owners who want to understand why POS reliability matters.

## Current Status

HyperPOS is actively developed and hardened.

Recent focus areas include:

- manual QA for transaction lifecycle and reports;
- owner-facing language cleanup;
- supplier invoice revision hardening;
- timestamp display correctness;
- production-safe read-only diagnostics;
- technical and public README cleanup.

## Final Note

HyperPOS is dense because the business problem is dense.

A POS that handles money, stock, debt, payable balance, refunds, corrections, and reports cannot stay reliable by pretending everything is a basic create-read-update-delete screen.

CRUD is the alphabet. This project is about what happens after the alphabet starts touching cash.
