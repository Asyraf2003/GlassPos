# 0020 - Cashier Note Workspace Simple/Detail POS Hardening Handoff

## Metadata

- Date: 2026-09-04
- Slice / topic: Cashier Note Workspace Simple/Detail POS hardening, financial regression, and static asset delivery closure
- Workflow step: implementation closure and repository quality gate
- Status: closed locally; production deployment and physical-device acceptance remain out of scope
- Progress: 100% of the local closure slice

## Target Work Page

Cashier `Buat Nota` and edit/revision workspace presentation, its shared transaction/payment form state, and the local/static-CDN delivery boundary required to load that workspace reliably.

## References Used

- Blueprint: `docs/03_blueprints/ui/0014_cashier_note_workspace_simple_detail_pos_hardening.md`
- Superseded scope: create/edit portion of `docs/03_blueprints/ui/0011_cashier_stepper_mobile_ui_redesign.md`
- Related blueprint: `docs/03_blueprints/ui/0013_cashier_responsive_simple_complex_modes_note.md`
- Workflow: `docs/01_standards/0004_session_start_protocol.md`
- DoD: `docs/01_standards/core/0013_proof_and_progress.md`
- ADR: existing Note, Payment, Inventory, idempotency, revision, refund, audit, and projection contracts; no new ADR was required
- Previous handoff: `docs/04_lifecycle/handoff/0019_cloudflare_r2_static_sync_direct_upload_handoff.md`
- Error log: `docs/04_lifecycle/error_log/0063_static_asset_url_implicitly_coupled_to_r2_public_url.md`
- Repo snapshot / command output: local `main` and `origin/main` both started at `135762c81055f3b38595d811207c07cbbc052ec7`

## Locked Facts

- Fresh create renders one shared form in Simple mode with an idempotency key, four direct transaction type controls, and zero initial rows.
- Edit/revision renders the same form in Detail mode by default.
- Simple payment actions assemble the existing skip, full-cash, and partial-cash payload decisions; they do not call a new endpoint or application path.
- Product identity remains the selected product ID; query/display text is not accepted as identity.
- The existing server-side stock, price floor, package template/autosplit, payment, idempotency, transactional rollback, revision, refund, audit, and projection guards remain authoritative.
- Local `asset()` URLs now remain same-origin unless `ASSET_URL` is explicitly configured; `R2_PUBLIC_URL` remains a media/object-storage concern.

## Scope Used

### SCOPE-IN

- Simple-mode noise removal, zero-row fresh create, compact Detail switch, and action availability.
- Responsive desktop POS and mobile cashier presentation using shared state/form names.
- Product/service/package selected-state and quantity/payment interaction proof.
- Adversarial HTTP/database regression for financial, stock, idempotency, and rollback invariants.
- Explicit static asset URL separation and safe targeted R2 public-asset upload.
- Browser interaction/responsive proof, permanent blueprint, error log, and handoff.

### SCOPE-OUT

- New backend transaction/payment endpoints or domain semantics.
- Simplifying history/detail audit views.
- Production deploy, migration, production data mutation, or upload to the shared production CDN.
- Claiming physical phone or installed-PWA acceptance from Chromium emulation.

## GAP

- Physical-phone and installed standalone-PWA acceptance remain manual.
- Production must set its explicit `ASSET_URL`, advance `ASSET_VERSION`/release version, sync only the reviewed changed assets, and verify CDN responses during the separate deploy phase.

## Locked Decisions

- Blueprint `0014` supersedes only the create/edit stepper ceremony direction of blueprint `0011`; other cashier surfaces and history/detail remain unaffected.
- Simple is fresh-create default; a single unchecked `Detail` switch enables advanced fields. Edit/revision defaults Detail on.
- Viewport width owns layout; pointer/hover capability owns touch interaction; standalone display mode owns safe-area treatment. No user-agent branch exists.
- Mobile and desktop use one renderer state/form contract and the same HTTP/application/domain flow.
- `ASSET_URL` and `R2_PUBLIC_URL` are independent configuration concerns.
- Targeted public-asset upload takes explicit paths under `public/assets` and overwrites only those matching R2 keys.

## Files Created / Changed

### New files

- `app/Console/Support/PublicAssets/PublicAssetPathSelector.php`
- `docs/03_blueprints/ui/0014_cashier_note_workspace_simple_detail_pos_hardening.md`
- `docs/04_lifecycle/error_log/0063_static_asset_url_implicitly_coupled_to_r2_public_url.md`
- `docs/04_lifecycle/handoff/0020_cashier_note_workspace_simple_detail_pos_hardening_handoff.md`
- `tests/Feature/Note/CashierSimpleWorkspaceAdversarialFinancialRegressionTest.php`

### Changed files

- `.env.example`
- `config/app.php`
- `app/Console/Commands/UploadPublicAssetsToR2.php`
- `app/Console/Support/PublicAssets/PublicAssetUploadReporter.php`
- cashier workspace Blade partials and isolated CSS/JavaScript modules
- cashier workspace presentation and infrastructure feature tests
- `docs/04_lifecycle/handoff/README.md`

## Verification Proof

- command: `php artisan test` with 24 focused Note/infrastructure files
  - result: PASS, 114 tests and 1,324 assertions
  - meaning: fifteen adversarial scenarios plus canonical package, payment, rollback, idempotency, edit/revision, refund, and targeted-asset behavior remain green together
- command: `node /tmp/glasspos-ui-browser-proof.mjs`
  - result: PASS across widths 360, 390x844, 412, 768, 992, and 1440x900
  - meaning: all four add controls, remove, Detail on/off, product query/keyboard/select/change/quantity, service/package select, partial open/cancel/submit, skip, real full cash, Detail transfer, edit PATCH, and refund surface were interacted with; no overflow, duplicate active names, sticky/input overlap, or workspace asset/runtime failure was observed
- command: `node --check` for every changed workspace JavaScript file; `php -l` for changed PHP; `vendor/bin/pint --dirty`; `git diff --check`
  - result: PASS
  - meaning: touched source parses, follows PHP formatting, and contains no whitespace errors
- command: `make audit-contract && make audit-hex`
  - result: PASS; upload command is exactly 100 lines
  - meaning: line-count, Blade PHP boundary, and hexagonal dependency rules remain satisfied
- command: `make verify`
  - result: PASS; PHPStan analyzed 2,016 files with zero errors and full Pest passed 1,576 tests with 10,065 assertions
  - meaning: the repository-wide canonical closure gate is green

## Risks / Follow-up Notes

- Chromium responsive emulation is not physical-device or installed-PWA proof; the browser run recorded `display-mode: standalone` as false.
- A pre-existing compiled navigation warning involving `Mr.isElementInViewport` was observed and excluded from workspace error proof because it is not emitted by the cashier workspace modules. No workspace JavaScript exception occurred.
- Static UI files were deliberately not uploaded to the shared CDN during implementation.

## Next Step

Use the separate production deployment workflow to configure the asset CDN/release version, sync the reviewed changed-asset manifest, and perform physical-device/PWA plus deployed smoke verification.
