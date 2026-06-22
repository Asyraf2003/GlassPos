<!-- HYPERPOS_LATEST_HANDOFF_START -->
Latest Handoff: docs/04_lifecycle/error_log/0039_cashier_note_create_edit_refund_reporting_final_closure.md
<!-- HYPERPOS_LATEST_HANDOFF_END -->

# App Kasir Hexagonal

## ✅ Latest Closed Workflow

Cashier note create/edit/refund/reporting consistency workflow is **FINAL CLOSED**.

### Latest verified checkpoint

Service Package Profit Breakdown and transaction workspace revision-snapshot reporting workflow is **CLOSED / VERIFIED**.

Verified scope:
- admin report page **Laba Paket Service**
- sidebar entry and index route for service package profit breakdown
- Excel export for service package profit breakdown, including summary and detail sheets
- UI-level report checks, not only query/database checks
- HTTP workflow regression from transaction create, late payment, admin revision, report UI, and Excel export
- package auto split multi-sparepart reporting
- historical COGS from inventory movements
- component refund exposure in UI and Excel summary
- custom/monthly period filtering
- canceled/outside-range package exclusion
- catalog price changes after transaction without corrupting historical package reporting
- admin revision after catalog price changes without double counting old package rows

Regression fixes captured by the latest checkpoint:
- transaction workspace create now bootstraps an initial current revision so late selected-row payments can read billing rows
- trusted revision snapshot marking covers all product lines, not only the first line
- package auto split product line composition preserves server-trusted revision snapshot unit prices instead of silently using the latest catalog price
- revision/payment/reporting fixtures replace existing revision lines when reseeding current revisions, preventing duplicate `revision_id + line_no` collisions after automatic bootstrap
- audit-lines remains enforced without bypass by extracting new responsibilities into small services

Latest proof:
- `make audit-lines` GREEN
- `php artisan test` GREEN: 1290 passed, 7617 assertions, 54.50s

Canonical closure:
- `docs/04_lifecycle/error_log/0039_cashier_note_create_edit_refund_reporting_final_closure.md`
- `docs/03_blueprints/finance/0011_cashier_note_consistency_workflow_index.md`
- `docs/03_blueprints/finance/0016_cashier_note_final_regression_matrix.md`

Previous closure proof:
- Phase 0-7 FIXED.
- Focused regression matrix GREEN.
- `make verify` GREEN: 1276 passed, 7445 assertions, 54.12s.

Operator/AI rule:
Do not reopen this workflow, restart its source-map, or create a new phase unless there is new concrete failing test evidence, production bug evidence, or explicit owner instruction.

---

> Sistem kasir dan operasional servis-sparepart yang dibangun dengan fokus pada **presisi data**, **kerahasiaan data klien**, **arsitektur modular**, dan **auditability**.
>
> Project ini dibuat bukan untuk terlihat “ramai” di permukaan, tetapi untuk membuktikan bahwa aplikasi operasional yang menyentuh **uang, stok, riwayat, dan koreksi data** bisa dibangun dengan disiplin engineering yang serius.

---

## 🎯 Apa project ini?

**App Kasir Hexagonal** adalah aplikasi operasional untuk kebutuhan kasir, servis, sparepart, stok, suplai, pembayaran, dan riwayat perubahan data.

Project ini sengaja diarahkan menjadi **lebih dari sekadar aplikasi kasir biasa**.

Fokusnya bukan hanya “transaksi bisa jalan”, tetapi:

- data uang dan stok harus presisi
- perubahan sensitif harus bisa ditelusuri
- data klien harus diperlakukan secara hati-hati
- fitur harus bisa berkembang tanpa merusak fondasi inti
- setiap perubahan harus lolos pengujian dan Definition of Done sebelum dianggap selesai

Dengan kata lain, ini adalah project yang mencoba menjawab satu pertanyaan penting:

**bagaimana membangun aplikasi operasional yang tetap fleksibel untuk bisnis, tetapi tetap ketat terhadap integritas data?**

---

## 🧭 Kenapa project ini dibuat seperti ini?

Banyak aplikasi operasional gagal bukan karena tampilannya jelek, tetapi karena fondasinya longgar:

- stok berubah tanpa jejak yang jelas
- riwayat koreksi data kabur
- aturan bisnis tersebar di controller, view, dan query
- perubahan kecil merusak flow lama
- data klien diperlakukan terlalu longgar
- testing hanya jadi formalitas

Project ini dibangun dengan arah yang berbeda.

