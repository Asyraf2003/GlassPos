# Admin Mobile PWA and Supplier Reminder UX

Date: 2026-09-28
Issue: https://github.com/Asyraf2003/GlassPos/issues/35

## Facts and scope

Admin handset renders mobile_supplier_hub; desktop renders the overview. The existing cashier install affordance and manifest launch /cashier/dashboard in fullscreen. Generic subscriptions store the authenticated user_id and a unique endpoint hash, with expiration on delivery failure. Supplier sender previously selected all active subscriptions globally.

Only admin handset install/notification UX and supplier recipient eligibility are changed. Supplier payable balances, report semantics, selection H+5/overdue, payload deep link, command options/limits and cron setup remain unchanged. No finance mutation, schema or dependency additions.

## Decision

Use a separate admin-manifest.webmanifest with id/start_url /admin/dashboard and standalone display, selected by the handset admin view through a layout section. Preserve the original cashier manifest and install script. Reuse canonical /service-worker.js and existing AppPushNotifications enable/disable helper.

The handset card shows install capability and supplier-reminder opt-in. Permission is requested only from toggle action. Existing granted permission skips a prompt, denied permission is not retried, unsupported/missing configuration disables the control. Installation is not required for push where the browser supports it. No audio assets or silent notification option are added.

Extend the generic helper with getState: read browser PushSubscription and check its active registration for the current account via an authenticated, read-only POST status endpoint. This prevents an old/expired/other-account browser subscription from appearing active. Reads never revive expired subscriptions; explicit enable reuses and saves an existing subscription. Failed new subscription persistence rolls back the browser subscription; OFF deletes the backend row before browser unsubscribe and verifies the latter succeeded.

Generic subscription reader accepts an optional role filter. Only supplier sender requests admin; role is checked from actor_accesses before applying the existing limit. Generic customer push behavior remains unchanged. No hardcoded admin ID, allowlist or separate notification persistence.

## Proof plan and limitations

PHP tests: admin handset/desktop, cashier manifest/affordance regression, admin persistence and delete/status ownership, same-endpoint upsert, role changes and expiration, generic reader/customer sender regression, canonical deep link.

Node tests: permission/action boundaries, browser subscription state, stale backend state, denied/dismissed/unsupported/missing-key handling, rollback, delete ordering and unsubscribe failure; canonical worker system notification and click destination.

Browser: handset light/dark, install events, ON/reload/OFF through real local endpoints with simulated native permission/PushManager; real canonical worker registration; emulated standalone launch and unsupported browser. Physical OS install, push delivery and sound require deployment/device validation and are not inferred from mocks.

Workflow: issue/branch, implementation, tests/audits, browser evidence, review, PR/merge, handoff. No build/deployment package or production cron work.
