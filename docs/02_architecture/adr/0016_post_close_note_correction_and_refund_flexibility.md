# ADR-0016 — Post-Close Note Correction and Refund Flexibility

- Status: Accepted
- Date: 2026-04-30
- Deciders: Project Owner, Business Owner / Paman, Architecture Decision
- Scope: Note / Payment / Refund / Inventory / Audit / Reporting / Authorization
- Supersedes: ADR-0005 in part

## Context

Operasi bengkel tidak selalu mengikuti happy path.

Dalam praktik nyata, satu nota bisa berisi banyak line. Setelah nota closed, paid, atau bahkan sudah punya refund history, operator masih bisa menemukan kebutuhan normal seperti:

- membatalkan satu line tertentu
- menambah line baru pada nota yang sama
- menambah produk yang sama
- mengubah qty atau harga karena salah input
- mengoreksi line lain setelah refund sebelumnya
- mengembalikan stok walaupun pembayaran belum penuh
- mempertahankan nota yang sama agar konteks kerja tidak pecah

Memaksa kasir membuat nota baru hanya untuk menambah satu line pada nota 15 line tidak sesuai realitas operasional.

Namun domain ini tetap sensitif karena:

- laporan tidak boleh salah 1 rupiah
- stok tidak boleh selisih 1 unit
- payment/refund tidak boleh hilang jejak
- harga produk dapat berubah setelah nota dibuat
- closed/paid/refunded note sudah memiliki konsekuensi finansial dan/atau inventori

Karena itu sistem tidak boleh memilih antara dua ekstrem:

- immutable total yang membuat operator kembali ke kertas
- editable bebas yang merusak audit, laporan, stok, dan histori

Keputusan baru adalah:

Post-consequence note tetap boleh berubah, tetapi hanya melalui mutation resmi yang audited, revisioned, evented, dan dapat diproyeksikan ulang untuk laporan.

## Decision

Sistem menetapkan bahwa closed, paid, dan refunded note bukan terminal mutation lock.

Nota tetap boleh mengalami perubahan setelah closed, paid, atau refunded, selama perubahan dilakukan melalui jalur resmi yang memenuhi aturan:

- wajib memiliki actor
- wajib memiliki role
- wajib memiliki reason
- wajib memiliki timestamp
- wajib memiliki before snapshot
- wajib memiliki after snapshot
- wajib mencatat affected rows
- wajib mencatat financial delta bila total/outstanding/payment/refund terdampak
- wajib mencatat inventory delta bila stok terdampak
- wajib menjaga histori lama tetap dapat dibaca
- wajib menghasilkan projection laporan yang konsisten

Perubahan tidak boleh dilakukan sebagai overwrite diam-diam.

## Mental Model

Refund dan edit memiliki model mental yang berbeda.

### Refund

Refund adalah pembatalan atau neutralization line tertentu dalam nota yang sama.

Analogi kertas:

- refund seperti menghapus sebagian gambar pada kertas yang sama
- line yang di-refund tidak hilang dari histori
- line tersebut menjadi inactive/canceled/refunded dalam projection
- uang kembali hanya terjadi jika memang ada uang yang sudah diterima
- stok kembali jika barang toko sudah pernah keluar

Refund tidak berarti seluruh nota mati.

### Edit

Edit adalah revision overlay.

Analogi kertas:

- edit seperti menempel kertas baru di atas kertas lama
- kertas lama tetap ada sebagai histori
- kertas baru menjadi current active projection
- line lama masih dapat ditampilkan sebagai previous/original snapshot
- perubahan current tidak boleh menghapus bukti state sebelumnya

## Status and Lifecycle Rule

`refunded` tidak boleh diperlakukan sebagai terminal state absolut yang membuat nota tidak bisa berubah lagi.

`refunded` berarti nota memiliki refund history atau pernah berada dalam projection refund tertentu.

Sistem wajib membaca status dari projection, bukan dari label tunggal yang dipakai sebagai kebenaran mutlak.

Projection minimal harus bisa menjelaskan:

- active rows
- inactive/refunded/canceled rows
- total active saat ini
- total lama / original
- total paid
- total refunded money
- outstanding
- inventory returned
- inventory still issued
- mutation history

