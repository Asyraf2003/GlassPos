# C5 Detail cancellation and restore

Issue #13; branch `feat/13-detail-cancellation-restore`; accepted base aefd26f5 (C4 PR #12).

Shared cashier/admin Detail now exposes explicit cancellation and restore via ordinary CSRF forms. Server preview reuses cancellation eligibility and current immutable revision; shows zero active cancellation effects and stock quantity, or the source revision amount and fresh stock requirement for restore. Restore selects the last accepted revision. Existing C1/C2 handlers retain authoritative validation, locks, idempotency, audit and atomic effects. No JavaScript mutation engine or Auto.

Read-only view access uses existing actor/capability ports and cashier date guard, avoiding mutation audit on GET. Paid notes explain existing selected Detail Refund; external and unresolved history are explicitly blocked. Existing history now renders actor identity alongside reason/time. Retry preserves observed base and command key together, marks stale forms disabled, and never silently rebases. Reasons are escaped by Blade.

Proof: initial two presentation RED failures; final eight related suites 26 tests / 284 assertions pass. Actual rendered HTML form fields drive create -> cancel -> reload -> restore -> new revision and stock, with stale JSON rejection and stale HTML retry identity preserved. Paid/external messages, admin broader date read versus disabled transaction capability, and adjacent cancellation/restore/refund/history are covered. PHPStan, Pint, contract audit and diff check pass. Capability test uses the existing deactivate port (direct SQL would bypass its request-local cache); DOM parser verifies element type explicitly.

Author diff review: no new mutation endpoint, source writer, money calculation, transaction or locking change. View helpers remain under 100 lines. New history keys are additive. GET preview is advisory; under-lock handlers remain authoritative. No independent approval claimed. Browser/full verification belongs to C6, and physical cashier/device acceptance is not claimed.

Next: PR/check/green merge, sync main and immediately run C6 integrated/browser and whole repository verification. C1-C4 stay closed absent actual regression.
