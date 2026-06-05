# error-log

34 individual bug and security findings in the Hyperpos system.

## Kesimpulan Remediasi Saat Ini

Per 2026-06-05, remediation batch terakhir menutup 0030 sampai 0034 dengan status `Strict Fixed`.

Kesimpulan sistem:

- 0031 menutup duplicate submit create transaction workspace dengan server-generated idempotency key dan DB-backed idempotency.
- 0032 menutup Excel formula injection pada inventory stock value export dengan explicit string cells untuk product text.
- 0033 menutup login brute-force gap pada web dan mobile login dengan explicit Laravel rate limiter.
- 0034 menutup unbounded product lookup dan N+1 inventory read dengan dedicated hexagonal lookup port dan bounded database adapter.
- 0030 menutup locked dependency security advisories melalui controlled dependency update, clean `composer audit --locked`, platform check, dan full verification gate.
- Patch 0034 tidak menjadikan Laravel API sebagai fokus produk; mobile endpoint hanya dirapikan sebagai boundary sistem yang sudah ada.
- Jalur 0034 disiplin hexagonal: controller/handler tidak melakukan DB shortcut, application bergantung ke port, persistence berada di out adapter.
- Query lookup 0034 memakai SQL umum melalui query builder (`LEFT JOIN`, `COALESCE`, `LIKE`, `LIMIT`) dan tidak menambah syntax MySQL-only baru.
- Catatan migrasi PostgreSQL: perilaku case-sensitivity `LIKE` tetap perlu diputuskan eksplisit saat migrasi Go/PostgreSQL.

Pending yang masih terpisah:

- Generic `ProductReaderPort::search()` masih layak diaudit jika nanti dipakai consumer lookup besar di luar port khusus 0034.

## Index

| # | File | Topic |
|---|---|---|
| 001 | `001-refunds-counted-as-paid-in-note-totals.md` | Refunds counted as paid |
| 002 | `002-seeder-introduces-predictable-admin-credentials.md` | Seeder creates predictable admin credentials |
| 003 | `003-refunded-revised-notes-are-misclassified-as-underpaid.md` | Revised + refunded notes are misclassified as underpaid |
| 004 | `004-refunded-work-items-survive-revisions-and-inflate-stock.md` | Refunded work items survive revisions and inflate stock |
| 005 | `005-note-revision-silently-drops-overpaid-allocations.md` | Revision silently drops overpaid allocations |
| 006 | `006-client-controlled-price-basis-bypasses-minimum-price-checks.md` | Client can bypass minimum price checks via price basis |
| 007 | `007-admin-note-edit-page-exposes-stored-xss.md` | Stored XSS on the admin note edit page |
| 008 | `008-legacy-paid-notes-can-be-paid-again.md` | Old paid notes can be paid again |
| 009 | `009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md` | Cashiers can rewrite closed notes via workspace update |
| 010 | `010-revision-reallocation-can-lose-concurrent-payments.md` | Revision reallocation can lose concurrent payments |
| 011 | `011-cashier-revision-path-mutates-settled-note-state.md` | Cashier revision path mutates settled note state |
| 012 | `012-canceled-note-rows-re-enter-payment-flows.md` | Canceled note rows re-enter payment flows |
| 013 | `013-forged-row-refund-can-auto-finalize-unpaid-notes.md` | Forged row refund can auto-finalize unpaid notes |
| 014 | `014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md` | Refund endpoint can cancel open or unpaid note rows |
| 015 | `015-refunded-notes-expose-edit-workspace.md` | Refunded notes still expose the edit workspace |
| 016 | `016-unauthenticated-admin-capability-toggle-endpoints.md` | Capability toggle endpoint is unauthenticated |
| 017 | `017-workspace-edit-payments-ignore-existing-note-payments.md` | Workspace edit ignores existing note payments |
| 018 | `018-refunded-notes-bypass-cashier-closed-note-guards.md` | Refunded notes bypass cashier closed-note guards |
| 019 | `019-cashiers-can-list-historical-closed-notes-by-date.md` | Cashiers can list historical closed notes by date |
| 020 | `020-admin-note-actions-bypass-transaction-capability.md` | Admin note actions bypass the transaction capability check |
| 021 | `021-refunds-can-be-recorded-on-open-notes.md` | Refunds can be recorded on open notes |
| 022 | `022-cashier-refund-route-bypasses-note-access-guard.md` | Cashier refund route bypasses the note access guard |
| 023 | `023-public-helper-can-expose-private-storage.md` | Public helper can expose private storage |
| 024 | `024-reflected-xss-in-expense-create-json-config.md` | Reflected XSS in the expense-create JSON config |
| 025 | `025-reflected-javascript-url-in-product-return-link.md` | Reflected JavaScript URL in the product return link |
| 026 | `026-concurrent-note-payments-can-over-allocate-balances.md` | Concurrent note payments can over-allocate balances |
| 027 | `027-admin-invoice-creation-bypasses-transaction-entry-gate.md` | Admin invoice creation bypasses the transaction-entry gate |
| 028 | `028-di-fix-exposes-unsafe-proof-attachment-content-type.md` | DI fix exposes unsafe proof attachment content type |
| 029 | `029-cashier-create-page-leaks-total-note-count.md` | Cashier create page leaks total note count |
| 030 | `0030_locked_dependency_security_advisories.md` | Locked dependency security advisories remain open |
| 031 | `0031_transaction_workspace_duplicate_submit_without_idempotency_key.md` | Transaction workspace duplicate submit can create duplicate financial rows without idempotency key |
| 032 | `0032_inventory_stock_value_excel_formula_injection.md` | Inventory stock value Excel export writes product text through formula-capable cells |
| 033 | `0033_web_and_mobile_login_without_rate_limiting.md` | Web and mobile login endpoints lack explicit rate limiting |
| 034 | `0034_product_lookup_unbounded_query_and_per_row_inventory_reads.md` | Product lookup fetches unbounded product rows and performs per-row inventory reads |

## Rules

- File status may only be updated after there is proof.
- Allowed statuses: Reported, Planned, Patched with verification gap, Fixed with proof, Strict Fixed, Deferred with owner acceptance.
- The remediation blueprint lives in `docs/03_blueprints/error_log_remediation/`.
