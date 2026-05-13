# Handoff 2026-04-19
## Procurement void integrity, payment-proof split, and seeder alignment

### Facts

#### 1. Procurement invoice action flow sudah dirapikan
- Index nota faktur procurement sekarang memakai contract backend 4 tombol aksi:
  - Detail
  - Bayar
  - Edit Nota / Koreksi
  - Hapus Nota
- `Bayar` tetap modal dari index.
- `Bukti Bayar` dipisah ke halaman khusus pembayaran / bukti bayar.
- `Edit Nota` dan `Koreksi` ditentukan backend berdasarkan state invoice.
- `Hapus Nota` hanya aktif untuk invoice editable dan memakai alasan + verifikasi ganda.

#### 2. Logic nomor faktur duplicate sudah dikunci dengan opsi B
- Duplicate nomor faktur ditolak hanya terhadap invoice supplier yang masih aktif.
- Nomor faktur dari invoice yang sudah `void` boleh dipakai lagi.
- Jalur `update` / `revise` sudah dibetulkan agar tidak menembak invoice dirinya sendiri.
- Regression pada revise/update procurement akibat validator duplicate sudah dibereskan.

#### 3. Halaman pembayaran / bukti bayar sudah dipisah
- Halaman detail procurement lama dibersihkan agar fokus ke:
  - ringkasan nota
  - policy
  - receipt
  - rincian line
- Halaman pembayaran / bukti bayar sekarang menjadi surface khusus untuk:
  - catat pembayaran
  - status pembayaran
  - upload bukti
  - riwayat lampiran bukti

#### 4. State `voided` sekarang sudah dibaca benar di read-side
- Detail procurement sekarang mengenali `policy_state = voided`.
- Detail `voided` bersifat read-only.
- Payment proof page untuk invoice `voided` bersifat baca-saja.
- Filter table procurement sekarang mendukung `voided`.
- Row `voided` di table tidak lagi memberi aksi edit/payment/void.

#### 5. Integrity test untuk `void` sudah ditambah
Fokus penguncian:
- `void` sukses pada invoice editable tidak membuat side effect inventory/costing.
- `void` gagal karena payment 1 rupiah tidak mengubah angka keuangan.
- `void` gagal karena receipt 1 pcs tidak mengubah stok / avg cost.
- `void` sukses hanya mengeluarkan invoice target dari outstanding view dan payable summary, tanpa merusak invoice aktif lain.

#### 6. Seeder alignment sudah dimulai dan sebagian besar scope yang relevan sudah dibereskan
- Product scenario seeder modern sekarang sudah mengisi threshold stock.
- Procurement seeder cluster sekarang sudah mengikuti schema:
  - `supplier_invoice_lines` revision fields
  - `supplier_receipt_lines` snapshot fields
- Level 2 sekarang punya seeder khusus skenario invoice `void`.

---

### References

#### Domain / UI / logic yang disentuh
- `app/Adapters/In/Http/Requests/Procurement/CreateSupplierInvoiceDuplicateNumberPostValidation.php`
- `app/Adapters/In/Http/Requests/Procurement/CreateSupplierInvoicePostValidator.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/ProcurementInvoiceDetailPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/ProcurementInvoicePaymentProofPageController.php`
- `app/Adapters/In/Http/Controllers/Admin/Procurement/Concerns/BuildsProcurementInvoiceDetailPolicyView.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailPayload.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableBaseQuery.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTableFilters.php`
- `app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceTablePayload.php`
- `app/Adapters/Out/Procurement/Concerns/BuildsProcurementInvoiceTableRowPayload.php`
- `app/Adapters/In/Http/Requests/Procurement/ProcurementInvoiceTableQueryRequest.php`
- `resources/views/admin/procurement/supplier_invoices/index.blade.php`
- `resources/views/admin/procurement/supplier_invoices/show.blade.php`
- `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php`
- `resources/views/admin/procurement/supplier_invoices/partials/filter_drawer.blade.php`
- `public/assets/static/js/pages/admin-procurement-invoices-table.js`
- `routes/web/admin_procurement.php`

