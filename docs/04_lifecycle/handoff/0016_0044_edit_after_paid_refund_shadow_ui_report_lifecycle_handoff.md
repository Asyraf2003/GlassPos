# Handoff 0016 - 0044 Edit After Paid Refund Shadow UI Report Lifecycle

## Status

Active workflow handoff.

This handoff must be updated at the end of every 0044 session.

This file is not an implementation patch.

## Linked Documents

- Error log: `docs/04_lifecycle/error_log/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_gap.md`
- Workflow: `docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md`
- Architecture DoD: `docs/01_standards/architecture/0044_audit_and_dod.md`
- Finance residual DoD: `docs/03_blueprints/finance/0004_finance_residual_dod.md`
- Readiness analysis: `docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md`
- Characterization plan: `docs/03_blueprints/db/0017_edit_refund_characterization_plan.md`

## Connector Rule

Do not execute direct GitHub connector write actions for this workflow.

Assistant must provide local CLI commands only unless owner explicitly overrides this rule in the same session.

Reason:

Owner controls local patching, verification, git diff, commit, and push.

## Current Objective

Build and execute the 0044 workflow slowly, one verified slice at a time:

1. Structure/source-map.
2. Edit after unpaid/paid.
3. Refund shadow/historical truth.
4. Combined edit/refund/payment/stock matrix.
5. UI backend-derived action flags.
6. Browser refresh resilience.
7. Report/PDF/Excel parity.
8. Final blast-radius verification.

## Current Slice

Slice 0 - Structure, Source Map, and Guardrail.

## Workflow Checklist

### Slice 0 - Structure / Source Map

- [ ] Active edit routes mapped.
- [ ] Active refund routes mapped.
- [ ] Active payment routes mapped.
- [ ] Note detail UI data source mapped.
- [ ] UI action flag builders mapped.
- [ ] Note revision services mapped.
- [ ] Payment allocation services mapped.
- [ ] Refund allocation services mapped.
- [ ] Inventory movement services mapped.
- [ ] Note history projection mapped.
- [ ] Surplus/refund_due/refund_paid records mapped.
- [ ] Transaction report dataset mapped.
- [ ] Excel export path mapped.
- [ ] PDF export path mapped.
- [ ] Dead/unproven paths listed.
- [ ] First characterization test selected.
- [x] No production code patch made.

### Slice 1 - Edit After Unpaid/Paid

- [ ] Edit unpaid note characterization exists.
- [ ] Edit paid note carry-forward characterization exists.
- [ ] Edit paid upward revision characterization exists.
- [ ] Edit paid downward surplus characterization exists.
- [ ] Edit paid delete-all active lines characterization exists.
- [ ] Runtime patch, if any, has RED proof first.
- [ ] Targeted tests PASS.
- [ ] Existing relevant tests PASS.

### Slice 2 - Refund Shadow / Historical Truth

- [ ] Edit after ordinary refund characterization exists.
- [ ] Refund ledger/shadow persistence characterization exists.
- [ ] Refund after revision current row id test exists.
- [ ] Stale historical row id rejection test exists.
- [ ] Money effect and stock effect separation test exists.
- [ ] Runtime patch, if any, has RED proof first.
- [ ] Targeted tests PASS.
- [ ] Existing relevant tests PASS.

### Slice 3 - Combined Lifecycle Matrix

- [ ] Matrix dimensions selected.
- [ ] Paid/refund/edit upward case covered.
- [ ] Paid/refund/edit downward case covered.
- [ ] Paid/refund/delete-all active lines case covered.
- [ ] Service-only case covered.
- [ ] Product-only case covered.
- [ ] Store-stock case covered.
- [ ] Service package stock component case covered.
- [ ] Cash and transfer payment variants covered.
- [ ] Payment/refund/allocation/inventory/projection assertions exist.
- [ ] Targeted matrix PASS.

### Slice 4 - UI Backend-Derived Action Flags

