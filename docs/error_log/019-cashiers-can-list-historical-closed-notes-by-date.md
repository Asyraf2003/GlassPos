# 019 - Cashiers can list historical closed notes by date

## Status

Fixed with proof and explicit residual global/browser gaps.

## Severity

High.

## Summary

The cashier note history table previously accepted a client-controlled `date` query parameter and used it as the anchor date for the cashier two-day history window.

That allowed an authenticated cashier to call:

`GET /cashier/notes/table?date=2025-01-15`

and force the table query to search a historical window selected by the client.

The disclosure risk became larger after cashier history moved away from an open-only query. The table can legitimately show closed notes in the current cashier window, but it must not let the cashier enumerate arbitrary historical closed notes by choosing the anchor date.

The JSON table response can disclose cashier-facing note data, including note IDs, transaction dates, customer labels/names/phones, grand totals, paid totals, outstanding totals, line summary counts, payment labels, work labels, and action URLs.

## Vulnerable Path

Authenticated cashier session

- `GET /cashier/notes/table?date=2025-01-15`
- route passes `web`
- route passes `auth`
- route passes `EnsureCashierAreaAccess`
- route passes `EnsureTransactionEntryAllowed`
- route reaches `NoteHistoryTableDataController`
- request validates the client-controlled `date` format
- controller forwards validated filters
- `CashierNoteHistoryCriteria` used the client date as the anchor
- query searched `previousDate..anchorDate`
- historical closed customer/financial note summaries could be returned

## Root Cause

`CashierNoteHistoryCriteria::resolveAnchorDate()` trusted a valid client-supplied `date`.

Before the fix, the code path was:

- `CashierNoteTableQueryRequest` accepted nullable `date`
- `NoteHistoryTableDataController` forwarded `$request->validated()`
- `CashierNoteHistoryTableQuery` called `CashierNoteHistoryCriteria::fromFilters($filters)`
- `CashierNoteHistoryCriteria::resolveAnchorDate($filters['date'] ?? null)` parsed and returned a valid client date
- `CashierNoteHistoryBaseQuery` used the resulting anchor and previous date in `whereBetween('note_history_projection.transaction_date', ...)`

The route-level middleware was not enough to enforce the cashier date-window boundary because the vulnerable behavior was a query-window trust issue, not a per-note route authorization issue.

## Source Reality Before Fix

Current source contradicted the old document status.

The document previously said `Patched, with verification gap`, but local source still accepted the client date as the trusted anchor:

- `app/Adapters/Out/Note/Queries/CashierNoteHistoryCriteria.php`
  - `resolveAnchorDate(mixed $value)` parsed valid `Y-m-d` client input.
  - if parsing succeeded, the parsed client date was returned.
  - only invalid or missing input fell back to `new DateTimeImmutable(date('Y-m-d'))`.

Therefore #019 was a real source vulnerability, not only a verification gap.

## Production Patch

Changed production file:

- `app/Adapters/Out/Note/Queries/CashierNoteHistoryCriteria.php`

Patch behavior:

- cashier history anchor date is always derived from the server-side current date
- client-supplied `date` no longer controls the cashier history query window
- `search`, `line_status`, and `page` filtering remain preserved
- closed notes remain allowed inside the legitimate today/yesterday cashier window
- historical arbitrary closed notes are not returned by choosing a historical `date`

The final source anchor is:

- `CashierNoteHistoryCriteria::fromFilters()` calls `self::resolveAnchorDate()`
- `resolveAnchorDate()` returns `new DateTimeImmutable(date('Y-m-d'))`

## Test Patch

Changed test files:

- `tests/Feature/Note/CashierNoteHistoryTableClosurePolicyFeatureTest.php`
- `tests/Feature/Note/CashierNoteHistoryTableFeatureTest.php`

Added/updated coverage:

- service/query-level regression proving client historical date is ignored
- direct HTTP JSON endpoint regression proving `/cashier/notes/table?date=2025-01-15` still returns server today/yesterday data
- existing cashier history table test updated so it no longer expects the client date to become the response filter
- existing current-window behavior still allows today/yesterday notes without forcing open-only behavior

## RED Proof

Command:

`php artisan test tests/Feature/Note/CashierNoteHistoryTableClosurePolicyFeatureTest.php`

Result before production patch:

- 1 failed
- 1 passed
- 5 assertions

Failure shape:

Expected server anchor date:

`2026-05-10`

Actual client-controlled anchor date:

`2025-01-15`

Failure line:

`tests/Feature/Note/CashierNoteHistoryTableClosurePolicyFeatureTest.php:74`

Assertion:

`$this->assertSame($today, $result['filters']['date']);`