#### Seeder yang disentuh
- `database/seeders/Product/ProductSeedThresholds.php`
- `database/seeders/Product/ProductScenarioActiveBasicSeeder.php`
- `database/seeders/Product/ProductScenarioEditedSeeder.php`
- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`
- `database/seeders/Product/ProductScenarioSoftDeletedSeeder.php`
- `database/seeders/Load/ProcurementLoadSeeder.php`
- `database/seeders/SupplierInvoiceBaselineSeeder.php`
- `database/seeders/SupplierInvoiceAnnualDenseSeeder.php`
- `database/seeders/SupplierInvoiceScenarioSeeder.php`
- `database/seeders/SupplierInvoiceVoidedScenarioSeeder.php`
- `database/seeders/SeedLevel2Seeder.php`

#### Test yang relevan
- `tests/Feature/Procurement/CreateSupplierInvoiceDuplicateNumberValidationFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceIndexPageFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceTableDataAccessFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceDetailPageFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoicePaymentProofPageFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceVoidedDetailPageFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceVoidedPaymentProofPageFeatureTest.php`
- `tests/Feature/Procurement/ProcurementInvoiceVoidedTableFilterFeatureTest.php`
- `tests/Feature/Procurement/VoidSupplierInvoiceIntegrityFeatureTest.php`
- `tests/Feature/Procurement/UpdateSupplierInvoiceFeatureTest.php`
- `tests/Feature/Procurement/ReviseReceivedSupplierInvoiceDeltaFeatureTest.php`
- `tests/Feature/Procurement/ReviseReceivedSupplierInvoiceNegativeStockGuardFeatureTest.php`
- `tests/Feature/Procurement/ExtremeReceivedInvoiceRevisionMatrixFeatureTest.php`

---

### Scope in

#### Selesai dalam page ini
- Perbaikan action procurement index.
- Split halaman pembayaran / bukti bayar.
- Hardening logic `void`.
- Hardening duplicate nomor faktur opsi B.
- Hardening read-side `voided`.
- Penambahan integrity tests untuk `void`.
- Alignment product scenario seeder threshold stock.
- Alignment procurement seeder cluster terhadap schema terbaru.
- Penambahan level 2 seeder untuk skenario `void`.

---

### Scope out

#### Belum menjadi target page ini
- Handoff atau audit menyeluruh semua seeder non-product dan non-procurement.
- Audit cluster employee / expense / customer seeder.
- Penambahan skenario `void` ke level 3.
- Refactor lanjutan terhadap seeder cleanup untuk reversal tables.
- Verifikasi manual UI di browser untuk semua surface hasil patch.
- Penguatan handoff seeder global di luar procurement + product.

---

### Locked decisions

#### 1. Policy `void` tetap pre-effect only
- Invoice yang sudah punya payment atau receipt tidak boleh `void`.
- Kasus salah input setelah payment / receipt ditangani lewat correction / reversal, bukan `void`.

#### 2. `voided` adalah terminal state di read-side
- Tidak ada aksi mutasi lagi.
- Halaman payment proof bersifat read-only.
- Detail tidak boleh lagi menawarkan receive/payment yang tidak relevan.

#### 3. Nomor faktur boleh dipakai ulang hanya jika sumber lama sudah `void`
- Ini dikunci di validator create.
- Update/revise mengecualikan invoice yang sedang diedit.

#### 4. Level 2 adalah tempat skenario bisnis eksplisit
- Seeder `void` dimasukkan ke `SeedLevel2Seeder`.
- Level 3 dibiarkan fokus ke dataset monster / volume, bukan edge-case policy.

#### 5. `ProductScenarioLegacyIncompleteSeeder` sengaja dibiarkan null threshold
- Ini dipertahankan sebagai skenario legacy / backfill.
- Seeder modern lainnya sekarang sudah isi threshold.

---

### Development options yang sudah dieksekusi

#### Procurement action
- Dipilih opsi backend contract-driven, bukan hardcoded JS branching.

#### Payment proof UX
- Dipilih halaman khusus terpisah, bukan menumpuk section baru di detail lama.

#### Void integrity
- Dipilih hardening via test matrix dan read-side guard, bukan melonggarkan policy void.

#### Seeder strategy
- Dipilih per-cluster alignment:
  - product threshold dulu
  - procurement schema setelah itu
  - baru tambah scenario void level 2

---

### Proof

#### Verifikasi logic dan UI procurement
- Full suite pernah lulus:
  - `695 passed`
  - `3598 assertions`
- Sebelumnya regression revise/update akibat duplicate validator juga sudah kembali hijau.

#### Verifikasi khusus `void`
- `VoidSupplierInvoiceIntegrityFeatureTest` pass 4 test.
- Fokus test:
  - no inventory/costing side effect on successful pre-effect void
  - failed void with 1 rupiah payment preserves financial state
  - failed void with 1 pcs receive preserves stock and avg cost
  - successful void excludes only target invoice from outstanding and payable summary

#### Verifikasi seeder product threshold
- `null` threshold di product scenario tinggal muncul di `ProductScenarioLegacyIncompleteSeeder`
- Semua file scenario product yang diubah lolos `php -l`

#### Verifikasi seeder procurement schema
- `grep` menunjukkan field berikut sudah masuk:
  - `revision_no`
  - `is_current`
  - `source_line_id`
  - `superseded_by_line_id`
  - `superseded_at`
  - `product_id_snapshot`
  - `unit_cost_rupiah_snapshot`
- Semua file procurement seeder yang diubah lolos `php -l`

#### Verifikasi seeder void level 2
- `SupplierInvoiceVoidedScenarioSeeder.php` lolos `php -l`
- `SeedLevel2Seeder.php` lolos `php -l`
- Wiring `SeedLevel2Seeder` sudah memanggil `SupplierInvoiceVoidedScenarioSeeder`
- Seeder `void` sudah jelas mengisi:
  - `voided_at`
  - `void_reason`
  - pasangan reuse nomor faktur dari invoice void ke invoice aktif

#### Verifikasi make seed levels
- `make 2` dan `make 3` tersedia.
- `make 2` sudah berhasil setelah wiring terbaru.
- `make 3` tetap tidak dimaksudkan untuk semua skenario `void`; level 3 fokus volume.

---

### Files changed summary

#### Product seeder cluster
- Tambah helper threshold:
  - `database/seeders/Product/ProductSeedThresholds.php`
- Ubah scenario product agar pakai threshold:
  - active basic
  - edited
  - recreated
  - soft deleted

#### Procurement seeder cluster
- Tambah revision fields pada `supplier_invoice_lines`
- Tambah snapshot fields pada `supplier_receipt_lines`
- Tambah seeder skenario void khusus level 2

#### Procurement logic cluster
- Payment proof page terpisah
- Detail page bersih
- Table action contract
- Filter `voided`
- State read-only `voided`
- Duplicate invoice guard opsi B
- Integrity tests

---

### Known gaps

#### 1. Seeder cluster non-product/non-procurement belum diaudit penuh
Masih perlu audit terpisah bila mau baseline seeder benar-benar bersih seluruh repo:
- customer transaction / payment / refund cluster
- employee finance cluster
- expense cluster

#### 2. Level 3 belum punya skenario `void`
Ini bukan bug, tapi by design:
- level 3 sekarang fokus beban / volume
- bukan skenario policy eksplisit

#### 3. Belum ada handoff global untuk seluruh seeder architecture
Page ini hanya menutup:
- procurement logic + integrity
- seeder alignment product/procurement yang paling riskan

---

### Safest next step

#### Pilihan paling aman setelah handoff ini
1. Simpan file ini.
2. Commit semua patch terkait procurement + seeder alignment sebagai satu batch logis.
3. Kalau mau lanjut page berikutnya, fokus audit seeder cluster lain:
   - employee / debt / payroll
   - customer payment / refund / correction
   - expense
4. Jangan ubah policy `void` ke post-effect. Pertahankan correction / reversal sebagai jalur yang benar.

---

### Suggested commit grouping

#### Commit 1
`fix(procurement): harden invoice actions, void state, and payment proof page`

#### Commit 2
`test(procurement): add void integrity and voided read-side coverage`

#### Commit 3
`chore(seeders): align product thresholds and procurement revision/snapshot seeders`

---

### Progress

#### Page procurement void + integrity
- 100%

#### Page product threshold seeder alignment
- 100%

#### Page procurement seeder schema alignment
- 100%

#### Page level 2 void scenario seeder
- 100%

#### Seeder global audit
- belum selesai