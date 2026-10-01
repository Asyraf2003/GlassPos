# Supplier invoice current quantity and duplicate number

Issue: #72. Baseline: latest main `0a7bfd0770c1829350dd341fd830ce6ad03ed7e8`.

## Audit and target

Canonical invoice number is UTF-8 lowercase after trim, global across suppliers,
excluding voided invoices. The request post-validator already uses
`SupplierInvoiceDuplicateNumberChecker`; the database only has a nonunique index.
Create has no asynchronous number validation.

Detail and persisted list quantity sum initial `supplier_receipt_lines` only.
Accepted edits preserve version snapshots and old lines, and record quantity
corrections as `supplier_invoice_revision_delta_line` inventory movements.
Current line readers already filter `is_current`. Projection sync already runs
after successful edits, in the same transaction as invoice and inventory writes.

## Selected change

1. Expose an authenticated admin read endpoint using the existing canonical checker.
2. Bind create number validation after draft hydration. Debounce inputs, invalidate
   requests immediately on every change, abort obsolete requests and check request
   identity plus captured input before applying results. Show red inline duplicate
   feedback and disable submit while pending or duplicate, including shortcuts.
3. Enforce unique active normalized numbers with a generated nullable key and unique
   index, preserving voided-number reuse. Classify constraint errors in the existing
   backend error classifier. Existing request validation remains authoritative UX.
4. Share the receipt quantity query between detail and list: immutable receipt
   quantity plus all accepted revision quantity deltas. This reconstructs the latest
   accepted received state, including successive edits, decreases and replacements,
   without counting cost-only revaluations or unrelated stock movements. Preserve
   existing partial receipt and reversal semantics.
5. Repair only persisted list `total_received_qty` on migration; do not rewrite
   receipts, versions, audit snapshots, payment columns or costing projections.

## Boundaries and risks

No bank/payment feature changes, reversal policy, costing/reporting, customer
transactions, R2/storage, unrelated UI or versioning architecture changes.
Historical receipt quantities and revision snapshots remain historical truth.
Do not infer full receipt from invoice quantity: partial receipts are supported.
Existing active duplicates must stop the constraint migration for explicit data
resolution; never delete or rewrite duplicate invoices automatically.
Async transport failure falls back to mandatory backend validation at save.

## Workflow and proof

Issue → branch from latest main → source audit → regression characterization →
minimal patch → focused PHP and executable frontend tests → PHPStan and contract
audit → final `make verify` GREEN → commit → push → PR → merge.

Regressions must prove 42→80, 2→20, successive revisions, partial receipts, current
detail/rendered summary/table, immutable old version/receipt/audit values, existing
projection repair, duplicate canonical check, bypass rejection/storage uniqueness,
voided-number reuse, disabled/re-enabled submit and stale async result protection.
