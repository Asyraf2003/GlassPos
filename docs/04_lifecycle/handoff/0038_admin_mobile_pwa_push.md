# Admin Mobile PWA + Push Notification UX

Date: 2026-09-28
Issue: https://github.com/Asyraf2003/GlassPos/issues/35
Branch: feat/admin-mobile-pwa-push
Status: CLOSED — implementation merged, local proof GREEN; deployment/device validation remains separate.

## Delivered contract

Admin handset dashboard has App di HP install affordance and Reminder Hutang Pemasok switch. Desktop does not render this card. Separate admin manifest starts /admin/dashboard in standalone; cashier manifest/start/fullscreen and install script remain unchanged. Canonical /service-worker.js remains the system notification worker, without custom audio or silent override.

Permission is requested only on explicit ON action. State reads PushManager.getSubscription plus authenticated backend ownership/expiration status; no localStorage source of truth. Existing subscriptions are reused. Backend failures roll back newly created subscriptions. OFF deletes the current user's backend registration before browser unsubscribe. Denied, unsupported and unavailable configuration remain OFF with explanation. Reload does not automatically revive expired registrations.

Supplier sender selects active subscriptions whose current actor_accesses role is admin, before the existing limit. Cashier and expired subscriptions are excluded. Generic subscriptions and customer push recipient behavior remain intact. Endpoint upsert remains unique. Payload still opens the named supplier invoice list with payment_status=outstanding, sort_by=due_date, sort_dir=asc and no shipment date filter. Reminder selection, finance lifecycle, report/current balances, 100/500 limits and cron command are unchanged.

The toggle uses the existing generic per-browser subscription; this change does not introduce per-topic preferences or separate persistence.

## Final executed proof

Execution context: repository root /home/asus/projects/GlassPos, after refreshing from origin/main d004163d (PR #41).

~~~bash
php artisan test --compact tests/Feature/PushNotification tests/Feature/Admin tests/Feature/Procurement/DatabaseSupplierPayableReminderReaderFeatureTest.php tests/Feature/Cashier/CashierDashboardPwaInstallFeatureTest.php tests/Feature/Cashier/CashierDashboardThemePresentationContractTest.php
node --test tests/Frontend/admin-mobile-push.test.cjs
make audit-contract
./vendor/bin/phpstan analyze --memory-limit=-1 --no-progress
php scripts/audit-line-count.php
git diff --check
~~~

Results: PHPUnit 44 passed / 355 assertions; Node 7 passed / 0 failed; contract audit PASS; PHPStan no errors; line count PASS; diff check PASS.

Proof covers admin-only recipients, cashier excluded from supplier reminders but retained for generic customer reminder delivery, role changes, expiration, ownership, same-endpoint deduplication, enable/reload/disable, denied/unsupported/config missing, failed persistence/unsubscribe, unchanged worker notification and click destination. Existing supplier readiness test requests the payload URL and operational endpoint.

Integration with PR #41 preserved its compact action layout. Its new whole-page icon absence assertion incorrectly matched sidebar icons; the test now checks the two action buttons and absence of icons inside those buttons. No sidebar/UI behavior was changed to satisfy the test.

## Browser proof and limits

Executed in isolated local database glasspos_mobile_pwa_proof_20260928 with Chromium/Playwright installed only in /tmp; no repository dependency added. Actual authenticated HTTP backend routes and CSRF protection were used.

~~~bash
LD_LIBRARY_PATH=/tmp/glasspos-mobile-proof/libs/usr/lib/x86_64-linux-gnu node /tmp/glasspos-mobile-proof/browser.mjs
~~~

Handset light and dark: install states, toggle ON, reload ON without new subscription/prompt, OFF, reload OFF, denied OFF, cashier install/no admin control, zero page errors. Canonical service worker registration was also exercised without mocks. Admin manifest launch stayed on /admin/dashboard with standalone display mode emulated. Unsupported state disabled the switch.

Native permission, PushManager and install events were simulated for deterministic UI flows; real physical installation, external push delivery and OS sound were not tested. Sound/vibration remains subject to browser/OS/device settings. Evidence: ../../03_blueprints/ui/evidence/admin_mobile_pwa/ (browser results, screenshots and command logs).

## Deployment delta / boundaries

Deploy the new admin manifest and handset JS, updated shared helper/layout/view, role-filtered reader/sender and status route through the normal application deployment. Existing HTTPS, valid Web Push/VAPID configuration and session authentication remain prerequisites. Usual route/view cache and asset-version handling applies to these changed routes/templates/assets.

No new schema, migration or project dependency. No package build or deployment performed. No cPanel/scheduler/cron changes. Existing repeated-invocation resend and 100 invoice / 500 subscription limits remain unchanged operational gaps of the earlier closed backend target.

## Merge record

Implementation PR: https://github.com/Asyraf2003/GlassPos/pull/42 (MERGED, ready/non-draft).
Merge SHA: b0a92364d0b3e212b6bf090545d62a9abf10d5b0.
Issue #35: CLOSED automatically.
Self-review confirmed no supplier balance/report/cron/schema changes, cashier launch preserved, and generic push behavior preserved. GitGuardian check succeeded. Owner-authorized implementation target is CLOSED. Production deployment has not been performed.
