# Handoff 0011 - Local Procurement Projection and Legacy ServiceWorker Cleanup

## FACT

- Session ini berawal saat Phase 6 / next audit candidate `0003_route_security_boundary.md` akan dimulai.
- Work Phase 6 route security belum dilanjutkan karena owner menemukan local anomaly pada halaman admin procurement supplier invoice.
- Owner reported symptom:
  - supplier data ada.
  - supplier invoice source rows ada.
  - halaman UI `Nota Pemasok` kosong saat dibuka dari menu sidebar.
  - data muncul setelah refresh / Ctrl+Shift+R.
- Source-confirmed UI table supplier invoice membaca dari `supplier_invoice_list_projection`, bukan langsung dari `supplier_invoices`.
- Initial local row-count proof from owner showed:
  - `suppliers = 78`
  - `supplier_invoices = 24`
  - `supplier_invoice_lines = 72`
  - `supplier_receipts = 24`
  - `supplier_receipt_lines = 72`
  - `supplier_payments = 24`
  - `supplier_payment_proof_attachments = 12`
  - `supplier_invoice_list_projection = 0`
  - `supplier_list_projection = 0`
  - `audit_events = 0`
  - `audit_event_snapshots = 0`
  - `supplier_invoice_versions = 0`
  - `employee_versions = 0`
- Owner then rebuilt local projection and audit baseline.
- Post-rebuild owner proof showed:
  - `supplier_invoices = 24`
  - `supplier_invoice_list_projection = 24`
  - `supplier_list_projection = 78`
  - `audit_events = 2413`
  - `audit_event_snapshots = 2413`
  - `supplier_invoice_versions = 24`
  - `employee_versions = 53`
- After projection/audit rebuild, the UI still showed empty when opened through sidebar menu, but showed 24 rows after browser refresh.
- Network proof showed normal navigation/fetch was served through ServiceWorker and referenced `sw.js:53`.
- Current source layout registers `service-worker.js`, not `sw.js`.
- Current `public/service-worker.js` only handles push notification and notification click behavior; it does not define a fetch cache handler.
- Owner manually ran ServiceWorker unregister and CacheStorage delete from browser console.
- After unregistering ServiceWorker and deleting CacheStorage, the procurement invoice UI showed the expected rows.
- A local source patch was applied to `public/assets/static/js/shared/push-notifications.js` to clean up legacy `/sw.js` registrations automatically.

## REFERENCES

- `docs/04_lifecycle/handoff/0010_phase_5_seeder_createonly_contract_hardening_handoff.md`
- `docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/0003_route_security_boundary.md`
- `resources/views/admin/procurement/supplier_invoices/index.blade.php`
- `app/Adapters/Out/Procurement/DatabaseProcurementInvoiceTableReaderAdapter.php`
- `routes/console.php`
- `database/seeders/CreateOnly/CreateAuditBaselineSeeder.php`
- `resources/views/layouts/app.blade.php`
- `public/service-worker.js`
- `public/assets/static/js/shared/push-notifications.js`

## SCOPE-IN

- Characterize why local supplier invoice UI was empty while source data existed.
- Restore local projection and audit baseline using existing commands.
- Identify stale legacy ServiceWorker as the browser/runtime cause of menu-navigation emptiness.
- Add local app-shell cleanup for legacy `/sw.js` ServiceWorker registrations.
- Preserve Phase 6 route security audit as next unstarted work.

## SCOPE-OUT

- Do not modify database schema.
- Do not modify procurement query behavior.
- Do not modify routes, middleware, controllers, or auth policy.
- Do not treat this as closure of `0003_route_security_boundary.md`.
- Do not touch shared hosting scope.
- Do not touch git unless owner explicitly requests it.

## GAP

- No full test suite output was provided after this local patch in-session.
- No `git diff` output was captured in-session.
- No browser Network screenshot after patch was archived into repo.
- No dedicated automated test exists yet for legacy ServiceWorker cleanup.
- Phase 6 / `0003_route_security_boundary.md` remains pending and was not characterized in this session beyond document reading.

## DECISION

- The local procurement invoice UI issue is classified as:

  FIXED LOCALLY WITH LEGACY SERVICE WORKER CLEANUP

- The data-layer problem was not a missing supplier invoice source problem.
- The initial UI emptiness had two layers:
  1. projection/audit baseline was empty after source-only seeding;
  2. after projection/audit rebuild, stale legacy ServiceWorker `/sw.js` still controlled local browser navigation/fetch and caused stale empty UI behavior.
- The code patch belongs in `public/assets/static/js/shared/push-notifications.js` because that file already owns ServiceWorker registration for push notification support.

## BLUEPRINT

Problem being solved:

- Local admin procurement invoice page showed no rows even though source procurement data existed.

Facts already known:

