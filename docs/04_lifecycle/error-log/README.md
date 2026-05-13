# error-log

29 individual bug dan security findings sistem Hyperpos.

## Index

| # | File | Topik |
|---|---|---|
| 001 | `001-refunds-counted-as-paid-in-note-totals.md` | Refund ikut terhitung sebagai paid |
| 002 | `002-seeder-introduces-predictable-admin-credentials.md` | Seeder buat kredensial admin yang predictable |
| 003 | `003-refunded-revised-notes-are-misclassified-as-underpaid.md` | Note revisi+refund salah diklasifikasi underpaid |
| 004 | `004-refunded-work-items-survive-revisions-and-inflate-stock.md` | Work item refund bertahan di revisi, inflasi stok |
| 005 | `005-note-revision-silently-drops-overpaid-allocations.md` | Revisi diam-diam hilangkan alokasi overpaid |
| 006 | `006-client-controlled-price-basis-bypasses-minimum-price-checks.md` | Client bisa bypass minimum price via price basis |
| 007 | `007-admin-note-edit-page-exposes-stored-xss.md` | Stored XSS di halaman edit note admin |
| 008 | `008-legacy-paid-notes-can-be-paid-again.md` | Note lama yang sudah paid bisa dibayar lagi |
| 009 | `009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md` | Kasir bisa tulis ulang note closed via workspace |
| 010 | `010-revision-reallocation-can-lose-concurrent-payments.md` | Reallokasi revisi bisa hilangkan concurrent payment |
| 011 | `011-cashier-revision-path-mutates-settled-note-state.md` | Path revisi kasir mutasi settled note |
| 012 | `012-canceled-note-rows-re-enter-payment-flows.md` | Row canceled masuk kembali ke payment flow |
| 013 | `013-forged-row-refund-can-auto-finalize-unpaid-notes.md` | Forged row refund bisa auto-finalize note unpaid |
| 014 | `014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md` | Refund endpoint bisa cancel row open/unpaid |
| 015 | `015-refunded-notes-expose-edit-workspace.md` | Note refunded masih expose edit workspace |
| 016 | `016-unauthenticated-admin-capability-toggle-endpoints.md` | Capability toggle endpoint tidak terautentikasi |
| 017 | `017-workspace-edit-payments-ignore-existing-note-payments.md` | Workspace edit abaikan payment yang sudah ada |
| 018 | `018-refunded-notes-bypass-cashier-closed-note-guards.md` | Note refunded bypass guard closed note kasir |
| 019 | `019-cashiers-can-list-historical-closed-notes-by-date.md` | Kasir bisa list note closed historical by date |
| 020 | `020-admin-note-actions-bypass-transaction-capability.md` | Aksi admin bypass transaction capability check |
| 021 | `021-refunds-can-be-recorded-on-open-notes.md` | Refund bisa direkam di note yang masih open |
| 022 | `022-cashier-refund-route-bypasses-note-access-guard.md` | Route refund kasir bypass access guard |
| 023 | `023-public-helper-can-expose-private-storage.md` | Public helper bisa expose private storage |
| 024 | `024-reflected-xss-in-expense-create-json-config.md` | Reflected XSS di JSON config expense create |
| 025 | `025-reflected-javascript-url-in-product-return-link.md` | Reflected JS URL di product return link |
| 026 | `026-concurrent-note-payments-can-over-allocate-balances.md` | Concurrent payment bisa over-alokasi balance |
| 027 | `027-admin-invoice-creation-bypasses-transaction-entry-gate.md` | Invoice creation admin bypass transaction gate |
| 028 | `028-di-fix-exposes-unsafe-proof-attachment-content-type.md` | DI fix expose unsafe content-type attachment |
| 029 | `029-cashier-create-page-leaks-total-note-count.md` | Halaman create kasir leak total note count |

## Aturan

- Status file hanya boleh diupdate setelah ada proof.
- Allowed status: Reported, Planned, Patched with verification gap, Fixed with proof, Deferred with owner acceptance.
- Blueprint remediasi ada di `docs/03-blueprints/error-log-remediation/`.