**App Kasir Hexagonal** memakai pendekatan bahwa:

- UI boleh berkembang
- fitur boleh bertambah
- mekanisme boleh diganti
- tetapi **inti aturan bisnis, presisi data, dan tanggung jawab tiap layer harus tetap terkunci**

---

## ✨ Nilai pembeda utama

### 1. Presisi data sebagai prioritas utama
Project ini dibangun dengan sensitivitas tinggi terhadap:

- nominal uang
- stok
- mutasi data
- laporan
- riwayat perubahan

Targetnya jelas: **sistem tidak boleh “kurang lebih benar”**.  
Untuk aplikasi operasional, selisih kecil tetap dianggap masalah.

### 2. Kerahasiaan data klien bukan tempelan
Kerahasiaan data tidak diperlakukan sebagai gimmick.

Desain project ini menempatkan kehati-hatian terhadap data sebagai bagian dari tanggung jawab sistem, bukan sekadar pesan di README.

### 3. Hexagonal architecture untuk fleksibilitas yang terkontrol
Project ini memakai pendekatan **hexagonal architecture** agar:

- aturan bisnis tetap hidup di core
- detail framework tidak menguasai domain
- adapter bisa diganti
- fitur bisa dibongkar-pasang dengan lebih aman
- perubahan tidak memaksa rewrite besar di seluruh aplikasi

Tujuannya bukan terlihat “canggih”, tetapi supaya project tetap sehat saat tumbuh.

### 4. Editable, tapi tetap punya log dan riwayat
Kebutuhan bisnis nyata sering menuntut data bisa dikoreksi.

Project ini mengambil posisi yang tegas:

- **editable** bila memang dibutuhkan operasional
- tetapi **setiap perubahan sensitif harus tetap bisa ditelusuri**

Jadi fleksibilitas tidak dibayar dengan hilangnya jejak.

### 5. Testing brutal + DoD ketat
Project ini tidak menempatkan testing sebagai pelengkap.

Filosofinya sederhana:

- kalau menyentuh perilaku penting, harus ada pembuktian
- kalau belum lolos verifikasi, belum layak dianggap selesai
- kalau belum memenuhi DoD, belum pantas diluncurkan

---

## 🧱 Fokus arsitektur

Project ini dibangun dengan pembagian tanggung jawab yang tegas.

### Core / Domain
Tempat hidupnya aturan bisnis inti:

- perilaku domain
- validasi bisnis
- invariant penting
- aturan presisi data
- tanggung jawab yang tidak boleh bocor ke UI

### Application / Use Case
Tempat orkestrasi alur kerja:

- menjalankan proses bisnis
- menghubungkan request dengan domain
- memastikan flow berjalan lewat jalur yang benar

### Adapters
Tempat detail implementasi berada:

- HTTP
- persistence
- database
- integrasi lain
- input/output boundary

### UI
UI diposisikan sebagai lapisan interaksi, **bukan sumber kebenaran**.

Artinya:
- UI mengikuti aturan inti
- UI tidak menjadi tempat logika bisnis utama
- perubahan UI tidak boleh mengubah makna domain secara diam-diam

---

## 🔒 Filosofi tanggung jawab data

Project ini dibangun di atas filosofi berikut:

### User-centric di permukaan
Pengguna butuh flow yang masuk akal, cepat, dan bisa dikoreksi saat operasional berubah.

### Precision-centric di inti
Meski user experience penting, sistem tetap harus keras pada:

- konsistensi data
- riwayat perubahan
- pembuktian transaksi
- integritas stok dan nominal

### Editable without losing accountability
Data yang boleh diubah harus tetap punya akuntabilitas.

### Modular without becoming chaotic
Fitur boleh modular, tetapi tidak boleh membuat aturan inti tercerai-berai.

---

## 📌 Cakupan sistem saat ini

Project ini sudah berada pada tahap di mana **inti sistem operasional, jalur koreksi transaksi, pembayaran komponen, dan sebagian laporan finansial penting telah dibangun serta diverifikasi**.

### Area yang sudah menjadi fokus implementasi inti
- kontrol akses dan pembatasan tanggung jawab
- master data inti
- alur barang / sparepart
- alur stok dan perubahan stok
- alur suplai / data masuk
- alur transaksi inti berbasis domain
- koreksi data dengan jejak riwayat
- selected-row payment dan settlement komponen pembayaran
- service package profit breakdown report
- Excel export laporan paket service
- auditability dan histori perubahan
- pengujian untuk flow penting
- quality gate berbasis DoD dan audit-lines

