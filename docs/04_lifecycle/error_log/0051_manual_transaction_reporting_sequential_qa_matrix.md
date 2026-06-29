# 0048 Manual Transaction Reporting Sequential QA Matrix

## Status

Proposed.

Reported by owner on 2026-06-29 WITA after completing owner-facing language cleanup and make-help documentation cleanup.

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
test -f docs/04_lifecycle/error_log/0048_manual_transaction_reporting_sequential_qa_matrix.md
python - <<'PY'
from pathlib import Path

path = Path('docs/04_lifecycle/error_log/0048_manual_transaction_reporting_sequential_qa_matrix.md')
for number, line in enumerate(path.read_text().splitlines(), start=1):
    if number > 260:
        break
    print(line)
PY
git diff -- docs/04_lifecycle/error_log/0048_manual_transaction_reporting_sequential_qa_matrix.md
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
