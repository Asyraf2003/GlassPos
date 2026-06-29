# 0050 - Legacy Timestamp Repair Handoff

## Context

This handoff continues from error log:

- `docs/04_lifecycle/error_log/0049_manual_qa_supplier_invoice_revision_and_timezone_gap.md`

The supplier invoice revision/reason/draft/history issues have been fixed and documented.

The note correction history manual failure was reclassified as likely manual data/setup mismatch because the existing automated test passed:

- `CashierNoteCorrectionHistoryReasonViewFeatureTest`
- 2 tests passed
- 19 assertions passed

The remaining topic is timestamp mismatch / production legacy timestamp repair.

## Current Known Proof

- `ViewDateFormatterTest` passed.
- `ViewDateFormatter` displays database timestamp `2026-06-29 02:07:45` as `29 Juni 2026 10:07`.
- Date-only business values are not shifted.
- `config/app.php` keeps app timezone as `UTC`.
- `config/app.php` has owner-facing display timezone:
  - `APP_DISPLAY_TIMEZONE`
  - default `Asia/Makassar`.

## Production Constraint

- Real production is on shared hosting.
- Lab environment is separate.
- Do not execute production write from lab assumptions.
- User does not rely on tinker for this production path.
- Need a safe plan that can later be translated into shared-hosting execution steps.

## Problem

Production legacy timestamps may be inconsistent:

- Some rows may be stored as UTC-like values.
- Some rows may be stored as local Asia/Makassar-like values.
- Blindly adding or subtracting 8 hours can corrupt audit and financial history.

## Hard Rule

Do not bulk-shift date-only business fields:

- `refunded_at`
- `transaction_date`
- `shipment_date`
- `due_date`
- `expense_date`
- `payment_date`

These are business dates and must not move across days.

## Candidate Timestamp Fields For Diagnosis

Audit/history fields only:

- `audit_events.occurred_at`
- `audit_events.created_at`
- `note_mutation_events.occurred_at`
- `note_revision_surplus_dispositions.occurred_at`
- `note_revision_surplus_dispositions.created_at`
- `note_revision_surplus_refund_payments.occurred_at`
- `note_revision_surplus_refund_payments.created_at`
- note revision `created_at`
- supplier invoice history `occurred_at` / `created_at`

## Recommended Next Step

Create a lab-only diagnostic, not a repair.

The diagnostic should:

1. Print:
   - `config('app.timezone')`
   - `config('app.display_timezone')`
   - `now()`
   - `now(config('app.display_timezone'))`
2. Sample recent rows from candidate tables.
3. Show raw timestamp and formatted display timestamp side by side.
4. Avoid writes.
5. Work without tinker, preferably via:
   - temporary artisan command, or
   - one-off route disabled by default, or
   - CLI script inside `php artisan`.

## Acceptance Criteria For Diagnostic

- Running diagnostic does not mutate database.
- Output clearly separates:
  - raw DB timestamp;
  - owner-facing formatted timestamp;
  - table name;
  - row id;
  - event label/type if available.
- Date-only fields are not included in repair candidates.
- Diagnostic can be copied/run later on shared hosting after backup.

## Later Production Execution Rules

Before any write on production:

1. Export full database backup.
2. Run diagnostic read-only.
3. Compare sample rows to known real action times from owner memory/manual log.
4. Decide whether rows are:
   - UTC-like,
   - local-like,
   - mixed/unknown.
5. If mixed/unknown, do not bulk repair.
6. If repair is needed, repair only a narrow proven set.
7. Re-run diagnostic and UI checks after repair.

## Strong Non-Goal

Do not create global migration that updates every timestamp column.


## 2026-06-29 Production Read-only Diagnostic Result

Status: production read-only diagnostic completed. No repair recommended.

### Scope

Diagnostic was run against production shared-hosting database using SQL `SELECT` only.

No production write query was executed.

### Runtime Finding

Production MySQL runtime showed:

- `UTC_TIMESTAMP()` matched UTC.
- `NOW()` was 7 hours ahead of UTC.
- Live owner local time matched `UTC_TIMESTAMP() + INTERVAL 8 HOUR`.

Interpretation:

- Production MySQL session/runtime appears WIB-like / UTC+7.
- Owner operational display timezone remains Asia/Makassar / UTC+8.
- Runtime timezone alone must not be used as proof to shift stored data.

### Production Schema Finding

Timestamp candidate columns split into:

- `DATETIME` literal fields:
  - `audit_events.occurred_at`
  - `audit_event_snapshots.created_at`
  - `note_mutation_events.occurred_at`
  - `note_revision_surplus_dispositions.occurred_at`
  - `note_revision_surplus_refund_payments.occurred_at`

- `TIMESTAMP` session-sensitive fields:
  - `note_revisions.created_at`
  - `note_revision_surplus_dispositions.created_at`
  - `note_revision_surplus_refund_payments.created_at`
  - `supplier_invoice_versions.changed_at`

### Production Data Finding

Rows found:

- `audit_events.occurred_at`: populated.
- `audit_event_snapshots.created_at`: populated.
- `supplier_invoice_versions.changed_at`: populated.
- `note_mutation_events.occurred_at`: empty.
- `note_revision_surplus_dispositions.occurred_at`: empty.
- `note_revision_surplus_refund_payments.occurred_at`: empty.
- `note_revisions.created_at`: empty.

### Classification

Recent `audit_events.occurred_at` rows and matching `supplier_invoice_versions.changed_at` rows showed the same raw timestamp for the same supplier invoice update event.

The raw timestamp plus 8 hours matched the expected owner-facing Asia/Makassar display time.

Classification for proven recent supplier invoice/audit rows:

- UTC-like.

Classification for older product seed/create rows without owner action-time memory:

- unknown.

Empty tables:

- not repair candidates.

### Decision

No legacy timestamp repair write is recommended at this time.

The current display-layer fix is the correct solution for proven UTC-like audit/history data.

### Reason

A write repair would be unsafe because:

- proven recent audit/supplier invoice timestamps are UTC-like and already display correctly after formatter conversion;
- several candidate note/refund/mutation tables are empty in production;
- older rows without a known real action time remain unknown;
- production MySQL session timezone is UTC+7 while owner operation timezone is UTC+8, so session display cannot be treated as canonical stored business time;
- date-only business fields remain excluded.

### Acceptance

- Production diagnostic was read-only.
- No date-only business field was treated as a repair candidate.
- No bulk timestamp shift is needed.
- Repair write remains forbidden unless future rows are proven local-like with reliable owner action-time evidence.


## 2026-06-29 Final Closure

Status: FINAL CLOSED.

### Closure Summary

Production read-only timestamp diagnostic was completed.

Final decision:

- No legacy timestamp repair write is recommended.
- Proven recent audit/supplier invoice rows are UTC-like.
- Empty note/refund/mutation candidate tables are not repair candidates.
- Older rows without owner action-time evidence remain unknown and must not be bulk-shifted.
- Date-only business fields remain excluded.

This document has been archived as the production timestamp repair decision record.
