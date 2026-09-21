# Blueprint0018 closeout — 2026-09-21

## Status and scope

Automated lifecycle scope through Slice11: GREEN. Slice12 automated regression and bounded browser/emulation proof: GREEN. Physical/manual acceptance remains MANUAL-ONLY / NOT EXECUTED; Blueprint0018 is not unconditionally all-device COMPLETE.

Active source: [Blueprint0018](../../03_blueprints/finance/0018_primitive_lifecycle_contract_map_and_adversarial_torture_tests.md). Contract: [ADR0042](../../02_architecture/adr/0042_note_edit_refund_settlement_machine_contract.md). Per-slice history, classifications and commands: [handoff0027](0027_primitive_lifecycle_ordered_execution_handoff.md). CLOSED Slices1–9 were not redesigned; their tests participate in the required final regression.

## Automated GREEN

| Scope | Evidence and boundary |
|---|---|
| A payment/debt/cash | PrimitivePaymentDebtCashChainFeatureTest; exact payment intent, multiple DP, revision carry, final settlement and cash details. |
| B refund/revision/receivable | PrimitiveRefundRevisionReceivableChainFeatureTest; source split, ordinary versus surplus returns, repeated revision, reopened debt and second close. |
| C cancellation/correction/version | PrimitiveCancelCorrectionVersionChainFeatureTest and existing guarded mutation probes; no fabricated reset/correction UI. |
| D inventory | PrimitiveInventoryRevisionRefundChainFeatureTest; ten source-specific movement rows, immutable original issues, source costs, insufficient-stock rollback. |
| E reports | PrimitiveLifecycleReportingChainFeatureTest; current/event bases, exports, source hashes and cross-date reporting. |
| Slice10 atomic capture | Payment, revision, ordinary/direct refund and standalone surplus actual-writer failures; rollback and same-key retry. FK-bound surplus canonical events retain the CLOSED synchronous compatibility path. No crash-durability claim. |
| Slice11 ordering | Exact concurrent replay; same-key changed payload and different root conflict; fresh-key stale base; winner rollback allows waiting claim. Independent forked connections, held transaction barrier, third observer. |
| Slice11 financial/inventory | Payment then waiting revision, refund then waiting revision; one stock compensation/reversal, no duplicate money/version effects. Existing payment/payment and payment/refund T15 retained. |

Slice11 publication: 399a41eddd806e457bab837074de0c3ec7024d20 matched HEAD, origin/main and remote refs/heads/main with a clean tree before Slice12 began. Focused+adjacent:22 tests/415 assertions, exit0. Slice11 broad:1748/13116, exit0. Assertion count can vary by the explicitly asserted server-observation fallback branch; no test was removed in Slice12.

Final repo-root command:

    env APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3319 DB_DATABASE=glasspos_slice10_test DB_USERNAME=root DB_PASSWORD= make verify

Final GREEN:1748 tests/13115 assertions/62.96s, exit0. PHPStan2133 files/no errors; line limit, Blade and contract audits PASS. Artifacts: /tmp/glasspos-blueprint0018-final-verify.log and .exit=0. Node syntax, Pint --test for the new fixture, and git diff --check PASS. This is the final broad gate, not a historical Slice10 result.

## Browser/emulation GREEN

1. `node scripts/test-primitive-lifecycle-presentation.mjs`: exit0,12 scenarios A1/A4/A5/B8 across workspace/detail/Simple. Exact credited amounts agree; Simple intentionally uses exact tender. Synthetic intent fixture, not live acceptance. Receipt /tmp/glasspos-slice12-intent.log and .exit=0.
2. With the final testing DB environment above, `node scripts/test-primitive-lifecycle-pages.mjs`: exit0, underlying HTTP2 tests/69 assertions/1.56s. Chromium1280x844 and390x844 execute production desktop/handset markup and JS: modal focus/close/reopen, insufficient-tender disabling, amount fit, scrolling, validation, Back/reload/Forward, A5 action absence, B4/B6 history/surplus and editor fit. Artifact /tmp/glasspos-primitive-pages-mVxhuH; /tmp/glasspos-slice12-pages.exit=0. Handset a4-cash-390.png visually inspected:112903/120011/7108/137983 and both action buttons readable. Exported-page navigation is not live persisted submission.
3. `node scripts/test-primitive-lifecycle-live.mjs`: exit0. Real local HTTP server, real login, real production payment/refund controls, network-captured POST replay in the same authenticated browser session, persisted DB assertions and reload. Artifact /tmp/glasspos-slice12-live-9eKLRq/proof.json plus live-a5.png and live-refund.png. Refund screenshot visually inspected; captured during page appearance transition, not used as a contrast/design acceptance gate.

