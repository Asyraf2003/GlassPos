# 027 - Admin invoice creation bypasses transaction-entry gate

## Status

Fixed and locally verified.

## Keparahan

High.

## Ringkasan

Route pembuatan supplier invoice admin melewati gate `transaction.entry`.

Endpoint `POST /admin/procurement/supplier-invoices` berada di group middleware:

- `web`
- `auth`
- `admin.page`
- `app.shell`

Namun route tersebut sebelumnya tidak memakai `transaction.entry` atau check capability setara.

`admin.page` hanya membuktikan actor memiliki role admin. Gate itu tidak membuktikan admin boleh melakukan input transaksi. Admin dengan transaction-entry capability inactive tetap dapat membuat supplier invoice melalui route ini.

## Jalur rentan

Admin login
-> submit `POST /admin/procurement/supplier-invoices`
-> route melewati `auth` dan `admin.page`
-> tidak melewati `transaction.entry`
-> request mencapai `StoreSupplierInvoiceController`
-> controller memanggil `CreateSupplierInvoiceFlowHandler`
-> flow membuat supplier invoice
-> flow membuat supplier payment sebesar invoice total
-> jika `auto_receive` aktif, flow membuat supplier receipt
-> flow membuat inventory movement `stock_in`
-> inventory projection berubah tanpa capability input transaksi

## Root cause

Route admin mutation untuk supplier invoice ditempatkan di bawah admin-page access saja.

Admin-page access bukan authorization boundary untuk transaksi. Untuk mutation bernilai tinggi seperti supplier invoice, supplier payment, receipt, dan stock movement, route harus melewati `transaction.entry`.

## Dampak

Bypass ini memengaruhi integritas finansial dan inventory.

Actor admin yang seharusnya tidak boleh input transaksi tetap dapat:

- membuat supplier invoice
- membuat payable/supplier payment pending
- membuat receipt bila auto-receive aktif
- membuat stock-in inventory movement
- mengubah inventory projection

## Patch summary

`routes/web/admin_procurement.php` diubah pada route:

`Route::post('/admin/procurement/supplier-invoices', StoreSupplierInvoiceController::class)`

dengan menambahkan:

`->middleware('transaction.entry')`

Patch commit yang dilaporkan:

`dbdb5d8 - Protect admin supplier invoice store route with transaction entry gate`

## Verification

Local verification from #027 remediation:

Source reality intake:

- `routes/web/admin_procurement.php` initially contradicted this document's previous `Patched` status.
- The store route still lacked `transaction.entry` before the local #027 remediation patch.
- Therefore this issue was treated as source-not-patched and RED proof was required before patching.

RED proof:

- `php -l tests/Feature/Procurement/AdminSupplierInvoiceTransactionCapabilityFeatureTest.php`
  - PASS: no syntax errors.
- `php artisan test tests/Feature/Procurement/AdminSupplierInvoiceTransactionCapabilityFeatureTest.php`
  - RED: 1 failed, 2 passed, 16 assertions.
  - Failure: admin without transaction capability expected `403`, actual `302`.
  - Read procurement pages still passed.
  - Active authorized admin create still passed.

Patch proof:

- `routes/web/admin_procurement.php`
  - Added `->middleware('transaction.entry')` only to `admin.procurement.supplier-invoices.store`.
- `php -l routes/web/admin_procurement.php`
  - PASS: no syntax errors.

GREEN proof:

- `php artisan test tests/Feature/Procurement/AdminSupplierInvoiceTransactionCapabilityFeatureTest.php`
  - PASS: 3 tests, 27 assertions.
  - Proves admin without transaction capability is rejected from supplier invoice creation.
  - Proves no supplier, supplier invoice, invoice line, invoice version, supplier payment, supplier receipt, receipt line, inventory movement, product inventory, or product costing row is created on denied request.
  - Proves admin read pages remain available.
  - Proves active authorized admin can still create supplier invoice and stock-in inventory movement.

Focused proof:

- `php artisan route:list --path=admin/procurement/supplier-invoices -v`
  - Proves `admin.procurement.supplier-invoices.store` has `transaction.entry`.
- Focused procurement regression:
  - `php artisan test tests/Feature/Procurement/AdminSupplierInvoiceTransactionCapabilityFeatureTest.php tests/Feature/Procurement/CreateSupplierInvoiceFeatureTest.php tests/Feature/Procurement/CreateSupplierInvoicePageFeatureTest.php tests/Feature/Procurement/ProcurementInvoiceIndexPageFeatureTest.php tests/Feature/Procurement/ProcurementInvoiceTableDataAccessFeatureTest.php`
  - PASS: 17 tests, 166 assertions.

## Verification gap

Not globally verified:

- Full `make verify` was not rerun for #027 closure.
- Browser/manual QA was not run.
- Adjacent procurement mutation routes remain outside this #027 patch scope unless separately proven and scoped:
  - `admin.procurement.supplier-invoices.receive`
  - `admin.procurement.supplier-invoices.payments.store`
  - `admin.procurement.supplier-receipts.reverse.store`
  - `admin.procurement.supplier-invoices.void`
  - `admin.procurement.supplier-payments.reverse.store`
  - `admin.procurement.supplier-payments.proof.store`
  - `admin.procurement.supplier-invoices.update`
- This #027 closure covers supplier invoice creation only.

## Update - 2026-05-10 local verification

The previous `Patched, with verification gap` status is promoted to `Fixed and locally verified` based on RED/GREEN targeted proof, route-list middleware proof, and focused procurement regression proof.

The implemented boundary remains intentionally narrow:

- Gate `admin.procurement.supplier-invoices.store` with `transaction.entry`.
- Keep procurement read routes available to authorized admin users.
- Do not silently include adjacent procurement mutation routes in #027.

## Relations

Related to #020.

#020 covers admin note mutation routes bypassing the transaction-entry capability gate.

#027 covers admin procurement supplier-invoice creation bypassing the same transaction-entry capability gate.

Related to #016 as part of identity/access capability authorization.

#016 covers unauthenticated admin capability toggle endpoints, while #027 covers a privileged business mutation route missing the capability gate.

Terkait dengan #023 hanya secara tidak langsung melalui surface procurement proof/storage, tetapi root cause berbeda. #027 adalah authorization pada mutasi supplier invoice, bukan exposure public storage.