- `supplier_invoices` contained 24 rows.
- `supplier_invoice_list_projection` initially had 0 rows.
- UI table reads from `supplier_invoice_list_projection`.
- After projection rebuild, `supplier_invoice_list_projection` had 24 rows.
- Audit baseline initially had 0 rows.
- After audit baseline seed, `audit_events`, `audit_event_snapshots`, `supplier_invoice_versions`, and `employee_versions` were populated.
- Stale ServiceWorker `/sw.js` controlled the browser.
- Unregistering ServiceWorker and clearing CacheStorage made the UI work.

Binding rules:

- No git touch unless owner explicitly asks.
- Do not patch before proof.
- One active workstream at a time.
- Facts and proof must stay separate from inference.

Recommended approach used:

1. Confirm source rows versus projection rows.
2. Rebuild projections using existing artisan projection command.
3. Seed audit baseline using existing create-only audit baseline seeder.
4. Confirm UI still had menu-navigation anomaly.
5. Use Network proof to identify stale ServiceWorker involvement.
6. Clean stale ServiceWorker and CacheStorage.
7. Patch app-shell JS to automatically unregister legacy `/sw.js`.

## WORKFLOW

Completed local workflow:

1. Source/projection row-count characterization.
2. Projection rebuild.
3. Audit baseline seed.
4. Browser/runtime anomaly characterization.
5. Manual ServiceWorker unregister proof.
6. Legacy ServiceWorker cleanup patch.

Pending workflow:

1. Capture local diff proof.
2. Run focused static proof for cleanup helper.
3. Optionally run relevant frontend/manual browser proof.
4. Return to Phase 6 route security boundary characterization.

## ACTIVE STEP

Completed active step:

- Local procurement projection and legacy ServiceWorker anomaly characterization/fix.

Patch area:

- `public/assets/static/js/shared/push-notifications.js`

Patch intent:

- Detect ServiceWorker registrations whose active/waiting/installing script URL ends with `/sw.js`.
- Unregister those legacy registrations.
- Delete CacheStorage.
- Reload once using a `sessionStorage` guard to avoid infinite reload.
- Keep cleanup best-effort and non-blocking.

## PROOF

Owner-provided row-count proof after rebuild:

    supplier_invoices = 24
    supplier_invoice_list_projection = 24
    supplier_list_projection = 78
    audit_events = 2413
    audit_event_snapshots = 2413
    supplier_invoice_versions = 24
    employee_versions = 53

Owner-provided browser proof:

    After ServiceWorker unregister and CacheStorage delete, supplier invoice UI data appeared.

Source proof targets for follow-up:

    rg -n "legacyReloadFlag|cleanupLegacyServiceWorkers|isLegacyServiceWorkerScript|clearCacheStorage" public/assets/static/js/shared/push-notifications.js

Expected static proof:

    cleanup helper exists in public/assets/static/js/shared/push-notifications.js

Suggested local cleanup command already used during session:

    php artisan optimize:clear

Suggested local browser verification:

    navigator.serviceWorker.controller?.scriptURL || null

Expected after cleanup:

    null

or, if push worker later becomes active again:

    .../service-worker.js

Not expected:

    .../sw.js

Expected UI proof:

    Admin sidebar -> Pengadaan
    UI displays supplier invoice rows.
    Table summary displays Total: 24 nota supplier.

## FILES CREATED / CHANGED

### New files

- `docs/04_lifecycle/handoff/0011_local_procurement_projection_and_legacy_sw_cleanup_handoff.md`

### Changed files

- `public/assets/static/js/shared/push-notifications.js`
  - Added legacy ServiceWorker cleanup for `/sw.js`.
  - Added CacheStorage cleanup.
  - Added reload-once guard using `sessionStorage`.
  - Added best-effort cleanup call after `window.AppPushNotifications` setup.

Possible local-only changed file if owner applied asset version command:

- `.env`
  - `ASSET_VERSION` may have been changed to force browser asset refresh.
  - This is local environment state and should not be committed unless repo policy says otherwise.

## RISKS / FOLLOW-UP NOTES

- The cleanup clears all CacheStorage names for the current origin when legacy `/sw.js` is found.
- This is acceptable for the local stale-worker recovery case, but future PWA cache strategy should be explicit if offline caching is introduced.
- Because `public/service-worker.js` currently does not handle fetch caching, unregistering old `/sw.js` should not remove intended dynamic-page cache behavior.
- If browser still shows `(ServiceWorker)` with `sw.js`, create an additional legacy kill-switch file at `public/sw.js` in a separate step.
- If future production had an older `/sw.js`, migration behavior must be reviewed carefully before deployment.

## NEXT

Return to the original planned Phase 6 audit candidate:

- `docs/04_lifecycle/error_log/2026-05-28_full_repo_audit/0003_route_security_boundary.md`

Next active step should remain characterization only:

1. Read route security boundary issue.
2. Identify exact routes/middleware/controllers/policies involved.
3. Identify source-confirmed facts versus gaps.
4. Identify minimum proof commands.
5. Do not patch route/security behavior before proof.

