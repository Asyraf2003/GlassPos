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