## Edit Rule After Closed/Paid/Refunded

Edit setelah closed, paid, atau refunded diperbolehkan melalui audited revision resmi.

Allowed examples:

- tambah line baru
- tambah produk yang sama
- ubah service line
- ubah qty
- ubah harga
- hapus / cancel line
- refund line lain setelah refund sebelumnya
- koreksi data customer/header bila masih dalam policy akses

Forbidden examples:

- direct repository update tanpa event
- overwrite line lama sampai previous value hilang
- menghapus payment/refund lama
- mengubah inventory movement lama secara mutasi mundur
- mengubah harga line lama diam-diam mengikuti master terbaru
- menganggap refunded note tidak bisa berubah sama sekali

## Price Snapshot Rule

Harga tidak boleh retroaktif mengikuti master terbaru.

### Existing Line

Existing line menggunakan price snapshot saat line itu dibuat.

Jika existing line dihapus/refund/cancel, nilai yang dipakai adalah snapshot line tersebut.

Jika existing line diedit, sistem wajib mencatat:

- original value
- previous value
- current value
- reason
- actor
- role
- timestamp

### New Line After Old Note

Line baru yang ditambahkan ke nota lama memakai harga master terbaru sebagai default.

Namun ada kasus operasional ketika barang sebenarnya sudah diambil pada saat nota lama dibuat, tetapi baru dimasukkan belakangan.

Untuk kasus itu, user boleh melakukan manual price override sampai batas bawah harga lama/snapshot lama yang relevan.

Manual override wajib audited.

Rule minimum:

- default price = current master price
- allowed lower bound = relevant old snapshot price
- tidak boleh silent undercut di bawah batas bawah tanpa ADR/decision baru
- semua override harus punya reason dan actor

## Refund Rule for Paid, Partial, and Unpaid Rows

Refund selected line boleh dilakukan pada line yang sudah paid, partial, ataupun unpaid.

Jika line sudah punya uang diterima:

- sistem mencatat customer refund money event sesuai jumlah refundable
- outstanding dan projection diperbarui

Jika line belum punya uang diterima:

- money refund amount = 0
- sistem tetap boleh menonaktifkan/cancel/neutralize line
- outstanding turun sesuai active total baru
- stok kembali jika barang toko sudah pernah keluar
- audit tetap wajib

Jika line partial:

- sistem mengembalikan uang sesuai paid portion
- sistem menonaktifkan/cancel/neutralize line sesuai selected-row rule
- outstanding turun sesuai active total baru
- stok kembali jika applicable

Money refund dan row neutralization adalah konsep berbeda.

UI boleh memakai istilah operasional yang sederhana seperti "Refund" atau "Batalkan Line", tetapi domain internal harus tetap membedakan:

- refund uang
- cancel/neutralize line
- inventory reversal
- outstanding recalculation

## Inventory Rule

Inventory ledger harus tetap evented/reversal-based.

Jika line yang dibatalkan/refund mengandung barang toko dan stok sudah pernah keluar, sistem wajib membuat reversal movement.

Sistem tidak boleh mengedit movement lama secara diam-diam.

Inventory projection harus bisa menjelaskan:

- stock out asli
- reversal stock in
- source line
- refund/cancel event
- actor/reason/timestamp bila tersedia

Refund unpaid line tetap bisa mengembalikan stok.

Pembayaran bukan syarat untuk stok balik.

## Financial Rule

Payment lama tidak boleh diedit mundur.

Refund uang dicatat sebagai financial event baru.

Jika edit membuat total lebih kecil dari net paid:

- sistem harus menghasilkan refund required amount atau langsung membuat refund event bila flow yang dipilih memang refund

Jika edit membuat total lebih besar dari net paid:

- sistem harus menghasilkan additional payment required / outstanding baru

Jika edit hanya membatalkan unpaid line:

- tidak perlu customer refund money event
- outstanding tetap harus turun
- row history tetap tercatat

## Access Rule

Perbedaan kasir dan admin adalah scope akses, bukan model domain mutation.

Kasir:

- hanya boleh mengakses nota hari ini dan kemarin
- mutation tetap harus lewat rule audit/revision/event yang sama

