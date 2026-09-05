# 0017 - Admin Index Standardization Campaign

## Status

Complete. Implemented and verified on 2026-09-05.

## Reference

`GET /admin/products` and `GET /admin/products/table` are the canonical visual and interaction reference. Product-specific catalog fields, filters, relevance weights, inventory projections, and write workflows are not reusable domain rules.

## Target

Make the following admin lists feel like one coherent system while preserving their module-owned read models and actions:

- Jasa (`admin.services.index`)
- Paket Service (`admin.service-product-templates.index`)
- Pemasok (`admin.suppliers.index`)
- Pengadaan (`admin.procurement.supplier-invoices.index`)
- Nota Pelanggan (`admin.notes.index`)
- Data Karyawan (`admin.employees.index`)
- Gaji Karyawan (`admin.payrolls.index`)
- Hutang Karyawan (`admin.employee-debts.index`)
- Biaya Operasional (`admin.expenses.index`)
- Audit Log (`admin.audit-logs.index`)

## Common Normal-Index Contract

- Header context is left aligned; search, optional Filter, and optional primary Create/Add action are ordered at the right.
- Tables use the Product density, responsive horizontal overflow, sortable meaningful headers, and one rightmost row-action location.
- Search is server-side, begins at two useful characters, uses a short debounce, aborts superseded requests, retains a stale-response guard, and resets the authoritative query when the input drops below two characters.
- Search, filters, explicit sort, direction, and page are URL-addressable. Browser popstate restores the controls and reloads the authoritative server result.
- Multi-field identity search uses module-owned relevance ordering. An explicit sortable header overrides relevance.
- Filters use an explicit request whitelist, execute before pagination, survive sorting and navigation, and have a consistent reset lifecycle.
- Sorting is server-side, executes before pagination, uses a whitelist, and ends in a deterministic tie-breaker representing real chronology or stable identity rather than UUID lexical order as business chronology.
- Normal indexes use 10 rows per page.
- Loading, empty, fetch-error, summary, and pagination states are consistent. Summary text follows `Menampilkan X sampai Y dari Z ...`.
- Blade and JavaScript render and coordinate state only. Domain truth and authoritative filtering remain in the application/read-adapter boundary.

## Architecture Contract

Each module owns its Request, criteria DTO, application handler, outbound reader port, database adapter, and presenter/payload. Existing suitable abstractions are extended rather than replaced. Jasa and Paket Service receive new JSON table read contracts because their current controllers directly load unpaginated database page data.

Small shared presentation/state helpers are allowed only for behavior that is identical across modules. This campaign must not introduce a universal domain query, common module DTO, or mega-table framework.

## Baseline Matrix

| Module | Current list | Search/filter | Ordering/pagination | Confirmed gap |
| --- | --- | --- | --- | --- |
| Jasa | server-rendered DB page data | none | active then name; all rows | no table boundary or standard interactions |
| Paket Service | server-rendered DB page data | none | product, line order, ID; all rows | no table boundary or standard interactions |
| Pemasok | JSON read side | supplier name; no filter | name; 10/page | lifecycle, relevance, summary, proof gaps |
| Pengadaan | JSON read side | invoice/supplier plus payment/date filters | shipment newest; 10/page | lifecycle, relevance, summary gaps |
| Nota Pelanggan | JSON read side | text plus date/line-status filters | created newest plus note ID; 10/page | abort and common state/summary gaps |
| Data Karyawan | JSON read side | employee identity/employment text | name; 10/page | missing deterministic fallback and browser lifecycle |
| Gaji Karyawan | JSON read side | employee/payroll text | disbursement newest; 10/page | incomplete tie-break and browser lifecycle |
| Hutang Karyawan | grouped JSON read side | employee name | latest activity; 10/page | one-character search and no stale/abort/popstate lifecycle |
| Biaya Operasional | JSON read side | text plus category/date filters | expense newest with stable fallback; 10/page | lifecycle, relevance, summary gaps |
| Audit Log | unified server-rendered legacy/v2 reader | broad GET search | newest-first; 20/page | inline JSON, no show route, no ranked search/sort contract |

## Module-Owned Semantics

