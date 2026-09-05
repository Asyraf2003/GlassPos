# 0022 - Cashier Note-level Payment And Finance Work Queue Handoff

## Metadata

- Date: 2026-09-05
- Slice / topic: note-level cash contract, deterministic component allocation, cashier finance queue, admin chronology/sort, and revision/refund-aware payment timeline
- Workflow step: final local financial-critical closure
- Status: closed locally; ready for separate pre-production release QA
- Progress: 100% for the authorized local slice

## Target Work Page

Cashier create/detail payment, cashier `Riwayat Nota`, and admin note history. The slice preserves the existing Note/payment/refund/revision/inventory write paths and changes only the owner-authorized payment assembly, allocation priority, access/read-model, presentation, and regression contracts.

## References Used

- Blueprint: `docs/03_blueprints/ui/0014_cashier_note_workspace_simple_detail_pos_hardening.md`
- History blueprint: `docs/03_blueprints/ui/0015_cashier_note_history_two_focus_work_queue.md`
- Error log: `docs/04_lifecycle/error_log/0062_transaction_edit_refund_payment_stock_reporting_hardening_campaign.md`
- Static delivery error log: `docs/04_lifecycle/error_log/0063_static_asset_url_implicitly_coupled_to_r2_public_url.md`
- Workflow: `docs/01_standards/core/0012_step_by_step_execution.md`
- DoD: `docs/01_standards/core/0013_proof_and_progress.md` and `docs/01_standards/0006_final_review_checklist.md`
- ADR: existing note lifecycle, payment/allocation, revision settlement, refund, inventory, projection, and Laravel hexagonal contracts under `docs/02_architecture/`
- Previous handoff: `docs/04_lifecycle/handoff/0021_cashier_final_preproduction_ux_hardening_handoff.md`
- Repo snapshot / command output: local `main` and `origin/main` both started at `96f5f51450265942386292fafce36b8488f372ba`

## Locked Facts

- A cash payment is credited by `min(received, current outstanding)`; change exists only above current outstanding.
- A partial suggestion is presentation only. Cash Rp149.000 against a Rp200.000 suggestion persists payment/received Rp149.000, change Rp0, and remaining Rp351.000 on a Rp500.000 note.
- The cashier HTTP payment endpoint allocates at note level. Internal priority is external purchase, store product, then service; selected UI row IDs do not override it.
- `customer_payments`, cash detail, component allocations, refund records, idempotency, audit, projection, and transaction boundaries remain the authoritative write model.
- Payment timeline uses event-time revision total and refunds occurring before a later payment. Later revision/refund events do not rewrite an earlier payment's historical meaning.
- `Belum Lunas` is projected outstanding greater than zero at any age. `Selesai Hari Ini` uses authoritative `closed_at` today; work status is context, not finance membership.
- Old outstanding notes may be viewed/collected by cashier, but the old edit/refund date restrictions remain in force.
- Admin history defaults to `notes.created_at DESC`, then ID fallback, and supports created/total/net-paid/outstanding sorting before pagination.
- No production/Cloudflare operation or shared-CDN asset upload occurred.

## Scope Used

### SCOPE-IN

- Simple and Detail cash semantics, actual-vs-suggested amount transparency, remaining-after-payment UI, and same-note payment chain.
- Internal component allocation priority and note-level cashier payment authority.
- Revision/refund-aware payment timeline read model.
- Cashier all-age debt queue, settled-today transition/access, search/pagination/chronology, and lifecycle-safe actions.
- Admin true chronology and reusable server-backed money sorting.
- Adversarial HTTP/application/database tests, browser acceptance, repository audits, canonical documentation, and release asset manifest.

### SCOPE-OUT

- New payment workflow, customer credit/wallet, manual approval for ordinary revision surplus, or alternate Simple/mobile backend.
- Production deploy, production migration/data mutation, Cloudflare/R2 upload, or release configuration changes.
- Claiming physical phone or installed standalone PWA acceptance from Chromium emulation.

## GAP

- Physical phone and installed-PWA acceptance remain manual pre-production QA.
- Production release must advance `ASSET_VERSION` and run only the reviewed targeted asset command after deployment authorization.

## Locked Decisions

- Suggestion is not a limit and selected component UI is not payment authority.
- Cash tender above outstanding is ordinary change; transfer cannot exceed outstanding.
- Historical payment events are immutable money truth; revision/refund lifecycle context belongs in the persistence read adapter, not Blade/JavaScript.
- Cashier history is a finance collection queue; operational work status remains visible context.
- Admin sorting is whitelisted in the request and executed query-side before pagination.
- Existing automatic revision-surplus refund remains authoritative; no customer credit or default manual approval is introduced.

## Files Created / Changed

### New files

- `app/Application/Note/Services/NotePaymentAmountResolver.php`
- `app/Adapters/Out/Payment/Queries/DatabaseNotePaymentTimelineLifecycleContextQuery.php`
- `tests/Feature/Note/CashierNoteLevelCashPaymentContractFeatureTest.php`
- `tests/Feature/Note/CashierOldDebtSettlementWorkQueueFeatureTest.php`
- `tests/Feature/Note/PaymentTimelineRevisionTruthFeatureTest.php`
- `docs/04_lifecycle/handoff/0022_cashier_note_level_payment_and_finance_work_queue_handoff.md`

### Changed files

