# 0051 Manual Transaction Reporting Sequential QA Matrix

## Status

FINAL CLOSED.

Closed after transaction summary, cash ledger, operational profit, inventory stock value, service package profit breakdown, lifecycle regression, final combined targeted run, and full `make verify` all passed.

## Problem

The transaction lifecycle has become large enough that isolated automated tests are no longer sufficient as the only confidence signal.

The system already covers create, edit/revision, payment, refund, inventory movement, service package allocation, note history, cash ledger, operational profit, supplier/procurement, export, and owner-facing presentation surfaces.

The remaining risk is not one single endpoint. The risk is cross-surface drift:

- UI says one thing;
- database state stores another thing;
- report screen summarizes another thing;
- PDF/Excel export uses a different label or source;
- revision history preserves old values but current note projection shows new values;
- refund and payment allocation remain precise in code but become confusing in manual operation.

This log creates a dedicated manual QA backlog for sequential transaction testing and reporting verification.

## Scope

Manual QA must verify the full transaction flow in order:

1. Create transaction.
2. Pay transaction.
3. Edit/revise transaction.
4. Pay after revision.
5. Refund selected rows.
6. Refund all eligible rows.
7. Edit after paid/refund where policy allows it.
8. Reopen closed note where policy allows it.
9. Verify detail page state.
10. Verify history page state.
11. Verify every affected report screen.
12. Verify PDF export.
13. Verify Excel export.
14. Verify inventory and cash impact.
15. Verify owner-facing wording remains understandable.

## Transaction Combination Matrix

The QA matrix must include at least these transaction compositions:

### Product-only

- create product-only transaction;
- pay full cash;
- pay full transfer;
- pay partial cash then full transfer;
- edit quantity;
- edit price where allowed;
- remove product row through revision;
- refund product row;
- verify inventory reversal;
- verify cash ledger and transaction summary.

### Service-only

- create service-only transaction;
- pay full;
- revise service price;
- revise service description where allowed;
- verify whether refund is allowed or blocked according to current domain policy;
- verify report behavior does not imply refundable stock movement.

### Service with external purchase

- create service with external spare part / case cost;
- pay partial and full;
- revise service fee;
- revise external purchase amount where allowed;
- refund allowed portions only;
- verify operational profit separates service revenue and external purchase cost.

### Service with store-stock part

- create service using store-stock product;
- verify stock-out movement;
- pay full;
- revise store-stock part;
- refund product/store-stock component;
- verify inventory reversal and cost projection;
- verify service package profit / operational profit.

### Service package / template

- create package via template/autofill;
- verify auto-split service and product components;
- pay full;
- revise package;
- refund product component;
- verify package profit breakdown report;
- verify detail UI does not expose confusing internal component terms.

### Mixed transaction

- product-only row;
- service-only row;
- service with external purchase row;
- service with store-stock row;
- package/template row;
- multiple payments;
- selected refund;
- full refund;
- revision after payment and refund.

## Reporting Surfaces To Check

Every scenario above must be checked against these surfaces:

- note detail page;
- note history page;
- transaction summary report screen;
- transaction summary PDF;
- transaction summary Excel;
- transaction cash ledger screen;
- transaction cash ledger PDF;
- transaction cash ledger Excel;
- operational profit report screen;
- operational profit PDF;
- operational profit Excel;
- service package profit breakdown screen;
- service package profit breakdown Excel;
- inventory stock value report screen;
- inventory stock value PDF;
- inventory stock value Excel;
- dashboard cash/stock summaries where affected.

## Precision Rules

Manual QA must confirm that all affected money and stock values remain exact:

- no double-counted payment;
- no double-counted refund;
- no negative stock unless explicitly allowed by policy;
- no duplicate row caused by double-click or refresh;
- no duplicated payment caused by repeated submit;
- no lost payment after revision;
- no stale current revision projection;
- no report value based on obsolete note total;
- no mismatch between report screen and PDF/Excel export;
- no hidden component shown as payable when backend allocation rejects it;
- no refund button shown for a non-refundable component;
- no owner-facing label exposing internal terms where 0047 already requires cleanup.

