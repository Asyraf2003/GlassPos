# Security ADR-0019 Access Boundary Workflow
## Status
Canonical Workflow.

This file is not an implementation patch and does not mark any error log as fixed.

## Source
- `docs/03_blueprints/security/adr-0019-access-boundary.md`


## Characterization Test Matrix

Tests must be written before production patch for each slice.

### Slice 1 Admin Note Mutation Capability

Goal:

- prove admin role is not enough for transaction-sensitive note mutation

Required tests:

1. admin without transaction capability cannot POST admin note payment
2. admin with transaction capability can POST admin note payment when domain validation passes
3. admin without transaction capability cannot POST admin note refund
4. admin with transaction capability can reach refund use case when domain validation passes
5. admin without transaction capability cannot PATCH admin note workspace update
6. admin with transaction capability can reach workspace update when domain validation passes
7. admin can GET admin note detail without transaction capability
8. admin metadata-only correction does not require transaction capability, if metadata-only route exists

Expected status:

- denied mutation returns 403
- allowed mutation follows existing route behavior
- read page remains accessible to admin

### Slice 2 Cashier Date Window

Goal:

- prove cashier cannot read or mutate out-of-window note

Required tests:

1. cashier can read today note
2. cashier can read yesterday note
3. cashier cannot read note older than yesterday
4. cashier cannot post payment for older-than-yesterday note
5. cashier cannot post refund for older-than-yesterday note
6. cashier cannot open workspace edit for older-than-yesterday note
7. cashier cannot patch workspace update for older-than-yesterday note
8. out-of-window denial returns 403

Expected status:

- out-of-window read and mutation return 403

### Slice 3 Supplier Invoice And Proof Gate

Goal:

- prove procurement sensitive flows require transaction capability

Required tests:

1. admin without transaction capability cannot create supplier invoice
2. admin with transaction capability can create supplier invoice when domain validation passes
3. admin without transaction capability cannot update supplier invoice
4. admin without transaction capability cannot void supplier invoice
5. admin without transaction capability cannot receive supplier invoice
6. admin without transaction capability cannot record supplier payment
7. admin without transaction capability cannot upload supplier payment proof
8. admin without transaction capability cannot download or serve supplier payment proof attachment
9. admin read-only supplier invoice list/detail remains accessible without transaction capability

Expected status:

- denied sensitive action returns 403
- read-only action remains accessible to admin

### Slice 4 Capability Toggle Authorization And Audit

Goal:

- prove capability toggle endpoint is protected and audited

Required tests:

1. guest cannot enable capability
2. guest cannot disable capability
3. kasir cannot enable capability
4. kasir cannot disable capability
5. admin can enable capability
6. admin can disable capability
7. enable writes audit record
8. disable writes audit record
9. audit contains actor, target, old state, new state, timestamp

Expected status:

- guest redirects or returns auth-required according to existing auth pattern
- kasir returns 403
- admin succeeds
- audit exists

### Slice 5 Cashier Count Disclosure

Goal:

- prove cashier create page no longer exposes global note count

Required tests:

1. cashier create page does not include global note count
2. default customer naming does not require global note count
3. if any count remains, it is scoped to cashier window
4. admin pages may keep global count only where authorized

Expected status:

- cashier page renders without global count leak

## Implementation Order

The safest order:

1. Inventory exact classes used by affected routes
2. Add tests for Slice 1 admin note mutation capability
3. Patch minimal policy enforcement for admin note mutation
4. Run targeted tests for Slice 1
5. Add tests for Slice 2 cashier date window
6. Patch cashier note access policy
7. Run targeted tests for Slice 2
8. Add tests for Slice 3 supplier invoice and proof gate
9. Patch procurement access policy
10. Run targeted tests for Slice 3
11. Add tests for Slice 4 capability toggle audit
12. Patch capability toggle authorization or audit if missing
13. Run targeted tests for Slice 4
14. Add tests for Slice 5 cashier count disclosure
15. Patch cashier create page or data provider
16. Run targeted tests for Slice 5
17. Run ADR-0019 blast-radius test suite
18. Update docs/error_log only after proof
19. Commit only after owner reviews diff and proof

## CLI Workflow

Workflow is flexible because each error fix may reveal a new failure.

However, these rules are fixed:

1. Start every slice with git status.
2. Read relevant ADR and error log before editing.
3. Read route and controller source before editing.
4. Add red characterization test first.
5. Run targeted test and confirm it fails for the expected reason.
6. Patch the smallest safe boundary.
7. Run the targeted test again.
8. Run local blast-radius tests for affected cluster.
9. Show git diff.
10. Update docs/error_log only with proof.
11. Commit only after owner approval.

## Required Commands For Execution Sessions

### Start Session Snapshot

Run before any implementation slice:

    git status --short
    git rev-parse --abbrev-ref HEAD
    git log --oneline -5

### Route Snapshot

Run when route behavior is involved:

    php artisan route:list | grep -Ei "note|refund|capabil|supplier|invoice|cashier|proof|attachment|payment" || true

### ADR And Error Log Snapshot

Run before selecting a slice:

    sed -n '1,260p' docs/02_architecture/adr/0019-note-access-boundary-cashier-date-window-and-transaction-capability-enforcement.md
    sed -n '1,220p' docs/04_lifecycle/error_log/009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
    sed -n '1,220p' docs/04_lifecycle/error_log/015-refunded-notes-expose-edit-workspace.md
    sed -n '1,220p' docs/04_lifecycle/error_log/016-unauthenticated-admin-capability-toggle-endpoints.md
    sed -n '1,220p' docs/04_lifecycle/error_log/018-refunded-notes-bypass-cashier-closed-note-guards.md
    sed -n '1,220p' docs/04_lifecycle/error_log/019-cashiers-can-list-historical-closed-notes-by-date.md
    sed -n '1,220p' docs/04_lifecycle/error_log/020-admin-note-actions-bypass-transaction-capability.md
    sed -n '1,220p' docs/04_lifecycle/error_log/022-cashier-refund-route-bypasses-note-access-guard.md
    sed -n '1,220p' docs/04_lifecycle/error_log/027-admin-invoice-creation-bypasses-transaction-entry-gate.md
    sed -n '1,220p' docs/04_lifecycle/error_log/029-cashier-create-page-leaks-total-note-count.md

### Exact Class Discovery

Run before writing tests:

    grep -RIn "admin.notes.*payments\|admin.notes.*refunds\|admin.notes.*workspace\|cashier.notes.*payments\|cashier.notes.*refunds\|cashier.notes.*workspace\|admin-transaction-capability\|supplier-invoices\|supplier-payment-proof" routes app tests 2>/dev/null || true

### Test Run Pattern

Run targeted tests first.

Then run blast-radius tests.

Suggested targeted command per slice must be created after exact test files are chosen.

## Next Blueprint After This

After ADR-0019 blueprint is accepted, continue with:

1. ADR-0020 public surface, output context, unsafe URL, storage, attachment serving blueprint
2. Payment concurrency and over-allocation blueprint
3. Seeder legacy marker and future seeder reset blueprint

---

## Related Documents

- Blueprint: docs/03_blueprints/security/adr-0019-access-boundary.md
- DoD: docs/03_blueprints/security/adr-0019-access-boundary-dod.md
