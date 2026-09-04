# 0021 - Cashier Final Pre-production UX Hardening Handoff

## Metadata

- Date: 2026-09-05
- Slice / topic: selected stamps, create-only Simple mode, two-focus chronological cashier history, payment-event transparency, responsive hardening, adversarial regression, and release asset manifest
- Workflow step: final local pre-production closure
- Status: closed locally; production deployment and physical-device acceptance remain out of scope
- Progress: 100% of the authorized local slice

## Target Work Page

Cashier create/edit note workspace and `Riwayat Nota` navigation/work queue, using the existing Note HTTP, application, domain, projection, payment, inventory, idempotency, revision, and refund boundaries.

## References Used

- Blueprint: `docs/03_blueprints/ui/0014_cashier_note_workspace_simple_detail_pos_hardening.md`
- History blueprint: `docs/03_blueprints/ui/0015_cashier_note_history_two_focus_work_queue.md`
- Superseded scope: create/edit workspace direction in `docs/03_blueprints/ui/0011_cashier_stepper_mobile_ui_redesign.md`
- Workflow: `docs/01_standards/0004_session_start_protocol.md`
- DoD: `docs/01_standards/core/0013_proof_and_progress.md`
- ADR: existing Note/payment/inventory/idempotency/revision/refund/projection contracts; no new architecture decision was required
- Previous handoff: `docs/04_lifecycle/handoff/0020_cashier_note_workspace_simple_detail_pos_hardening_handoff.md`
- Repo snapshot / command output: local and remote `main` started the payment-transparency continuation at `84d162f977844021bcca28a3fdb0bfb574f5d12d`

## Locked Facts

- Product, service, and store-stock package use query -> authoritative selection -> compact stamp -> explicit `×` release; ordinary focus/click/re-render and stale requests do not release or revive identity.
- Product release clears ID, price, floor, stock, quantity-derived warning state; service release clears catalog identity/default price; package release clears template, service, product, and decomposition state.
- Fresh create is Simple by default and may enable Detail. Edit/revision renders no presentation toggle and is hard Detail in both Blade and JavaScript state.
- Cashier history defaults to `Belum Selesai`; classification is performed before pagination by the existing projection query adapter, not by Blade or browser JavaScript.
- `Belum Selesai` contains a non-refunded note with outstanding money or open operational work. `Selesai` contains a terminal refund or a note with neither outstanding money nor open work. Refund/canceled context remains visible.
- No transaction, payment, inventory, revision, refund, idempotency, audit, or projection write contract was replaced.
- History chronology is `notes.created_at DESC`, then note ID only as a deterministic fallback; `transaction_date` remains a business-date/window field.
- Note detail exposes each official customer payment event newest first, including method, cash received/change, allocation context, and post-event remaining balance while retaining the aggregate settlement summary.
- New payments use microsecond `recorded_at` to prevent rapid-event UUID ordering drift. Legacy events fall back to existing timestamps.
- Revision surplus remains an automatic refund lifecycle and is not represented as customer credit or a default manual approval action.

## Scope Used

### SCOPE-IN

- Shared selected-stamp and explicit-release behavior for product, service, and store-stock package.
- Create-only Simple mode and edit/revision hard Detail presentation.
- Two-focus cashier history backed by the existing projection/query boundary.
- Responsive workspace/history behavior at 360, 390x844, 412, 768, 992, and 1440x900.
- Functional browser proof, adversarial database regression, repository verification, permanent blueprint updates, and exact static-asset manifest.
- Same-note partial -> partial -> settlement chain proof and user-visible payment timeline reconciliation.

### SCOPE-OUT

- New mobile/desktop/Simple backend workflow or business semantics.
- Simplification of note detail/audit representation.
- Production deployment, production migration/data mutation, or upload to the shared static CDN.
- Claiming physical-phone or installed-PWA acceptance from Chromium emulation.

## GAP

- Physical phone and installed standalone PWA require manual device acceptance.
- The separate production release must advance `ASSET_VERSION`, run only the reviewed targeted asset upload, and smoke-check deployed CDN/application responses.

## Locked Decisions

- Display text is presentation only; canonical product/catalog/template IDs remain authoritative.
- Explicit `×` is the only selected-stamp release interaction and must atomically clear dependent presentation state.
- Simple mode exists only on fresh/create workspace; edit/revision cannot enter it.
- History is a two-focus navigation queue while detail remains the full audit surface.
- History classification uses projected outstanding plus operational open-work truth, with terminal refund as an explicit completed override.
- Static UI remains same-origin locally; production CDN sync is a separate, targeted release action.
- Payment chronology comes from official persisted events through Application -> Port -> Adapter; Blade and JavaScript do not calculate financial history.

## Files Created / Changed

### New files

- `docs/03_blueprints/ui/0015_cashier_note_history_two_focus_work_queue.md`
- `public/assets/static/css/cashier-note-history.css`
- `tests/Feature/Note/CashierNoteHistoryWorkQueueClassificationFeatureTest.php`
- `docs/04_lifecycle/handoff/0021_cashier_final_preproduction_ux_hardening_handoff.md`
- `app/Ports/Out/Payment/NotePaymentTimelineReaderPort.php`
- `app/Adapters/Out/Payment/DatabaseNotePaymentTimelineReaderAdapter.php`
- `app/Adapters/Out/Payment/Queries/DatabaseNotePaymentTimelineAllocationAmountsQuery.php`
- `app/Application/Note/Services/NotePaymentTimelineBuilder.php`
- `database/migrations/2026_09_05_000100_add_recorded_at_to_customer_payments_table.php`
- `resources/views/shared/notes/partials/payment-timeline.blade.php`
- `public/assets/static/css/cashier-note-payment-timeline.css`
- `tests/Feature/Note/CashierNotePaymentTimelineChainFeatureTest.php`
- `tests/Feature/Payment/DatabaseNotePaymentTimelineReaderAdapterFeatureTest.php`