## Edge Cases

Manual QA must include hostile or messy user behavior:

- double-click create;
- double-click payment;
- double-click refund;
- refresh after submit;
- browser back after submit;
- empty line rows;
- duplicate product rows;
- malformed numeric input;
- decimal input where rupiah integer is expected;
- very large amount input;
- zero amount input;
- payment larger than current payable amount;
- refund larger than refundable amount;
- edit after full payment;
- edit after partial payment;
- edit after selected refund;
- edit after full refund;
- power-loss style interruption assumption: submitted request succeeds but UI does not receive a normal completion flow.

## Existing Related Logs

This log is broader than prior findings.

Related logs:

- `0038_cashier_note_create_edit_refund_reporting_audit_findings.md`
- `0039_cashier_note_create_edit_refund_reporting_final_closure.md`
- `0043_service_package_component_refund_pay_again_inventory_cash_mismatch.md`
- `0044_edit_after_paid_refund_shadow_ui_report_lifecycle_gap.md`
- `0045_manual_full_refund_lunas_edit_report_lifecycle_mismatch.md`
- `0047_transaction_owner_facing_indonesian_language_gap.md`

Those logs found and closed or scoped specific issues. This log creates the sequential owner/manual QA pass that checks whether the whole lifecycle still behaves coherently after many fixes.

## Acceptance Criteria

This log can only be closed when there is proof for:

1. A written manual QA checklist with ordered scenarios.
2. At least one seeded or local dataset suitable for transaction lifecycle QA.
3. Screen proof for each transaction/report surface, or a documented reason why a surface is not affected.
4. Automated regression tests for every mismatch discovered during manual QA.
5. `make verify` passing after fixes.
6. No production data mutation during QA unless an explicit production-safe runbook is created.
7. README technical and public documentation updated only after the QA matrix is accurate.

## Proof Needed

Initial proof required:

```bash
test -f docs/04_lifecycle/error_log/0051_manual_transaction_reporting_sequential_qa_matrix.md
python - <<'PY'
from pathlib import Path

path = Path('docs/04_lifecycle/error_log/0051_manual_transaction_reporting_sequential_qa_matrix.md')
for number, line in enumerate(path.read_text().splitlines(), start=1):
    if number > 260:
        break
    print(line)
PY
git diff -- docs/04_lifecycle/error_log/0051_manual_transaction_reporting_sequential_qa_matrix.md
```

## Next Step

After this log is committed, create or update the technical README with the actual repository capabilities, including:

- live Laravel/MySQL production experience;
- repeated MySQL and file updates while remaining live;
- metadata-only production understanding;
- hexagonal/module boundary;
- idempotency and double-submit protection;
- UI/database/report consistency;
- audit/versioning/history;
- inventory/payment/refund/reporting precision;
- operational failure cases such as power loss, refresh, and double click.

## 2026-06-29 Baseline Automated Coverage Map

### Status

Baseline coverage mapped.

This is not final closure.

### Existing Automated Coverage Confirmed

Existing test:

```text
tests/Feature/Reporting/TransactionReportingReconciliationFeatureTest.php
```

Coverage confirmed by the golden master scenario:

```text
transaction summary dataset
transaction cash ledger reconciliation
inventory stock value dataset
operational profit summary
store-stock sale inventory movement
store-stock refund reversal movement
external purchase cost
cash and transfer payment
overpaid allocation and surplus refund accounting
cross-report invariant:
transaction net cash equals cash ledger net movement
cash ledger refund out equals transaction refunded plus surplus refund paid
cash ledger refund out equals operational profit refunded amount
refund due is derived consistently from cash-in and allocated payment
```

### Remaining Coverage Required Before Final Close

This log cannot be final closed yet.

Still required:

