# Handoff: Detail Refund C3

## Metadata
- Date: 2026-09-26
- Active scope: Blueprint0019 C3, Issue #7
- Branch: `feat/7-detail-refund-cancellation`, based on main `da5bc06b`
- Status: implementation and focused proof complete; prepare draft PR, no merge while repository contract gate is red
- Campaign: C0/C1/C2 merged; C3 not yet accepted; C4/C5/C6 remain

## FACT / contract
- ADR-0042 accepts paid service refund and explicit physical return/no-return. Raw owner statements in handoff0029 remain unchanged.
- Source constant is `PaymentComponentType::SERVICE_FEE`, shared by standalone and package services.
- Detail requires `stock_returns[work_item_id]` for selected stock-bearing rows. Missing/extra/non-boolean choices reject. Service-only has no stock choice.
- Whole package selection refunds both eligible service and product allocations. Existing component selectors still select only those components; callers testing stock-only refund now explicitly use them.
- Individually paid/closed rows can refund on a mixed/open note; unpaid/open rows remain rejected.
- Existing payment allocations, refund buckets, source-cost reversal, projections and durable audit remain authoritative.

## Implementation
- The existing selected-refund transaction now delegates its business effects to `RecordSelectedRowsRefundPlanExecutor`, keeping transaction/receipt handling separate and within the repository file-size boundary.
- The executor takes the canonical root lock and rebuilds the plan before writing money, inventory, shadow rows, projection and audit.
- Typed idempotency claim conflicts roll back and replay the winning receipt. HTTP normalizes selection order and boolean encodings; changed stock choices conflict.
- Return uses existing stock reversal; no-return adds no movement. Revision replacement skips refunded stock history so it cannot return a no-return issue later.
- Fully refunded service/no-return stock is not collectible again. Detail billing reads actual per-component allocations instead of redistributing row refund by component order.
- The existing modal has an empty initial stock decision, required reason and per-row choice. The server derives money. No Auto preset was added.
- Audit outbox metadata includes selected IDs, stock choices, actor, reason, refund IDs and amount; the existing timestamp remains canonical.

## RED / focused proof
All commands ran from the repository root. Test database was isolated at `127.0.0.1:3321`, database `glasspos_cancellation_c1_test`.

Initial `DetailRefundChoicesFeatureTest` produced three expected RED failures: service rejected, no-return still added stock, mixed root rejected. An added service-component Detail test also failed before the billing allocation fix.

Final test command: `env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3321 DB_DATABASE=glasspos_cancellation_c1_test DB_USERNAME=root DB_PASSWORD= php -d memory_limit=-1 vendor/bin/pest <related paths> --compact`.

Related paths were all test files returned by `rg -l 'notes.refunds.store' tests`, plus:
- `tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`
- `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
- `tests/Feature/Payment/ServicePackageComponentRefundPayAgainMatrixTest.php`
- `tests/Unit/Application/Payment/Services/AllocateRefundAcrossComponentsTest.php`
- `tests/Unit/Application/Note/Services/SelectedRowsRefundBucketsBuilderTest.php`
- `tests/Feature/Note/RefundReportingOwnerDecisionV2CharacterizationTest.php`
- `tests/Feature/Note/DetailRefundConcurrencyFeatureTest.php`
- `tests/Feature/Note/WholeNoteCancellationFeatureTest.php`
- `tests/Feature/Note/WholeNoteRestoreFeatureTest.php`
- `tests/Feature/Note/WholeNoteRestoreAtomicityFeatureTest.php`
- `tests/Feature/Note/WholeNoteRestoreConcurrencyFeatureTest.php`

Result after final production changes: **160 passed, 2906 assertions**. This includes actual outbox insert failure rollback/retry, two independent MySQL-session refund races (same/different keys), existing refund/revision overlap, reporting chains, access boundaries and cancellation/restore regression. Races use held-transaction barriers and observer lock-wait evidence; these are not sequential concurrency claims.

One verification attempt failed because the dedicated test server was no longer running (`Connection refused`). The same isolated datadir was restarted; port and database identity were verified before the successful final run. Application database at port3306 was not used.

Other proof:
- `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`: no errors.
- `vendor/bin/pint --test <all changed PHP paths>`: pass. Two existing test traits were moved next to other trait declarations to avoid a formatter error; test behavior is unchanged by that placement.
- `node --check public/assets/static/js/pages/cashier-note-refund.js`: pass.
- Chromium headless with `--allow-file-access-from-files --dump-dom file:///home/asus/projects/GlassPos/tests/Browser/detail-refund-choices.html`: **PASS: 9 DOM assertions**. Actual production JavaScript checks empty initial choice, no-return/return payload and preview, service without physical choice, selected IDs, required reason and server-supplied amount display. This is a static DOM smoke, not end-to-end cashier acceptance.
- `php scripts/audit-blade-no-php.php`: pass.
- `git diff --check`: pass.
- `make audit-contract`: blocked by six inherited line-count failures, independently confirmed on origin/main and tracked by Issue #8. C3 introduces no additional over-limit files.
- No full repository test scan was run in C3; that remains the campaign-closeout gate.

## Diff review
Manual diff-review pass covered public request change, under-lock eligibility, stock/receipt/audit atomicity, current component billing, external restriction, test fixture migrations and actual JavaScript behavior. Findings fixed during this pass: no-return stock incorrectly returned by later revision, refunded service still collectible, and Detail refund allocated to the wrong package component. Each is covered by a lifecycle test. No independent GitHub approval is claimed.

## GAP / known limits
- Issue #8 blocks the full repository contract gate. Keep C3 PR draft until that independent baseline slice is resolved and the combined gate is verified.
- External-purchase rows remain rejected in both Detail and the underlying selected refund operation; C4 owns the boundary.
- Legacy service notes without reconstructable component allocation remain safely rejected. No historical allocation backfill was invented.
- This slice retains existing component/full-row eligibility and original-cost reversal mechanics; it does not redesign the refund engine or create component-specific amount editing.
- Physical cashier acceptance, complete cancellation/restore Detail integration and whole campaign verification remain C5/C6.
- `.codex/` is operator-local and excluded only through `.git/info/exclude`. Do not commit it or change repository `.gitignore` for it.

## NEXT
Push verified C3 commits to the active branch and open a draft PR closing Issue #7. Resolve the separate Issue #8 contract gate on its own branch/PR, then revalidate C3 against accepted main before review/merge. Do not publish candidate work directly to main.
