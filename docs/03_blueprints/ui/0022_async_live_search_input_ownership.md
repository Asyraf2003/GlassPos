# Async Live Search Input Ownership

## Scope and baseline

Issue: https://github.com/Asyraf2003/GlassPos/issues/74

Baseline: latest main 821d1d1d4a1cce27737e91bcac596d17b6c76232, clean tree, fetched before creating fix/async-live-search-input-ownership. Closed #72/#73 remain outside this issue.

Target: all application-owned asynchronous list searches and form lookups retain exactly the user's live text, including spaces, focus and selection. Latest query wins even when abort loses a race or responses arrive out of order.

## Actual root cause

Supplier, procurement, expense, note and audit list loaders combined result rendering with control hydration. Several pages updated query state and request identity only when debounce fired. A pending old response arriving inside the debounce interval still passed the counter check, rendered stale rows, rewrote the URL and assigned the search field from older state. Product already invalidated on input but still hydrated the input on response, including trimming the user's trailing spaces. Other lists avoided assigning the input on response but still accepted obsolete results during debounce.

Cashier product search relied on abort at request start without an identity guard. Supplier/PT form lookup advanced its counter only at fetch time. Workspace service lookup could return an empty stale result to an unconditional renderer; automatic service resolution could clear the editable query and move focus after an await. Workspace product/package lookups already invalidated tokens immediately but did not cancel network work.

## Decision

Use the small shared LiveSearch gate, not a table framework. The gate owns generation, AbortController and cancellable scheduling. Each module continues to own its query semantics, filters, row rendering, pagination and URL parameters.

Input events invalidate and abort immediately, then synchronously update module query state, then debounce. Every async success/error renderer checks current identity. Request start also cancels any queued debounce so submit/filter/pagination/navigation cannot run a superseded search afterward.

Control hydration defaults to leaving search alone. Only initial hydration or explicit popstate restoration opts into assigning the search value. Explicit user selection/reset remains allowed. Automatic service resolution updates hidden identity/pricing without clearing the live query, hiding its stage or moving focus. Boot hydration skips form fields edited while draft loading was pending.

Canonical live queries use at least two trimmed characters. One character issues no request, including submit. An unchanged empty default does not request again; clear of an active query restores the existing default state with a request containing no search parameter. The separate invoice-number uniqueness validator remains a documented business exception: any nonempty number, including one character, must be validated. Existing local-only pickers and server-rendered GET reports remain unchanged.

Keep existing debounce/history behavior: initial load/popstate replaces URL; accepted search/actions use existing push behavior. There is no URL mutation from an obsolete response or from each input event. Expense categories had no URL lifecycle and do not gain one.

The search input remains outside refreshed row/result containers. No DOM recreation, focus restoration or selection rewriting is necessary. An existing sidebar callback required an active menu item on cashier product-search/workspace-create; these routes now use the existing Dashboard item.

## Boundaries

No backend query semantics, database schema, payment/refund/cancel, supplier bank/payment, R2/media, quantities/versioning, or UI redesign. Metadata hydration in procurement create/edit only updates a selected card when its current product ID still matches; it never touches the shared lookup field.

## Workflow and proof

1. Issue and latest-main branch.
2. Audit app-owned async transports, local pickers and server-rendered report filters.
3. Reproduce debounce-window failure with production page scripts and delayed fetch promises.
4. Implement the gate and page/lookup integrations.
5. Node regressions A-G across all thirteen async indexes; independent gate/hydration tests; transport inventory contract.
6. Real Blade page export through authenticated Laravel test routes, then Chromium desktop/mobile typing with delayed responses that deliberately ignore abort.
7. Focused PHP, existing frontend/browser pickers, PHPStan, contract audits, full tests and make verify.
8. Review diff, commit, push, PR; merge only after verified gates, then clean/synchronize local and remote main.

Commands run from the repository root:

    make test-frontend
    make test-browser-live-search
    make audit-contract
    make verify

PHP tests share a test database and must run sequentially. Chromium proof uses authenticated test-rendered pages, production DOM/scripts and controlled API response timing, never production data writes. Chromium Snap uses its accessible common profile directory; CHROMIUM_BIN and CHROMIUM_PROFILE_ROOT can override the runner defaults.

## Inventory and final gate ledger

The executable inventory is tests/Frontend/support/search-inventory.json. Its contract requires an audit when an app-owned request surface or call count changes. The detailed classification and final gate proof live in docs/05_audits/0001_async_live_search_audit.md.