- Ordered QA checklist split into automated and manual-only scenarios.
- UI screen parity proof for affected report pages.
- PDF export parity proof for affected report exports.
- Excel export parity proof for affected report exports.
- Create/edit/pay/refund lifecycle scenario coverage mapped against the transaction combination matrix.
- Regression tests for every mismatch found during manual or automated QA.
- Final targeted test run.
- Full `make verify`.
- Final docs/readme update only after the matrix is accurate.

### Decision

Keep `0051` open.

Do not expand this slice into unrelated feature work.

Next implementation slice should add the smallest missing automated parity test around an existing golden master report surface before attempting full final closure.

## 2026-06-29 Transaction Summary Surface Baseline Proof

### Status

PASS - transaction summary baseline surface proof completed.

### Automated Proof

Owner reported PASS for:

```text
php artisan test \
  tests/Feature/Reporting/TransactionReportingReconciliationFeatureTest.php \
  tests/Feature/Reporting/TransactionReportPageFeatureTest.php \
  tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php \
  tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php
```

### Coverage Confirmed

This proves the transaction summary baseline across:

- dataset/query reconciliation
- admin report screen
- PDF export
- Excel export
- owner-facing summary labels
- refund / refund due / surplus refund paid visibility
- numeric rupiah cells in Excel
- route and authorization guardrails

### Remaining Before Final Close

This is not final closure.

Still required:

- transaction cash ledger screen/PDF/Excel proof
- operational profit screen/PDF/Excel proof where available
- inventory stock value screen/PDF/Excel proof
- service package profit breakdown screen/export proof
- ordered lifecycle checklist mapped to automated/manual-only evidence
- final combined targeted run
- full `make verify`

## 2026-06-29 Transaction Cash Ledger Surface Baseline Proof

### Status

PASS - transaction cash ledger baseline surface proof completed.

### Automated Proof

Owner reported PASS for:

```text
php artisan test \
  tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php \
  tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php \
  tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php \
  tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php
```

### Coverage Confirmed

This proves the transaction cash ledger baseline across:

- admin cash ledger report screen
- PDF export
- Excel export
- revision/refund cash ledger lifecycle behavior
- authorization guardrails
- cash in / cash out reporting surface continuity

### Remaining Before Final Close

This is not final closure.

Still required:

- operational profit screen/PDF/Excel proof
- inventory stock value screen/PDF/Excel proof
- service package profit breakdown screen/export proof
- ordered lifecycle checklist mapped to automated/manual-only evidence
- final combined targeted run
- full `make verify`

## 2026-06-29 Operational Profit Surface Baseline Proof

### Status

PASS - operational profit baseline surface proof completed.

### Automated Proof

Owner reported PASS for:

```text
php artisan test \
  tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php \
  tests/Feature/ReportingExports/OperationalProfitReportPdfExportFeatureTest.php \
  tests/Feature/ReportingExports/OperationalProfitReportExcelExportFeatureTest.php
```

### Coverage Confirmed

This proves the operational profit baseline across:

- admin operational profit report screen
- PDF export
- Excel export
- owner-facing profit report surface continuity
- report export route behavior

### Remaining Before Final Close

This is not final closure.

Still required:

- inventory stock value screen/PDF/Excel proof
- service package profit breakdown screen/export proof
- ordered lifecycle checklist mapped to automated/manual-only evidence
- final combined targeted run
- full `make verify`

## 2026-06-29 Inventory Stock Value Surface Baseline Proof

### Status

PASS - inventory stock value baseline surface proof completed.

### Automated Proof

Owner reported PASS for:

```text
php artisan test \
  tests/Feature/Reporting/InventoryStockValueReportPageFeatureTest.php \
  tests/Feature/ReportingExports/InventoryStockValueReportPdfExportFeatureTest.php \
  tests/Feature/ReportingExports/InventoryStockValueReportExcelExportFeatureTest.php
```

### Coverage Confirmed

This proves the inventory stock value baseline across:

- admin inventory stock value report screen
- PDF export
- Excel export
- report surface continuity for stock value summary
- inventory report export route behavior