- [ ] Note detail payment actions use backend flags.
- [ ] Invalid Lunasi/Bayar action hidden or explained.
- [ ] Edit action visibility matches lifecycle policy.
- [ ] Refund action visibility matches backend refundable rows.
- [ ] UI shows clear money/refund/stock/status wording.
- [ ] JS is not financial truth.
- [ ] Static render tests PASS.
- [ ] Feature UI tests PASS.

### Slice 5 - Browser Refresh Resilience

- [ ] Normal refresh behavior checked.
- [ ] Hard refresh behavior checked.
- [ ] Stale hidden payload rejected by backend.
- [ ] Modal values come from backend state.
- [ ] Manual QA proof or automated equivalent recorded.

### Slice 6 - Report / PDF / Excel Parity

- [ ] Official dataset source mapped.
- [ ] Screen report parity asserted.
- [ ] Excel export parity asserted.
- [ ] PDF view data parity asserted.
- [ ] PDF Blade parity asserted.
- [ ] Cash ledger affected fields asserted if touched.
- [ ] Operational profit affected fields asserted if touched.
- [ ] Service package breakdown affected fields asserted if touched.
- [ ] No report formula patch without write-side proof.

### Slice 7 - Final Blast Radius

- [ ] Targeted 0044 tests PASS.
- [ ] Relevant note revision tests PASS.
- [ ] Relevant payment tests PASS.
- [ ] Relevant refund tests PASS.
- [ ] Relevant inventory tests PASS if touched.
- [ ] Relevant reporting tests PASS if touched.
- [ ] Relevant export tests PASS if touched.
- [ ] make verify PASS.
- [ ] git diff --stat reviewed.
- [ ] Error log updated after proof.
- [ ] Residual gaps listed.
- [ ] Owner acceptance recorded.

## Session Update Template

Copy and fill this section at the end of every 0044 session.

```markdown
## Session Update - YYYY-MM-DD HH:MM

### Slice

- Active slice:
- Status:

### Files Read

- `path`

### Files Changed

- `path`

### FACT

- 

### GAP

- 

### DECISION

- 

### Tests / Commands Run

```bash
command
```

Result:

```text
output summary
```

Meaning:

- 

### Checklist Changes

- [ ] item

### Residual Gaps

- 

### Next Allowed Step

- 
```

## Session Log

### Session Update - 2026-06-25 Initial Workflow Setup

#### Slice

- Active slice: Slice 0 - Structure, Source Map, and Guardrail.
- Status: workflow/handoff setup only.

#### Files Read

- Existing error log 0044 if present.
- Existing architecture and finance DoD references from prior analysis.

#### Files Changed

- `docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md`
- `docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md`
- `docs/04_lifecycle/error_log/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_gap.md` only if it did not already exist.

#### FACT

- 0044 needs workflow and DoD before production patch.
- Every future 0044 session must update this handoff.
- Direct connector write execution is forbidden by default for this workflow.

#### GAP

- Active source map has not been completed yet.
- No characterization test has been added yet.
- No production patch has been made.

#### DECISION

- Start next session from Slice 0 source-map.
- Do not patch UI, report, payment, refund, or inventory yet.

#### Tests / Commands Run

```bash
grep -n \
  -e "## Session Rule" \
  -e "## Connector Rule" \
  -e "## Core Domain Policy" \
  -e "## Workflow Slices" \
  -e "### Slice 0" \
  -e "### Slice 7" \
  -e "## Global Stop Conditions" \
  -e "## Definition of Done" \
  docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md

grep -n \
  -e "## Connector Rule" \
  -e "## Workflow Checklist" \
  -e "### Slice 0" \
  -e "### Slice 7" \
  -e "## Session Update Template" \
  -e "## Session Log" \
  docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md
```

Result:

```text
Pending owner local run.
```

Meaning:

- Handoff setup must be verified locally before commit.

#### Checklist Changes

- [x] Workflow file drafted.
- [x] Handoff file drafted.
- [x] Connector write prohibition recorded.
- [ ] Slice 0 source map completed.

#### Residual Gaps

- Source map still pending.
- Characterization test selection still pending.
- Runtime behavior still unpatched.

#### Next Allowed Step

- Slice 0 source-map only.
