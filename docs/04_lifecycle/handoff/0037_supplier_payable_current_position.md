# Supplier Payable Current Position Handoff

Date: 2026-09-27
Issue: https://github.com/Asyraf2003/GlassPos/issues/30
Branch: fix/supplier-payable-current-position
Baseline: 9099e9d7ba85035794006f96eb6333544a30ca44 (clean main, verified against remote)
Status: implementation verified; PR/merge gate remains open because global Blade audit fails on unchanged customer code.

## Scope and locked contract

Supplier payable read side only. No customer receivable/lifecycle code, schema, dependencies, production scheduler or cPanel changes.

Report defaults to all non-void invoices including settled invoices. Explicit period filters select shipment-date cohorts. Null/null means no period; partial bounds are rejected. Current dashboard supplier outstanding sums positive current balances across all non-void invoices using active/non-reversed payments, outside the month cache. Due date only determines urgency.

Push deep link uses RouteUrlGeneratorPort and admin.procurement.supplier-invoices.index with payment_status=outstanding, sort_by=due_date, sort_dir=asc. No shipment-date parameters. Reminder selection remains due <= today + 5 days, outstanding > 0, non-void.

## Changes

- Supplier report DTO/request, handlers, source port, source adapter and reconciliation query factory support real optional dates.
- Dedicated CurrentSupplierOutstandingReaderPort and database adapter reuse the invoice/payment query primitive; dashboard handler reads this field outside the period cache.
- HTML displays the existing paginated invoice rows: invoice, supplier, shipment, due, total, paid, outstanding, status.
- Shared filter adds an opt-in all choice for supplier report only. Dashboard supplier caption/link reflect current balance.
- PDF/Excel use the same all/filtered dataset; all receives explicit labels and filenames. Explicit-period range guards remain.
- Push payload targets operational invoices through the existing URL port.
- Cross-surface tests, lifecycle reversal projection checks and command readiness tests added. FC-001 stale absence claims replaced.

## Executed proof

Execution context for all repo commands: /home/asus/projects/GlassPos.

Focused command:

    php artisan test --compact tests/Feature/Reporting/SupplierPayableCurrentPositionFeatureTest.php tests/Feature/ReportingExports/SupplierPayableAllPeriodExportFeatureTest.php tests/Feature/PushNotification/SupplierPayableReminderReadinessFeatureTest.php tests/Feature/Procurement/ReverseSupplierPaymentFeatureTest.php

Result: 13 passed, 142 assertions. Final adjacent regression also includes these tests.

Adjacent command:

    php artisan test --compact tests/Feature/Procurement tests/Feature/Reporting tests/Feature/ReportingExports tests/Feature/PushNotification tests/Feature/Admin

Final result: 546 passed, 4462 assertions (35.04 seconds).

    ./vendor/bin/phpstan analyze --memory-limit=-1 --no-progress

Result: no errors. A test auth-factory type error found during iteration was corrected to use its guard before final verification.

    php scripts/audit-line-count.php
    git diff --check

Result: pass. Changed Blade files (4) and changed application/port imports were additionally checked against the repository's PHP-directive and framework-boundary rules: no violations.

    make audit-contract

Result: FAIL on resources/views/shared/notes/partials/status-badge.blade.php lines 1 and 16 (@php/@endphp). Exact same failure reproduced by exporting origin/main's views/audit script into /tmp/glasspos-payable-baseline-audit and running its script, exit 1. This file is unchanged by this branch. No audit bypass or customer fix was introduced. Under the owner's all-green merge gate, do not merge until the baseline audit is resolved or the owner explicitly changes that gate.

## Semantic proof

- August 10000000 invoice with active 2000000 payment: September default report contains invoice, 8000000 outstanding and overdue status. September explicit monthly report excludes it.
- September, October and historical dashboard month retain the same 8000000. After another active 1000000 payment, cached September/October requests both report 7000000.
- Reversed full payment restores 10000000 across current balance, operational list and reminder. Existing real reversal HTTP lifecycle test additionally verifies current balance, reminder and projection-backed list without manual projection refresh after reversal.
- Settled remains in report all with zero balance; excluded from operational outstanding/reminder. Void excluded from active payable.
- HTML, actual PDF generation/view data and XLSX cell values agree for all, explicit August and explicit September. Existing explicit-range rejection tests pass.

## Browser proof

Local Chromium/Playwright, isolated MySQL database glasspos_supplier_payable_proof_20260927, loopback 127.0.0.1:8098, fixed date 2026-09-27. Local /tmp router injects a fixture admin; this does not test login or real push delivery.

Command:

    LD_LIBRARY_PATH=/tmp/glasspos-dashboard-proof/lib node /tmp/glasspos-payable-proof/browser.mjs

Both light and dark passed:

1. Open report without query: all selected, August overdue row shows 8000000, settled row visible.
2. Use filter drawer to select monthly September: August row disappears, September row remains.
3. Open dashboard months 2026-09, 2026-10, 2025-01: supplier balance stays 9000000 (8000000 overdue plus 1000000 future outstanding).
4. Open the URL generated by the push factory: operational list shows old overdue and future outstanding, excludes settled, due-date ascending, active chip Masih Punya Tagihan. Browser table request contains no shipment-date bounds.
5. No page JavaScript errors; operational page has no document overflow. Screenshots reviewed.

Persistent result: docs/03_blueprints/reporting/evidence/supplier_payable/browser-results.json.
Local artifacts/scripts/screenshots: /tmp/glasspos-payable-proof/.

## Cron readiness, not setup

Run later from the actual GlassPos deployment root with its configured PHP/environment:

    php artisan push-notifications:send-supplier-payable-reminders

Deterministic command verified against the isolated fixture database:

    APP_ENV=testing DB_DATABASE=glasspos_supplier_payable_proof_20260927 php artisan push-notifications:send-supplier-payable-reminders --today=2026-09-27 --no-interaction

Exit 0, summary: reminders 1; subscriptions 0; sent 0; expired 0; failed 0. No real push was sent. Invalid 2026-02-30 exits 1. Tests with fake transport verify successful, failed (exit 1), expired-subscription and empty selection behavior, no authenticated guard requirement, default today, --today and unchanged finance tables.

Operational limits intentionally unchanged:

- Default invoice-limit=100 and subscription-limit=500. With 101 eligible invoices and 501 active subscriptions, one invocation reports 100 reminders and sends to 500 unique subscriptions, exit 0. Excess invoices are not included in the payload summary; excess subscriptions receive nothing. No truncation warning or batching exists.
- Multiple payment rows do not duplicate invoice reminders. One invocation sends once per selected subscription.
- Second invocation on the same date can resend to the same active subscription. Same daily tag is not persistent deduplication. Owner explicitly deferred this decision.
- No schema/idempotency subsystem added. No cPanel or server scheduler configured. Production PHP path, working directory, timezone, credentials and actual delivery must be verified during deployment/cron setup.
- All-period exports materialize all rows; production volume/load behavior is not proven by these fixture tests.

## Next

Review PR and the baseline Blade audit blocker. Keep customer work out of this branch. Merge only after the owner's gate is satisfied; no merge SHA exists yet.