This RED proof matched the root cause: the cashier history query still trusted the client-supplied historical date.

## Targeted GREEN Proof

After the production patch:

Command:

`php artisan test tests/Feature/Note/CashierNoteHistoryTableClosurePolicyFeatureTest.php`

Result:

- 2 passed
- 8 assertions

Covered:

- today and yesterday notes are still returned
- current-window closed notes are still allowed
- older notes are excluded
- client-supplied historical date is ignored when building the cashier window

## Direct HTTP GREEN Proof

Command:

`php artisan test tests/Feature/Note/CashierNoteHistoryTableClosurePolicyFeatureTest.php`

Result after adding direct endpoint regression:

- 3 passed
- 14 assertions

Covered:

- `/cashier/notes/table?date=2025-01-15` returns HTTP 200 for an authenticated cashier
- response `data.filters.date` uses the server current date
- today note is present
- yesterday note is present
- historical closed note is absent

This proves the security boundary is enforced server-side in the endpoint path, not by UI hiding.

## Focused / Blast-Radius Proof

Command:

`php artisan test tests/Feature/Note/CashierNoteHistoryTableClosurePolicyFeatureTest.php tests/Feature/Note/CashierNoteHistoryTableFeatureTest.php tests/Feature/Note/CashierNoteHistoryPageFeatureTest.php tests/Feature/Note/CashierNoteHistoryLegacyLineSummaryFeatureTest.php`

Result:

- 6 passed
- 34 assertions

Covered:

- #019 closure policy regression
- cashier note history JSON table behavior
- cashier note history shell page access
- legacy fully paid note line summary behavior

## Route Proof

Command:

`php artisan route:list --path=cashier/notes -v`

Relevant route:

`GET|HEAD cashier/notes/table cashier.notes.table`

Middleware shown:

- `web`
- `auth`
- `App\Adapters\In\Http\Middleware\IdentityAccess\EnsureCashierAreaAccess`
- `App\Adapters\In\Http\Middleware\IdentityAccess\EnsureTransactionEntryAllowed`
- `app.shell`

The table route is a list endpoint and is not guarded by per-note `EnsureCashierNoteAccess`.

Closure decision:

- #019 is fixed at the query-window boundary.
- The server-side query now ignores client date for cashier history.
- Per-note access middleware is not the right primary boundary for this table endpoint.

## UI / Blade Impact

No Blade file was changed for #019.

The issue is a JSON table query-window disclosure problem. The fix is server-side.

UI hiding is not used as a security boundary.

## Native JS Impact

No native JavaScript file was changed for #019.

Any client-side date picker or request parameter is treated as non-authoritative for the cashier history window.

The server remains the source of truth for the cashier date window.

## Security / Authorization Impact

Security decision:

- authenticated cashier can still access the cashier table endpoint
- cashier cannot choose arbitrary historical anchor dates
- server-side date window controls returned notes
- closed notes can still appear inside the legitimate current cashier window
- arbitrary historical closed notes are excluded

The route-level access gates remain:

- authenticated user required
- cashier area access required
- transaction entry allowed required

The #019 fix does not claim to solve per-note mutation access. Those are handled by other Slice 4 and Slice 5 issues.

## Audit / Logging / Redaction Impact

No mutation occurs in #019.

No audit writer was changed.

No new logging path was added.

No secret, token, private path, or proof attachment data is involved in this issue.

The sensitive part of #019 is read-side disclosure, and the closure proof focuses on preventing historical JSON response disclosure.

## Relations

Related to #009, #011, #015, and #018 as part of the cashier access-boundary cluster.

Different from those issues because #019 concerns read-only table disclosure through a cashier history endpoint, not workspace edit/revision/refund mutation authorization.

Related to #022 because both involve cashier historical-note access boundaries:

- #019 covers read-side historical closed note enumeration through the table endpoint.
- #022 covers cashier refund route access guard behavior for note-specific refund requests.

## Residual Gaps

Not proven in this #019 closure:

- full global suite
- browser/manual QA
- timezone abstraction beyond current project usage of `date('Y-m-d')`
- full ADR-0019 route matrix for all cashier note routes
- #022 cashier refund route behavior
- #020 and #027 transaction capability route coverage
- final global verification across all error logs

These are explicit residual gaps and must not be described as globally verified by this closure.

## Closure Decision

#019 is fixed for the scoped vulnerability:

- client-supplied historical `date` no longer controls the cashier history table window
- direct cashier table endpoint proof passes
- focused cashier history blast-radius proof passes
- documentation now records the old source contradiction, RED proof, production patch, GREEN proof, focused proof, and residual gaps

Final scoped status:

`Fixed with proof and explicit residual global/browser gaps.`
