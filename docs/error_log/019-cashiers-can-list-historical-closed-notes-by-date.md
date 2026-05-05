# 019 - Cashiers can list historical closed notes by date

## Status

Patched, with verification gap.

## Severity

High.

## Summary

The cashier note history table accepted a client-controlled `date` query parameter and used it as the anchor date for the two-day cashier history window. Because `/cashier/notes/table` was protected only by cashier-area and transaction-entry middleware, and was not inside the `EnsureCashierNoteAccess` per-note date-window guard, an authenticated cashier could query arbitrary historical windows.

The issue became higher impact after the cashier history query changed from `openOnly=true` to `openOnly=false`. With `openOnly=false`, the shared note history rows query no longer filtered `notes.note_state = open`, so closed historical notes were returned.

The JSON table response disclosed sensitive cashier-facing note data, including note IDs, transaction dates, customer labels/names/phones, grand totals, paid totals, outstanding totals, line summary counts, payment labels, work labels, and action URLs.

## Vulnerable path

Authenticated cashier session
-> GET /cashier/notes/table?date=2025-01-15
-> route passes auth, cashier-area, transaction-entry middleware
-> request validates client-controlled date format
-> controller forwards validated filters
-> CashierNoteHistoryCriteria uses client date as anchor
-> query searches previousDate..anchorDate
-> CashierNoteHistoryBaseQuery passes openOnly=false
-> NoteHistoryRowsQuery does not filter notes.note_state='open'
-> historical closed customer/financial note summaries are returned

## Root Cause

The table endpoint treated a client-supplied date as a trusted anchor for cashier history retrieval.

The endpoint also relied on broad cashier/transaction middleware instead of enforcing the intended cashier today/yesterday access boundary at the query level.

The change from `openOnly=true` to `openOnly=false` expanded the leak from open historical notes to closed historical notes.

## Patch Summary

`app/Adapters/Out/Note/Queries/CashierNoteHistoryCriteria.php` was changed so the cashier history anchor date always uses the server's current date.

Client-supplied `date` input is no longer used to choose the query window.

A regression test was added in:

`tests/Feature/Note/CashierNoteHistoryTableClosurePolicyFeatureTest.php`

The test passes an arbitrary historical date (`2025-01-15`) and asserts that the query still returns only today/yesterday notes while excluding older notes.

## Verification

Attempted:

`php artisan test --filter=CashierNoteHistoryTableClosurePolicyFeatureTest`

Result:

Failed in the reported environment because `vendor/autoload.php` is missing and dependencies are not installed.

## Verification Gap

The patch is source-level reviewed from the submitted report and patch summary, but the regression test is not proven passing in this environment.

A future verification run must install dependencies or run in the project environment, then execute:

`php artisan test --filter=CashierNoteHistoryTableClosurePolicyFeatureTest`

Recommended additional proof:

`php artisan route:list --path=cashier/notes`

to confirm route middleware ordering and guard placement.

## Relations

Related to #009, #011, #015, and #018 as part of the cashier access-boundary cluster.

Different from those reports because this issue is read-only historical data disclosure through the cashier table endpoint, not mutation/edit/refund workspace authorization.

Related to #018 because both involve cashier access logic around closed/refunded note boundaries, but #019 specifically concerns date-window enumeration and closed historical note listing.
