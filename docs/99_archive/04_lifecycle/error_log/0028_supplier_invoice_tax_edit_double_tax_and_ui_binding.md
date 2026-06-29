# 0028 Supplier Invoice Tax Edit Double Tax and UI Binding

Status: RESOLVED  
Severity: P0 for edit data correctness, P1 for JS/UI draft behavior  
Area: Admin Procurement Supplier Invoices  
Detected at: 2026-06-18  
Owner: Asyraf Mubarak  

## Summary

Audit area pajak supplier invoice menemukan bahwa create/backend tax allocation sudah relatif kuat, route naming admin procurement konsisten, dan show/read/persistence sudah membawa metadata pajak.

Namun ada bug P0 pada edit/revise form: existing invoice yang sudah memiliki pajak dapat dirender kembali dengan `line_total_rupiah` final setelah pajak sebagai input subtotal. Saat user submit tanpa perubahan berarti, backend akan menghitung pajak lagi di atas nilai final tersebut. Ini membuka risiko double tax.

Ada juga bug P1 pada JavaScript tax mode: fungsi `attachTaxModeHandlers()` sudah ada, tetapi tidak dipasang ke line item di create/edit JS. Akibatnya saat user mengisi pajak per rincian, header tax tidak otomatis hide/disable/clear secara langsung.

Draft localStorage create/edit juga belum menyimpan dan restore `tax_input` header, sehingga pajak supplier header bisa hilang saat restore draft.

## Confirmed Safe Areas

- Route admin procurement supplier invoice memakai pola `admin.procurement.supplier-invoices.*`.
- Create form mengarah ke route store.
- Edit form mengarah ke route update.
- Store controller dan update controller sama-sama menerima `tax_input`.
- Create request dan update request sama-sama memakai `CreateSupplierInvoiceInputNormalizer`.
- Create/update validator sama-sama memakai `CreateSupplierInvoicePostValidator`.
- Mixed header tax + line tax sudah ditolak backend.
- Tax allocator sudah mendukung:
  - no tax
  - header fixed tax
  - header percent tax
  - line fixed tax
  - line percent tax
- Persistence invoice dan line sudah menyimpan metadata:
  - `subtotal_before_tax_rupiah`
  - `tax_input`
  - `tax_mode`
  - `tax_rate_basis_points`
  - `tax_amount_rupiah`
- Show/detail page sudah menampilkan subtotal before tax, tax amount, dan total.

## P0 Bug: Edit Existing Tax Invoice Can Double Tax

### Evidence

`EditSupplierInvoiceLineItemsViewBuilder` membangun nilai form line dari:

- `line_total_rupiah`

Padahal untuk invoice yang sudah kena pajak:

- `line_subtotal_before_tax_rupiah` = base input sebelum pajak
- `line_total_rupiah` = final setelah pajak

Jika edit form mengirim ulang `line_total_rupiah` final bersama `tax_input`, backend akan menghitung pajak ulang.

### Example

Initial saved line tax:

- base subtotal: 100000
- tax input: 11%
- tax amount: 11000
- final line total: 111000

Jika edit form memakai `line_total_rupiah = 111000` dan tetap mengirim `tax_input = 11%`, backend membaca 111000 sebagai base subtotal baru.

Potential recompute:

- base subtotal: 111000
- tax 11%: 12210
- final: 123210

Ini double tax.

### Impact

- Grand total supplier invoice salah.
- Outstanding payable salah.
- Inventory movement/unit cost bisa salah saat receipt/revision.
- Audit version menyimpan angka yang valid secara domain, tetapi salah secara intent UI.

### Expected Behavior

Edit/revise form harus mengisi input `line_total_rupiah` dari base before-tax amount:

- use `line_subtotal_before_tax_rupiah` when present and greater than 0
- fallback to `line_total_rupiah` only for legacy/no-tax data

## P1 Bug: JS Tax Mode Handler Is Not Bound

### Evidence

Create/edit JS mendefinisikan:

- `attachTaxModeHandlers(item)`

Tapi `initLineItem(item)` tidak memanggilnya.

### Impact

Saat user mengisi `lines.*.tax_input`, header tax tidak langsung hide/disable/clear.

Backend tetap menolak mixed tax, tetapi UX salah dan user baru kena error saat submit.

### Expected Behavior

`initLineItem(item)` pada create dan edit JS harus memanggil:

```js
attachTaxModeHandlers(item);
```

## P1 Bug: Draft Does Not Persist Header Tax Input

### Evidence

Draft payload create/edit menyimpan:

- `nomor_faktur`
- `nama_pt_pengirim`
- `tanggal_pengiriman`
- `tanggal_terima`
- `auto_receive`
- `lines`

Tetapi belum menyimpan header:

- `tax_input`