Admin:

- boleh mengakses seluruh nota
- mutation tetap harus lewat rule audit/revision/event yang sama

Admin tidak boleh menjadi jalur bypass audit.

Kasir tidak boleh menjadi jalur bypass karena scope tanggal sempit.

## Reporting Rule

Laporan tidak boleh membaca overwrite terakhir secara buta.

Laporan harus membaca projection yang dapat ditelusuri ke event:

- note revision
- row cancellation / refund / neutralization
- payment
- money refund
- inventory movement
- inventory reversal
- price snapshot / override
- audit timeline

Report minimal harus bisa menjawab:

- nilai saat nota pertama dibuat
- nilai saat ini
- line mana yang aktif
- line mana yang dibatalkan/refund
- uang berapa yang benar-benar diterima
- uang berapa yang dikembalikan
- stok mana yang keluar
- stok mana yang kembali
- siapa mengubah
- kapan diubah
- alasan perubahan

## Relationship to ADR-0005

ADR-0005 tetap berlaku untuk prinsip:

- paid note tidak boleh diedit bebas tanpa audit
- reason wajib
- actor wajib
- timestamp wajib
- before/after snapshot wajib
- mutation sensitif tidak boleh direct overwrite
- report harus dapat merekonstruksi perubahan

ADR-0005 tidak lagi berlaku untuk larangan umum bahwa paid note hanya boleh dikoreksi secara sempit atau tidak boleh ditambah line baru.

ADR ini mengganti bagian tersebut dengan rule baru:

Closed/paid/refunded note boleh dimutasi melalui audited revision/event resmi selama financial ledger, inventory ledger, audit timeline, dan reporting projection tetap konsisten.

## Consequences

### Positive

- sistem mengikuti realitas operasional bengkel
- kasir tidak dipaksa membuat nota baru untuk kasus yang secara bisnis masih satu nota
- refund line unpaid/partial/full bisa ditangani konsisten
- stok bisa kembali walaupun belum ada uang balik
- laporan tetap bisa presisi karena mutation berbasis event/projection
- histori tidak hilang walaupun current projection berubah

### Negative

- implementation lebih kompleks
- status tunggal tidak cukup sebagai source of truth
- report harus lebih disiplin membaca projection/event
- UI harus membedakan current vs history tanpa membebani kasir
- test matrix menjadi lebih besar

## Implementation Direction

Implementasi harus dilakukan bertahap.

Prioritas aman:

1. Lock ADR ini.
2. Buat blueprint post-close mutation/refund projection.
3. Tambah test untuk paid/closed/refunded workspace revision yang sekarang masih ditolak.
4. Ubah guard dari "reject paid note" menjadi "route to audited post-close mutation mode".
5. Pisahkan money refund dari row neutralization.
6. Pastikan inventory reversal tidak bergantung pada payment status.
7. Perbaiki report/projection agar membaca active rows + mutation history.
8. Baru rapikan UI.

Tidak boleh langsung membuka semua mutation tanpa test dan projection proof.

## Invariants

- Tidak boleh silent overwrite.
- Tidak boleh menghapus payment lama.
- Tidak boleh menghapus refund lama.
- Tidak boleh mengubah inventory movement lama secara mundur.
- Tidak boleh mengubah harga line lama mengikuti master terbaru tanpa revision/correction.
- Tidak boleh membiarkan admin bypass audit.
- Tidak boleh membiarkan kasir bypass audit.
- Kasir hanya boleh akses hari ini dan kemarin.
- Admin boleh akses seluruh nota.
- Refund line unpaid tetap boleh mengembalikan stok bila stok sudah keluar.
- Nota yang punya refund history tetap boleh diedit lagi melalui jalur resmi.
- Current projection dan historical snapshot harus sama-sama dapat ditampilkan.

## Related Decisions

- ADR-0005 — Paid Note Correction Requires Audit
- ADR-0008 — Audit-First Sensitive Mutations
- ADR-0009 — Reporting as Read Model
- ADR-0011 — Money Stored as Integer Rupiah
- ADR-0015 — Note Operational Status Open Close Editable Partial Payment
