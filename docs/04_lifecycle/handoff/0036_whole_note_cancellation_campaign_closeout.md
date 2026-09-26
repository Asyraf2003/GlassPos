# Whole-note cancellation campaign closeout

Date: 2026-09-27. Active C6 Issue #15, branch `test/15-cancellation-campaign-closeout`, base b881de37 (C5 PR #14). This receipt distinguishes automated engineering acceptance from physical shop acceptance.

## Accepted chain

C0 #1/PR #2 contract; C1 #3/PR #4 unpaid cancellation; C2 #5/PR #6 restore-as-revision; C3 #7/PR #9 Detail refund; independent #8/PR #10 contract gate; C4 #11/PR #12 truthful external boundary; C5 #13/PR #14 Detail lifecycle forms. Each used a separate issue/branch/PR and merge commits retaining history. C6 has its own PR; no candidate changes were pushed directly to main. `.codex/` remains local-only.

## Contract and effect coverage

- Unpaid persisted product/service/package cancellation neutralizes active sale, receivable, profit and canonical stock effects while retaining immutable issue/revision/history. No money event is invented.
- Any recorded payment remains accountable and uses existing eligible Refund. C3 supports service and explicit stock return/no-return, with per-component reporting and audit; no Auto presets.
- External purchase, including full debt, is explicitly unsupported for pure cancellation and this selected refund flow. Specialized external service/pass-through/supplier rules were not invented. C4 does not claim those capabilities implemented.
- Restore commits a new current revision with fresh component/stock effects, parent/source/cancellation identity, stale rejection and retained history. It does not toggle a flag or undelete.
- Existing root locking, transaction, command receipts and durable audit enforce replay/conflict/rollback. Tests include actual independent-session cancel/cancel, cancel/payment, cancel/revision, paid-cancel/refund, refund/refund and restore/restore competitors; no sequential test is presented as overlap proof.
- Cashier date policy and admin capability remain distinct from broad admin read scope. Detail forms preserve observed identity/key on retry, require reason and explain paid/external restrictions. Cancellation/restore history shows actor, reason and time.

## C6 findings and fixes

1. Old cancellation/refund race fixture lacked C3's required stock choice. Reproduced failure, added explicit return choice, preserved all overlap/cardinality assertions.
2. Full scan found C3's no-return revision guard also skipped legitimate old partial-refund compensation. Restrict the skip to fully refunded component amounts; existing reversal-source guard remains. Existing partial-refund regression plus full no-return package chain pass.
3. Full scan found C5 actor visibility leaked into ordinary correction history. Restrict added actor display to cancellation/restore lifecycle events; preserve the old correction presentation contract.
4. Browser screenshot showed cancelled Detail still displaying R1's outstanding value. A new RED assertion locked this gap. Current workspace rows now expose zero outstanding and no active pay/edit/refund rights when root is cancelled; original snapshot amount remains history. Existing billing excludes canceled rows. No financial source data is changed by reads.

Focused proof: race/report parity 5 tests / 128 assertions; final affected fixes 23 tests / 288 assertions. Cancellation report parity verifies zero current dataset/HTML/XLSX, PDF export, immutable source reads and cross-date COGS: original issue 59163, next-day compensation -59163, combined zero.

## Live browser proof

Guarded fixture `scripts/fixtures/cancellation-live.php` requires APP_ENV=testing, localhost3321 and database `glasspos_cancellation_browser`, with no existing notes. Migrate only that disposable schema, then run fixture; serve it on localhost8128 with SESSION_DRIVER=file. `node scripts/test-cancellation-live.mjs` uses actual login, production HTML forms and captured authenticated POST bodies.

Final browser receipt: `/tmp/glasspos-cancellation-live-H2wTxC/proof.json`. Desktop1280 and handset390 each execute cancel -> reload -> restore -> reload, with exact POST replay returning200 and unchanged stock/version graph. Stock17 after cancellation/14 after restore, three final revisions, two retained cancellations/restores, zero payments/refunds. Required reason, viewport fit and zero cancelled outstanding are asserted. Screenshot cancel-390.png visually inspected: original revision206258, active amount0, outstanding0, cancellation badge and restore control visible.

Initial browser attempt timed out locating DevToolsActivePort because snap Chromium uses a private temp directory. Runner now obtains the port from Chromium stderr; no application behavior was changed for this environment issue. Old Blueprint0018 live refund runner is updated to explicitly choose return under C3; its syntax is checked, but that older complete A3/A5 scenario was not rerun as C6 live proof.

## Verification and review

Full verification result is recorded below after the command completes. First broad run was RED: 1779 passed, two failures described above; that is not a green receipt. New focused tests and browser evidence guided the small corrections.

Author diff review covers current/history separation, partial versus full refund compensation, existing locks/audit boundaries, fixture inputs without weakened assertions, guarded browser data and export read-only behavior. No independent approval claimed.

Separate optional `make audit-hex` remains RED on the same two ProductCatalog framework imports recorded in Blueprint0018 closeout0028. Tracked as Issue #16; no unrelated refactor bundled. Historical migration rollback dependency also remains outside this campaign. `make verify` does not include audit-hex.

## Limits and operational acceptance

No production deployment, data migration repair, secret/host change or physical cashier session was performed. Physical handset keyboard/touch, installed PWA/offline/resume and real shop habit acceptance remain manual-only; browser emulation is not their sign-off. External specialized refunds remain the explicit supported-scope limitation. Auto still requires observed cashier habit and a later owner-directed compression slice.

## Final automated GREEN

From repository root:

    env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3321 DB_DATABASE=glasspos_cancellation_c1_test DB_USERNAME=root DB_PASSWORD= make verify

Exit0: **1781 tests / 13627 assertions / 154.90 seconds**, no failed or skipped tests reported. PHPStan2170 files/no errors, line-count/Blade contract audit pass. Final log `/tmp/glasspos-c6-final-verify.log`. Focused corrections preceded this full repeat; no production changes followed it. Changed PHP Pint, new PHP fixture formatting, Node syntax and diff check pass. Browser final receipt above is GREEN after the cancelled outstanding fix.

C6 is ready for author-reviewed PR/check/merge. Automated campaign scope is complete subject to that merge; physical acceptance and explicit unsupported external capabilities remain as stated, not hidden behind test counts. No further automatic feature slice or Auto preset is opened by closeout.
