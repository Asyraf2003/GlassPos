# Issue #8 — independent contract gate repair

## Scope and facts

Branch `refactor/8-restore-contract-gate` starts at accepted main `da5bc06b`.
Issue #8 is independent of C3/Issue #7. PR #9 remains draft until this prerequisite is merged and its combined proof is green. No C3 code or workstation `.codex/` configuration is included.

Six existing files exceeded the 100-line contract gate. Blueprint0019 records the responsibility boundaries before extraction: history presentation mapper, lifecycle state readers, accepted revision base guard, restore audit recorder, Detail header payload builder, and legacy payment settlement reader.

## Behavior preservation and review

The diff review compared extracted bodies and their call sites against main. History SQL/snapshot decoding and event labels are unchanged. State fields and mutation methods remain intact. Root locking and cancelled-root validation still precede the revision base/editability guard. Restore timeline and canonical audit writes remain ordered inside the existing caller-owned transaction; event identity, timestamps, snapshots and metadata remain unchanged. The reread restored note has the same locked root identity. No transaction/idempotency/ledger/version writer was replaced.

Detail payload keys, current-revision precedence, cancelled legacy zero values, gross-payment fallback, refund arithmetic and partial-payment validation are unchanged. DI wiring and the existing unit-test constructor were updated for the extracted reader. Assertions were not weakened. This is an author diff-review pass, not an independent approval.

## Proof

Disposable MariaDB at `127.0.0.1:3321`, database `glasspos_cancellation_c1_test`; application database untouched.

- Existing affected suite before production edits: 42 tests / 630 assertions passed.
- Same suite after extraction: 42 tests / 631 assertions passed. Runtime observer branches can vary assertion count.
- Scope: Note/state unit tests, gross/outstanding settlement, correction history/Detail, presentation contract, cancellation and restore success/atomicity/concurrency, revision rollback/store-stock rollback, revision and financial revision concurrency.
- Initial post-change run: 41 passed; exact replay probe failed to observe the lock wait. Subsequent diagnostic invocation could not connect because the disposable server had stopped across the session boundary. After restarting that isolated server, the complete affected suite passed with the original overlap assertions intact. Do not describe the first run as green or infer a production defect from it.
- `vendor/bin/phpstan analyse --memory-limit=2G`: no errors.
- Pint on changed and new PHP files: passed.
- `make audit-contract`: line-count and Blade gates passed, no new bypass.
- `git diff --check`: passed.

No full-suite or end-to-end cashier acceptance claim. Broad campaign verification remains at closeout.

## Next

Push and review the dedicated Issue #8 PR; merge only green. Then sync published C3 with accepted main, rerun contract gate and relevant C3 regression, review the combined diff, and only then mark PR #9 ready and merge. Do not begin C4 before PR #9 merges.
