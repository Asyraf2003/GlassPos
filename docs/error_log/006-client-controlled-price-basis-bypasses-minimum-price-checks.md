# 006 - Client-controlled price basis bypasses minimum price checks

## Status

Patched, with verification gap.

Patch supplied and syntax/status/commit were reported, but no focused behavior test was reported as passing.

## Severity

High.

## Source

Audit report #006: Client-controlled price basis bypasses minimum price checks.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 005-note-revision-silently-drops-overpaid-allocations.md
- 004-refunded-work-items-survive-revisions-and-inflate-stock.md

### Jenis Keterkaitan

Direct workflow relationship with #005.

Indirect workflow relationship with #004.

### Alasan

Laporan #006 dan #005 sama-sama berada pada PATCH note workspace revision flow.

- #005 membahas payment replay saat downward note revision.
- #006 membahas client-controlled price_basis pada revision product line yang bisa melewati minimum selling price policy.

Laporan #006 juga berkaitan tidak langsung dengan #004 karena jika bypass berhasil, store-stock line tetap dapat dibuat dan inventory tetap bisa issued, sehingga inventory/financial integrity ikut terdampak.

Namun #006 bukan bug identik dengan #004/#005 karena root cause utamanya adalah trust boundary violation: server mempercayai marker pricing dari client.

## Update Log

### Update 1

Initial audit log entry untuk laporan #006.

Alasan update:

- Laporan menunjukkan client-controlled hidden field price_basis dapat dipakai untuk bypass minimum price check.
- Patch sudah diterapkan pada WorkItemFactory::makeSto().
- Patch menghapus bypass condition berbasis price_basis.
- Verification masih gap karena hanya php -l, git status, dan commit yang dilaporkan.

## Ringkasan Indonesia

Bug terjadi pada alur revisi note yang menerima product line dari client.

Field:

price_basis

dikirim dari HTTP revision workspace input, dinormalisasi, divalidasi, lalu diteruskan sampai ke WorkItemFactory.

Sebelum patch, WorkItemFactory::makeSto() melewati MinSellingPricePolicy::assertAllowed() jika payload memiliki:

price_basis = revision_snapshot

Masalahnya, price_basis berasal dari client. Walaupun niat awalnya adalah menjaga harga historis saat revision, marker itu tidak dibuktikan server-side sebagai snapshot existing line yang immutable.

Akibatnya authenticated cashier/admin dapat membuat PATCH request dengan:

- product_id valid
- qty valid
- unit_price_rupiah sangat rendah, misalnya 1
- price_basis = revision_snapshot

Lalu server membuat store-stock line di bawah harga minimum dan tetap mengeluarkan inventory.

## Dampak

Dampak utama:

- cashier/admin bisa mencatat penjualan store-stock di bawah harga minimum
- MinSellingPricePolicy bisa dilewati
- revenue record menjadi underpriced
- inventory tetap issued/stock_out
- fraud control berbasis floor price tidak efektif
- laporan penjualan dan margin bisa rusak

Ini financial-integrity dan inventory-integrity issue.

Severity High tepat karena minimum selling price adalah business invariant untuk POS/back-office. Tidak Critical karena butuh authenticated cashier/admin, akses note, CSRF/session valid, dan produk dengan stok.

## Jalur Risiko

Authenticated cashier/admin melakukan crafted revision request.

Workflow risiko:

1. User login sebagai cashier/admin.
2. User membuka note yang bisa direvisi.
3. User mengirim PATCH revision workspace request.
4. Request menyertakan product line dengan price_basis=revision_snapshot.
5. UpdateTransactionWorkspaceRules menerima value tersebut.
6. StoreTransactionWorkspaceProductLineNormalizer mempertahankan field tersebut.
7. CreateTransactionWorkspaceStoreStockLineMapper menyalin price_basis ke payload domain.
8. WorkItemFactory::makeSto() memakai price_basis untuk skip MinSellingPricePolicy.
9. StoreStockLine dibuat dengan harga di bawah floor.
10. Inventory issue tetap berjalan.

## Root Cause

Root cause:

Client-controlled marker dipakai sebagai dasar otorisasi/invariant bypass.

Field price_basis seharusnya tidak dipercaya untuk menentukan apakah price floor check boleh dilewati.

Jika sistem perlu menjaga historical revision price, status snapshot harus diturunkan dari server-side trusted data, misalnya:

- existing immutable work item line
- stored snapshot dari revision sebelumnya
- verified unchanged line id
- explicit correction/audit flow

Yang tidak boleh:

- hidden form field dari client langsung menentukan bypass financial policy

## Patch Summary

Patch minimal diterapkan pada:

app/Application/Note/Services/WorkItemFactory.php

Perubahan:

- hapus conditional skip:
  if (($p['price_basis'] ?? 'current_catalog') !== 'revision_snapshot')
- MinSellingPricePolicy::assertAllowed() sekarang selalu dipanggil untuk store-stock lines
- berlaku untuk current_catalog dan revision_snapshot
- existing flow tetap membuat StoreStockLine setelah policy lolos

Efek patch:

- client tidak lagi bisa bypass minimum price check memakai price_basis=revision_snapshot
- store-stock line revision tetap harus memenuhi floor price
- financial invariant ditegakkan di titik materialisasi work item

## Scope In

- WorkItemFactory::makeSto()
- Store-stock line materialization
- Minimum selling price invariant
- Revision flow price floor enforcement

## Scope Out

- Server-derived historical price snapshot model
- UI hidden field cleanup
- Request validation cleanup untuk price_basis
- Mapper/normalizer redesign
- Full browser/database E2E test
- Explicit exception flow untuk historical under-floor prices
- Inventory issue logic beyond price validation

## Proof Dari Patch Session

User reported:

- vulnerability still existed in HEAD logic path
- minimal remediation applied in WorkItemFactory::makeSto()
- MinSellingPricePolicy::assertAllowed() now always enforced for store-stock lines
- client-controlled bypass condition on price_basis removed
- commit created with message:
  Fix price-floor bypass in store stock revision lines

Testing reported:

- php -l app/Application/Note/Services/WorkItemFactory.php
- git status --short
- git add app/Application/Note/Services/WorkItemFactory.php && git commit -m "Fix price-floor bypass in store stock revision lines"

Changed file:

app/Application/Note/Services/WorkItemFactory.php

Reported diff size:

+1
-3

## Verification Gap

Only syntax validation and commit flow were reported.

No passing behavior test was reported for:

- underpriced current_catalog line is rejected
- underpriced revision_snapshot line is also rejected
- valid priced revision line still succeeds
- inventory is not issued when minimum price policy rejects the line
- transaction rolls back on rejected revision

Therefore this patch should be treated as source-fixed but not fully behavior-verified.

## Recommended Follow-up

Minimum regression test:

Scenario 1:

- product hargaJual: 100.000
- qty: 1
- unit_price_rupiah: 1
- price_basis: current_catalog
- expected: DomainException from MinSellingPricePolicy

Scenario 2:

- product hargaJual: 100.000
- qty: 1
- unit_price_rupiah: 1
- price_basis: revision_snapshot
- expected: DomainException from MinSellingPricePolicy

Scenario 3:

- product hargaJual: 100.000
- qty: 1
- unit_price_rupiah: 100.000
- price_basis: revision_snapshot
- expected: StoreStockLine created successfully

Recommended command later:

php artisan test --filter=WorkItemFactory

If no test exists, add focused coverage before treating this invariant as locked.

## Kesimpulan

Laporan #006 valid sebagai High severity financial-integrity issue.

Bug sebelumnya mempercayai field price_basis dari client untuk melewati minimum price policy. Itu trust-boundary bug yang cukup klasik: hidden field dianggap seperti dokumen kerajaan, padahal user bisa mengeditnya dari browser.

Patch minimal sudah benar untuk root cause langsung: WorkItemFactory sekarang selalu menjalankan MinSellingPricePolicy untuk store-stock lines. Namun patch masih perlu behavior test karena php -l hanya membuktikan sintaks valid, bukan invariant bisnis benar-benar terkunci.

## Related Workspace Security Finding From Error Log 007

### Related Error Log

- 007-admin-note-edit-page-exposes-stored-xss.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity issue in the shared note workspace surface.

This is not the same root cause as #006.

- #006 is about server-side price floor bypass through client-controlled price_basis.
- #007 is about stored XSS caused by raw JSON embedding of cashier-controlled note fields into the admin edit workspace.

Both findings involve note workspace/revision input crossing trust boundaries, so future workspace changes must verify both server-side financial invariants and safe client-side rendering.