- Cashier/admin history request, controller, criteria, projection filter/query, mapper, Blade, and JavaScript files.
- Cashier route access middleware/policies/data services.
- Detail/workspace payment resolver, validator, controller, timeline port/adapter/builder, payment priority, modal Blade, and modular payment JavaScript.
- Note/Payment feature tests that encoded suggestion, selected-row, partial-boundary, date-window, operational-bucket, chronology, and revision-timeline assumptions.
- UI blueprints `0014`/`0015`, carry-forward ADR `0025`, finance blueprints `0003`/`0005`, error log `0062`, and handoff index.
- Removed obsolete `SelectedNoteRowsPaymentAmountResolver.php`; cashier payment now resolves against authoritative note outstanding.

## Verification Proof

### Required RED evidence

- command: `php artisan test tests/Feature/Note/CashierNoteLevelCashPaymentContractFeatureTest.php`
  - result: RED, 4 failures
  - meaning: old code rejected cash below suggestion, persisted suggestion instead of actual cash, created bogus change, and failed the same-note priority/reconciliation chain
- command: focused `CashierNoteHistoryWorkQueueClassificationFeatureTest` and `CashierOldDebtSettlementWorkQueueFeatureTest`
  - result: RED; old debt was absent and old debt settlement was blocked/absent from completed-today
  - meaning: the date window and access policy contradicted the all-age finance queue
- command: focused `AdminNoteHistoryTableDataFeatureTest`
  - result: RED; lexical IDs beat created chronology, page-two money order was wrong, and invalid sort did not fail closed
  - meaning: ordering/sorting were not authoritative at the query boundary
- command: `php artisan test tests/Feature/Note/PaymentTimelineRevisionTruthFeatureTest.php`
  - result: RED; historical remaining became 501000/0 instead of 351000/300000 after upward/downward revisions
  - meaning: current revision/allocation was rewriting event history
- command: `php artisan test tests/Feature/Note/PaymentTimelineRevisionTruthFeatureTest.php --filter=payment_after_refund`
  - result: RED; later payment remaining was 0 instead of 50000
  - meaning: the timeline used gross cumulative payments without official refunds that occurred before the later event

The first full gate then exposed three obsolete Unit contracts: partial cash was expected to cover a suggestion, payment equal to outstanding was expected to fail, and store product was expected before external purchase. These were replaced with actual cash, inclusive `<= outstanding`, and external -> store product -> service assertions; production was not reverted to satisfy them.

### FIX responsibilities

- HTTP/Application amount resolver now derives cash credit from received cash and authoritative note outstanding.
- Existing allocation service receives the whole active note from the cashier endpoint and uses corrected component priority.
- Existing history query/access boundaries now use outstanding/`closed_at`, all-age collection access, and created/settled chronology.
- Existing payment timeline adapter supplies event-time revision/refund lifecycle context; the Application builder derives semantic/remaining values without current-state rewrite.
- Existing admin history request/query/JS pattern owns the sort whitelist, SQL order, URL state, and indicators.

### GREEN evidence

- command: same four cash contract tests
  - result: PASS, 4 tests / 34 assertions
  - meaning: actual cash, real change, three independent events, allocation priority, and component/note reconciliation are locked
- command: `php artisan test tests/Feature/Note`
  - result: PASS, 383 tests / 3,166 assertions
  - meaning: Note create/edit/revision/refund/payment/inventory/projection regressions remain green
- command: `php artisan test tests/Feature/Payment`
  - result: PASS, 83 tests / 449 assertions
  - meaning: payment/allocation/refund/concurrency regressions remain green
- command: same refund-after-payment timeline test
  - result: PASS, 1 test / 4 assertions
  - meaning: earlier payment stays Pelunasan and later replacement payment shows the correct Rp50.000 remaining
- command: focused replacement Unit contracts
  - result: PASS, 6 tests / 15 assertions
  - meaning: actual cash, inclusive outstanding boundary, and external-first allocation are locked at Unit level
- command: `node /tmp/glasspos-ui-browser-proof.mjs`
  - result: PASS
  - meaning: real browser drove Simple Rp149.000, Detail cash Rp51.000, transfer Rp50.000, settlement Rp250.000 with Rp300.000 received/Rp50.000 change; all events, remaining balances, aggregate, debt-age transition, six viewports, admin chronology/sort, and zero relevant runtime/asset failures were verified
- command: JS `node --check`, `vendor/bin/pint --dirty`, `make audit-contract`, `make audit-hex`, and `git diff --check`
  - result: PASS
- command: `make verify`
  - result: PASS; PHPStan 2,027 files / no errors; 1,600 tests / 10,228 assertions
  - meaning: canonical repository gate is green after the invalid old Unit contracts were replaced

### Changed static-asset manifest

- `public/assets/static/js/pages/admin-note-index.js`
- `public/assets/static/js/pages/cashier-note-index.js`
- `public/assets/static/js/pages/cashier-note-payment.js`
- `public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`

After separate production authorization and an `ASSET_VERSION` release bump, the exact incremental command is:

```bash
php artisan r2:upload-public-assets \
  --path=static/js/pages/admin-note-index.js \
  --path=static/js/pages/cashier-note-index.js \
  --path=static/js/pages/cashier-note-payment.js \
  --path=static/js/pages/cashier-note-workspace/payment-flow.js
```

This command was not run in this local slice.

## Risks / Follow-up Notes

- Legacy refund rows without operational timestamps use their business date as a deterministic read fallback; current writers persist operational timestamps.
- Existing compiled navigation emitted five unrelated `Mr.isElementInViewport` warnings during browser navigation; no cashier/admin Note module exception or asset failure occurred.
- Static UI changes were intentionally kept local.

## Next Step

Run the separate pre-production release workflow: physical phone/PWA acceptance, advance `ASSET_VERSION`, and upload only the reviewed four-file manifest after explicit production authorization. No production/CDN action belongs to this handoff slice.