### Changed files

- Cashier history HTTP request/controller and `CashierNoteHistory*` projection query adapter classes.
- Cashier history Blade/JavaScript/CSS; obsolete filter drawer removed.
- Cashier workspace create Blade, selected-stamp templates, isolated workspace CSS, and modular lookup/presentation/row JavaScript.
- Cashier create/edit/history feature and presentation contract tests.
- UI blueprint `0014` and handoff index.
- Note-detail page-data builder/payload/view, payment writer/provider, history query ordering, payment action policy, surplus audit wording/UI, and their regression tests.

## Verification Proof

- command: `php artisan test tests/Feature/Note tests/Feature/Payment tests/Feature/Database/PaymentRefundTimestampSchemaTest.php`
  - result: PASS, 453 tests and 3,525 assertions
  - meaning: the new event timeline and chronology regressions are green with the existing payment, allocation, idempotency, revision, refund, stock, audit, and projection suite
- command: `node /tmp/glasspos-ui-browser-proof.mjs`
  - result: PASS with a real same-note Rp120.000 chain: cash Rp24.000, transfer Rp36.000, cash settlement Rp60.000; all three events remained separate and newest-first
  - meaning: the visible timeline showed methods, cash received/change, remaining Rp96.000 -> Rp60.000 -> Rp0, and aggregates reconciled to total/paid Rp120.000 with zero outstanding; same-date history rendered newest -> oldest by `created_at`

- command: `php artisan test` for eight focused cashier history/workspace test files
  - result: PASS, 22 tests and 178 assertions
  - meaning: two-bucket classification, search/pagination boundary, response compatibility, create-only Simple, hard Detail edit, and workspace contracts are green together
- command: `php artisan test tests/Feature/Note`
  - result: PASS, 367 tests and 3,051 assertions
  - meaning: financial, stock, idempotency, rollback, revision, refund, audit, and projection Note regressions remain green
- command: `node /tmp/glasspos-ui-browser-proof.mjs`
  - result: PASS at 360, 390x844, 412, 768, 992, and 1440x900; no relevant runtime error, failed cashier asset, duplicate active name, sticky overlap, or overflow
  - meaning: four add controls, remove, Detail toggle, product/service/package search-stamp-release-reselect, stale request, keyboard navigation, quantity, partial open/cancel/pay, save, full cash, Detail transfer, edit PATCH, refund surface, and history bucket/search/pagination/Detail/Edit were interacted with
- command: `node --check` for all changed JavaScript; `vendor/bin/pint --dirty`; `make audit-contract`; `make audit-hex`; `git diff --check`
  - result: PASS
  - meaning: syntax, formatting, line-count/Blade, architecture boundaries, and whitespace checks pass
- command: `make verify`
  - result: PASS; PHPStan analyzed 2,023 files with no errors, contract audit passed, and 1,585 tests passed with 10,135 assertions
  - meaning: the canonical full repository quality gate is green

## Risks / Follow-up Notes

- Chromium emulation proves responsive browser behavior, not a physical device or installed standalone PWA; `display-mode: standalone` remained false.
- Five pre-existing compiled navigation warnings involving `Mr.isElementInViewport` remain outside these cashier modules; no cashier workspace/history JavaScript exception occurred.
- Static UI files were deliberately not uploaded to the shared CDN.
- Exact changed public-asset manifest for this continuation:
  - added: `static/css/cashier-note-payment-timeline.css`;
  - deleted locally and no longer referenced: `static/js/pages/note-surplus-refund-due.js`.
- The obsolete remote JS object does not need a destructive CDN delete because no shipped view loads it; the targeted upload contains only the new CSS.
- Safe release command for this continuation, after advancing production `ASSET_VERSION`:

```bash
php artisan r2:upload-public-assets --path=static/css/cashier-note-payment-timeline.css
```
- Exact production-target asset paths, relative to `public/assets`, are:
  - `static/css/cashier-note-history.css`
  - `static/css/cashier-note-workspace.css`
  - `static/js/pages/cashier-note-index.js`
  - `static/js/pages/cashier-note-workspace/package-search.js`
  - `static/js/pages/cashier-note-workspace/presentation.js`
  - `static/js/pages/cashier-note-workspace/rows.js`
  - `static/js/pages/cashier-note-workspace/search.js`
  - `static/js/pages/cashier-note-workspace/service-catalog.js`
- Safe release command after advancing production `ASSET_VERSION`:

```bash
php artisan r2:upload-public-assets \
  --path=static/css/cashier-note-history.css \
  --path=static/css/cashier-note-workspace.css \
  --path=static/js/pages/cashier-note-index.js \
  --path=static/js/pages/cashier-note-workspace/package-search.js \
  --path=static/js/pages/cashier-note-workspace/presentation.js \
  --path=static/js/pages/cashier-note-workspace/rows.js \
  --path=static/js/pages/cashier-note-workspace/search.js \
  --path=static/js/pages/cashier-note-workspace/service-catalog.js
```

## Next Step

Run the separate production release workflow: advance `ASSET_VERSION`, execute the reviewed targeted asset sync, deploy without force push/data mutation, then perform deployed smoke plus physical-phone/installed-PWA acceptance.