Live fixture starts at A3 via real authorized HTTP/domain actions, not direct lifecycle row inserts. Browser submits A4 cash112903/tender120011 then A5 cash137983/tender150007; exact captured-key retries each return HTTP200. Payments remain3 then4; outstanding137983 then0; total accepted413472; cash changes26874,7108,12024. Payment action disappears after close.

The additional browser selected-P refund submits142539, split across3 source rows. Exact POST replay returns200 without changing refund rows or inventory movements. P stock17, one reversal for the current source, active total270933/outstanding0 after reload; old P no longer selectable. This is a bounded live refund variant after A, not a claim that the browser enacted every B–E battle-card step.

Reproduction boundary: create an empty `glasspos_slice12_browser` schema on disposable localhost3319, run forward migrations, then run `scripts/fixtures/primitive-lifecycle-live.php` with APP_ENV=testing and the same DB parameters except DB_DATABASE=glasspos_slice12_browser. It refuses another environment/database/host/port and a nonempty note fixture. Run a real server with APP_ENV=local, APP_URL=http://127.0.0.1:8129, SESSION_DRIVER=file, CACHE_STORE=array and that same disposable schema; then the live runner. Test-only login is slice12@example.test with factory password password123. Never use these fixtures against production. Fixture note date uses the actual configured server timezone; do not freeze it outside the cashier access window.

## Classifications and explicit GAPs

- Initial live attempt used a fixed15September fixture against a21September server. A4/A5 accepted through the outstanding-finance queue, but retry after final close received403 from EnsureCashierNoteAccess → assertCanCollectOutstanding before replay. Source confirms the old note leaves the payment queue once outstanding disappears. This was an invalid date-window assumption for the intended current-day browser fixture, not evidence of a duplicate payment or failed payment commit. Current-day actual POST replay passes. Authorization outside the date window was not weakened; no new authorization precedence contract was chosen.
- Environment restart removed temporary artifacts/processes during continuation. The new live fixture/runner are committed for reproducibility; final browser/gate artifacts were recreated. Earlier handoff receipts remain historical evidence even if their /tmp files are absent.
- Separate `make audit-hex` remains RED, exit2: pre-existing Illuminate DB imports in ProductCatalog BulkProductMaintenanceRunner:8 and BulkProductMaintenanceValidator:7. Both existed at Slice10 closure f5f99752; last source commit d753ae42. Artifact /tmp/glasspos-blueprint0018-hex.log and .exit=2. No claim that every repository static gate is GREEN; repair is outside this bounded lifecycle scope.
- Historical migration rollback dependency remains a separate discovered defect: products.active_unique_marker depends on deleted_at, while the later unique-constraint down() leaves the generated column and the older soft-delete down() tries to drop deleted_at. No historical migration changed.

## Manual-only acceptance still pending

| Check | Status and required physical evidence |
|---|---|
| Actual handset keyboard, safe area, touch/focus/scroll | MANUAL-ONLY / NOT EXECUTED. Responsive Chromium is not a physical handset. |
| Installed PWA, resume/background, device Back/Forward and offline/reconnect | MANUAL-ONLY / NOT EXECUTED. No physical device/PWA interaction was available. |
| Human enactment of complete A–E battle cards and cashier→admin handoff | NOT EXECUTED as a complete manual session. Arithmetic/mutation chains are automated; browser proof is the explicitly listed representative surfaces/actions. Do not mark manual sign-off from test counts. |

## Cleanup and publication

After final tests, only glasspos_slice12_browser and glasspos_slice10_test were dropped on disposable localhost3319. Cleanup exit0; information_schema query returned remaining_disposable_schemas=0. Test success and external cleanup are separate results. No full migrate:rollback; no production database touched.

Each repository file change was immediately followed by sai push per owner instruction. Final handoff publication and remote/clean proof are recorded in the accompanying session receipt. Next work is physical/manual sign-off or a separately authorized explicit GAP; do not reopen CLOSED lifecycle slices without contradictory evidence.