Restore draft juga belum mengisi kembali `#tax_input`.

### Impact

User mengisi pajak supplier header, lalu reload/navigasi/restore draft, nilai pajak header bisa hilang.

Pada edit, auto restore draft bisa menimpa server-rendered tax value secara diam-diam.

### Expected Behavior

Draft payload create/edit harus menyimpan:

```js
tax_input: String(document.getElementById("tax_input")?.value ?? "")
```

Restore draft harus mengisi:

```js
const taxInput = document.getElementById("tax_input");

if (taxInput) {
    taxInput.value = String(header.tax_input ?? "");
}
```

Setelah restore, panggil:

```js
updateTaxModeFields();
```

## Fix Plan

### Slice 0028-A: Fix Edit/Revise Line Base Mapping

Target file:

- `app/Adapters/In/Http/Controllers/Admin/Procurement/Support/EditSupplierInvoiceLineItemsViewBuilder.php`

Change:

- `line_total_rupiah` untuk form input harus memakai `line_subtotal_before_tax_rupiah` when present and greater than 0.
- Keep display based on the submitted form input base amount.
- Preserve fallback for no-tax legacy rows.

Proof:

- Add/adjust feature test ensuring edit page for taxed line renders hidden `line_total_rupiah` as before-tax subtotal, not after-tax final.
- Run focused procurement tax/update tests.

### Slice 0028-B: Bind JS Line Tax Mode Handlers

Target files:

- `public/assets/static/js/pages/admin-procurement-create.js`
- `public/assets/static/js/pages/admin-procurement-edit.js`

Change:

- Add `attachTaxModeHandlers(item);` inside `initLineItem(item)`.

Proof:

- Static grep confirms function is called.
- Browser/manual behavior:
  - type line tax
  - header tax field hides/disables/clears
  - remove line tax
  - header tax becomes available again

### Slice 0028-C: Persist Header Tax in Draft

Target files:

- `public/assets/static/js/pages/admin-procurement-create.js`
- `public/assets/static/js/pages/admin-procurement-edit.js`

Change:

- Add `tax_input` to draft header payload.
- Restore `tax_input` into `#tax_input`.
- Call `updateTaxModeFields()` after restore.

Proof:

- Manual:
  - fill header tax
  - reload
  - draft restores header tax
  - line tax fields remain hidden/disabled
- Static grep confirms `header.tax_input` exists in collect and restore paths.

### Slice 0028-D: Optional UI Clarity

Target files:

- create/edit blade or shared partial if later extracted

Change:

- Add small status text/badge:
  - Mode Pajak Supplier aktif
  - Mode Pajak Rincian aktif

Proof:

- Manual UI inspection only.

## Regression Tests Needed

Minimum tests:

1. Edit page renders taxed line base subtotal:
   - saved `line_subtotal_before_tax_rupiah = 100000`
   - saved `line_total_rupiah = 111000`
   - rendered hidden `line_total_rupiah` must be `100000`

2. Edit page renders header-tax allocated line base subtotal:
   - saved line final after header tax allocation
   - rendered hidden input must use before-tax subtotal

3. Update submit without visible edit on taxed invoice must not increase tax again.

4. JS static proof:
   - create JS calls `attachTaxModeHandlers(item)`
   - edit JS calls `attachTaxModeHandlers(item)`

## Current Status

Open.

Next implementation slice should be 0028-A first because it protects money and inventory correctness. JS UX can wait; corrupted totals cannot. Humanity survives another annoying form, but accounting does not survive double tax.

## Progress Log

- 2026-06-18: 0028-A fixed. Edit/revise line item input now uses before-tax subtotal when available, preventing double tax on existing taxed invoices.
- 2026-06-18: 0028-B fixed. Create/edit JS now binds line tax mode handlers during line item initialization.
- 2026-06-18: 0028-C fixed. Create/edit draft payload now persists and restores header tax input, then recomputes tax mode visibility.

## Resolution Summary

Resolved at: 2026-06-18

Implemented fixes:

- 0028-A: Edit/revise form now uses `line_subtotal_before_tax_rupiah` as the line input base when available, preventing double tax on existing taxed invoices.
- 0028-B: Create/edit JavaScript now binds `attachTaxModeHandlers(item)` during line item initialization, so line tax immediately disables header tax mode.
- 0028-C: Create/edit draft payload now persists and restores header `tax_input`, then recomputes tax mode visibility after restore.

Verification performed:

- Focused tax UI tests passed.
- Focused supplier invoice update tests passed.
- Browser manual verification passed for supplier invoice detail page.
- Migration/schema issue was checked; detail page works after runtime DB verification.

Remaining notes:

- Optional UI badge for active tax mode is not required for correctness.
- Full regression test should pass before commit/push.