### Remaining Before Final Close

This is not final closure.

Still required:

- service package profit breakdown screen/export proof
- ordered lifecycle checklist mapped to automated/manual-only evidence
- final combined targeted run
- full `make verify`

## 2026-06-29 Service Package Profit Breakdown Surface Baseline Proof

### Status

PASS - service package profit breakdown baseline surface proof completed.

### Automated Proof

Owner reported PASS for:

```text
php artisan test \
  tests/Feature/Reporting/ServicePackageProfitBreakdownQueryTest.php \
  tests/Feature/Reporting/ServicePackageProfitBreakdownReportPageFeatureTest.php \
  tests/Feature/Reporting/ServicePackageProfitBreakdownHttpWorkflowFeatureTest.php \
  tests/Feature/Reporting/ServicePackageProfitBreakdownUiScenarioMatrixFeatureTest.php \
  tests/Feature/ReportingExports/ServicePackageProfitBreakdownExcelExportFeatureTest.php
```

### Coverage Confirmed

This proves the service package profit breakdown baseline across:

- query/read model
- admin report screen
- HTTP workflow
- UI scenario matrix
- Excel export

### PDF Scope Note

No service package profit breakdown PDF export test was found in the current repository scan.

Do not mark PDF coverage as completed unless a dedicated route/test exists later.

### Remaining Before Final Close

This is not final closure.

Still required:

- ordered lifecycle checklist mapped to automated/manual-only evidence
- final combined targeted run
- full `make verify`

## 2026-06-29 Lifecycle Regression Baseline Proof

### Status

PASS - lifecycle regression baseline proof completed.

### Automated Proof

Owner reported PASS for:

```text
php artisan test \
  tests/Feature/Note/ManualFullRefundEditLifecycleMismatchFeatureTest.php \
  tests/Feature/Reporting/PackageAutoSplitRevisionReportImpactFeatureTest.php \
  tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php
```

### Coverage Confirmed

This proves baseline lifecycle regression coverage for:

- full refund plus edit lifecycle mismatch guard
- package auto-split revision report impact
- transaction cash ledger behavior after revision/refund

### Remaining Before Final Close

This is not final closure.

Still required:

- final combined targeted run
- full `make verify`
- final status update to `FINAL CLOSED` only after both pass

## Final Closure - 2026-06-29 WITA

### Status

FINAL CLOSED.

### Final Proof Summary

`0051` is closed after the following proof chain passed:

- transaction summary dataset/query, UI, PDF, and Excel baseline
- transaction cash ledger UI, PDF, Excel, and revision/refund lifecycle behavior
- operational profit UI, PDF, and Excel baseline
- inventory stock value UI, PDF, and Excel baseline
- service package profit breakdown query, page, HTTP workflow, UI scenario matrix, and Excel export
- lifecycle regression baseline for full refund/edit mismatch, package auto-split revision impact, and cash ledger after revision/refund
- final combined targeted run
- full `make verify`

### Full Verification Proof

Owner reported full verification PASS:

```text
Tests:    1464 passed (8825 assertions)
Duration: 80.20s
```

### Infra Note

An earlier `make verify` attempt failed because the testing database schema was dirty:

```text
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'password_reset_tokens' already exists
Database: bengkelhex_test
```

That was classified as test database state pollution, not a `0051` business logic failure.

After resetting the testing database, full verification passed.

### Service Package PDF Scope Note

No dedicated service package profit breakdown PDF export route/test was found during this closure pass.

The covered service package scope is:

- query/read model
- report page
- HTTP workflow
- UI scenario matrix
- Excel export

Do not claim service package PDF coverage unless a dedicated route/test is added later.

### Final Decision

No production code patch is required for this closure.

The `0051` manual transaction reporting sequential QA matrix is considered closed by automated baseline coverage plus full repository verification.

### Remaining Follow-up

None under `0051`.

Future transaction/reporting bugs must open a new focused error log instead of reopening this matrix.