### Area yang masih menjadi pekerjaan lanjutan
- polish UI transaksi
- polish UI laporan dan konsistensi export antar laporan
- hardening skenario operasional baru bila ada bukti bug atau kebutuhan owner baru

Dengan posisi ini, project sudah menunjukkan bahwa yang dibangun bukan sekadar tampilan, tetapi **fondasi sistem operasional yang serius**.

---

## 🧪 Standar kualitas project

Project ini memakai standar kerja yang sengaja dibuat ketat.

### Definition of Done tidak bersifat kosmetik
Sebuah task tidak dianggap selesai hanya karena “sudah jalan”.

Task baru dianggap selesai bila:
- perilaku yang dituju benar
- hasilnya bisa dibuktikan
- pengujian relevan lulus
- perubahan tidak merusak kontrak penting
- kualitas implementasi lolos pagar yang sudah ditetapkan

### Testing diperlakukan sebagai alat validasi nyata
Testing di project ini bukan sekadar angka atau formalitas.

Pengujian dipakai untuk memastikan:
- flow penting tidak pecah
- koreksi data tidak merusak histori
- perubahan tidak menghasilkan regresi diam-diam
- perilaku sistem tetap konsisten saat fitur bertambah
- UI dan export menampilkan logic yang sama dengan source of truth laporan

### Launch harus layak, bukan sekadar cepat
Prinsip yang dipakai:
**lebih baik lambat sedikit tetapi benar, daripada cepat tapi meninggalkan utang keandalan.**

---

## 🧠 Apa yang ingin ditunjukkan project ini?

README ini tidak ditulis untuk menjual “fitur banyak”.

Yang ingin ditunjukkan dari project ini adalah kualitas berpikir di baliknya:

- kemampuan merancang aplikasi operasional yang sensitif terhadap data
- kemampuan menjaga boundary arsitektur
- kemampuan membangun sistem yang tetap fleksibel tanpa kehilangan kontrol
- kemampuan menjadikan testing dan DoD sebagai alat kerja nyata
- kemampuan memosisikan software sebagai alat yang harus bisa dipercaya, bukan sekadar dipakai

Bagi HRD teknis, founder, atau pihak yang memegang keputusan teknis, project ini dimaksudkan untuk menunjukkan bahwa pendekatan engineering di baliknya:

- sadar risiko
- sadar tanggung jawab
- sadar kualitas
- dan tidak membangun sistem bisnis penting secara serampangan

---

## 🚧 Status saat ini

**Current status:** active development

Posisi saat ini secara garis besar:
- core logic dan fondasi sistem sudah menjadi fokus implementasi utama
- disiplin arsitektur, testing, dan riwayat perubahan sudah menjadi bagian dari karakter project
- pekerjaan yang masih tersisa terutama berada pada **UI transaksi** dan **UI laporan**

Artinya, project ini sudah cukup matang untuk menunjukkan **arah engineering dan kualitas fondasi**, meskipun masih ada layer presentasi yang sedang disempurnakan.

---

## 🤝 Untuk siapa project ini relevan?

Project ini relevan untuk pihak yang menghargai software dengan karakter berikut:

- serius terhadap data
- tidak gegabah terhadap perubahan
- peduli terhadap jejak audit
- ingin sistem yang bisa berkembang tanpa rewrite total
- melihat kualitas engineering sebagai aset bisnis, bukan biaya tambahan

---

## 📜 Lisensi

Repository ini **bukan open source permissive**.

Kode sumber disediakan dalam model **source-available** untuk tujuan:
- membaca
- mempelajari
- mengevaluasi pendekatan arsitektur dan engineering

**Tidak diizinkan untuk penggunaan komersial atau distribusi ulang tanpa izin tertulis.**

Lihat file `LICENSE.md` untuk detail lengkap.

---

## Penutup

**App Kasir Hexagonal** adalah project yang dirancang untuk menunjukkan satu hal dengan jelas:

> aplikasi operasional yang menyentuh transaksi, stok, histori, dan data klien seharusnya dibangun dengan disiplin tinggi — bukan hanya supaya berjalan, tetapi supaya bisa dipercaya.

Jika Anda mencari project yang hanya menonjolkan tampilan, ini bukan itu.  
Tetapi jika yang dicari adalah **ketelitian data, arsitektur yang sadar tanggung jawab, auditability, dan quality bar yang serius**, maka project ini memang dibuat untuk berbicara di area tersebut.