- Jasa preserves active/inactive lifecycle and service price semantics.
- Paket Service preserves service/product pairing, up-to-three product lines, activation constraints, and configured line order.
- Pemasok preserves projection-derived invoice/payable summaries; there is no invented supplier-create action.
- Pengadaan preserves invoice revision, receipt, payment, void, attachment, snapshot, and action-availability contracts.
- Nota Pelanggan preserves current date/status defaults, historical projections, money sorting, and payment truth.
- Employee Finance preserves salary, reversal, grouped debt, and employee-level action semantics.
- Biaya Operasional preserves category/date rules and deletion policy.

## Audit Log Variant

- Page size remains exactly 20.
- Default ordering remains deterministic newest-first across legacy `audit_logs` and v2 `audit_events`.
- Safe meaningful headers may be explicitly sorted server-side; chronology remains the default.
- Search relevance prioritizes event, entity identifier, actor identifier, source, bounded context, and entity type before reason and role. Context JSON matching is a broad last-priority fallback.
- The index never renders full context JSON. A compact Detail/Show action links to `admin.audit-logs.show`.
- The detail surface resolves a source plus identifier, returns the normal not-found response for an unknown entry, and renders escaped, readable pretty JSON without executable HTML.

## Test Strategy

Every module is executed serially:

1. inspect and characterize the current route/read side/view/tests;
2. add behavior tests for the confirmed contract gap and prove RED;
3. implement the smallest correct module-owned read-side and common-UI changes;
4. prove the same scenarios GREEN before moving to the next module.

Applicable tests cover access, search and reset lifecycle, filter, default deterministic ordering, explicit sorting before pagination, URL/query preservation, empty results, and row-action links. Audit additionally covers fixed 20/page, ranked multi-field search, newest-first behavior, compact index, detail route, escaped pretty context, and missing entries.

Source-string tests are not substitutes for runtime behavior tests. JavaScript state contracts may receive focused unit/contract coverage, but final acceptance uses Chromium.

## Browser Acceptance

Each target must be exercised at desktop and mobile widths for header layout, horizontal table behavior, search typing, rapid typing/backspace, applicable filters, sortable headers, pagination, row actions, URL restoration/popstate, and absence of runtime JavaScript errors.

## Scope Out

- Finance/payment behavior changes.
- Inventory truth changes.
- ProductCatalog domain changes.
- Production deployment, CDN/R2 upload, or Cloudflare changes.
- Production migration or destructive schema work.

## Final Gates

- Focused target feature suites.
- Chromium desktop/mobile acceptance for all ten indexes.
- JavaScript syntax checks.
- Pint.
- `audit-contract`.
- `audit-hex`.
- `git diff --check`.
- `make verify`.
- Final diff review, one campaign commit, and fast-forward push only after every gate is green.

## Proof Ledger

| Slice | RED | GREEN | Browser | Status |
| --- | --- | --- | --- | --- |
| Blueprint/baseline | n/a | repository and route/read-side audit | pending | complete |
| Jasa | missing JSON table route | ServiceCatalog suite: 9 tests / 52 assertions | Chromium desktop/mobile | complete |
| Paket Service | missing JSON table route | ServiceProductTemplate suite: 17 tests / 132 assertions | Chromium desktop/mobile | complete |
| Pemasok | outstanding filter absent | focused supplier suite GREEN | Chromium desktop/mobile | complete |
| Pengadaan | broad newer supplier match outranked exact invoice | focused invoice suite GREEN | Chromium desktop/mobile | complete |
| Nota Pelanggan | broad newer customer match outranked exact nota | focused admin-note suite GREEN | Chromium desktop/mobile | complete |
| Data Karyawan | filters/relevance absent | focused Employee table suite GREEN | Chromium desktop/mobile | complete |
| Gaji Karyawan | filters/relevance absent | focused Payroll table suite GREEN | Chromium desktop/mobile | complete |
| Hutang Karyawan | grouped status filter absent | focused Debt table suite GREEN | Chromium desktop/mobile | complete |
| Biaya Operasional | newer broad description outranked exact category | Expense query: 4 tests / 15 assertions | Chromium desktop/mobile | complete |
| Audit Log | table/detail routes absent and context inline | AuditLog suite: 25 tests / 136 assertions | Chromium desktop/mobile | complete |
| Final gates | n/a | focused campaign: 140 tests / 724 assertions; full verify: 1,634 tests / 10,354 assertions | 20 viewport-route combinations GREEN | complete |
