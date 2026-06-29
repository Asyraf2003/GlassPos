<!-- HYPERPOS_LATEST_HANDOFF_START -->
Latest Handoff: docs/99_archive/04_lifecycle/error_log/0049_manual_qa_supplier_invoice_revision_and_timezone_gap.md
Latest Supporting Handoff: docs/99_archive/04_lifecycle/handoff/0050_legacy_timestamp_repair_handoff.md
Status: FINAL CLOSED / 0049-0050 FIXED / no production timestamp repair recommended
<!-- HYPERPOS_LATEST_HANDOFF_END -->

# App Kasir Hexagonal

## ✅ Latest Closed Workflow

Supplier invoice manual QA follow-up, timestamp display, and production timestamp repair assessment are **FINAL CLOSED**.

Source of truth:
- `docs/99_archive/04_lifecycle/error_log/0049_manual_qa_supplier_invoice_revision_and_timezone_gap.md`
- `docs/99_archive/04_lifecycle/handoff/0050_legacy_timestamp_repair_handoff.md`

Status:
FINAL CLOSED / 0049-0050 FIXED / supplier invoice revision + note correction history + timestamp display + production read-only diagnostic complete

Purpose:
Dokumen ini adalah pointer terbaru untuk AI/operator agar tidak mengulang investigasi supplier invoice revision, note correction history, owner-facing timestamp display, atau legacy production timestamp repair yang sudah diselesaikan.

Final scope closed:
- supplier invoice edit reason propagation and latest reason display
- supplier invoice version timeline and owner-facing revision summary
- supplier invoice tax-only revision negative stock false blocker
- supplier invoice edit localStorage draft revision isolation
- note correction history manual failure reclassified as data/setup-specific
- owner-facing timestamp display conversion to Asia/Makassar
- production read-only timestamp diagnostic
- no production timestamp repair recommended

Final proof:
- Focused procurement and note tests passed during the 0049 slices.
- `make audit-lines` passed after splitting oversized files.
- `make audit-blade` passed after removing PHP/directive PHP from supplier invoice Blade.
- Production diagnostic used SQL `SELECT` only.
- Production diagnostic found recent audit/supplier invoice timestamps UTC-like and several note/refund/mutation candidate tables empty.
- Date-only business fields remain excluded from timestamp shifting.

Canonical closure docs:
- `docs/99_archive/04_lifecycle/error_log/0049_manual_qa_supplier_invoice_revision_and_timezone_gap.md`
- `docs/99_archive/04_lifecycle/handoff/0050_legacy_timestamp_repair_handoff.md`
- `docs/99_archive/04_lifecycle/error_log/0048_owner_facing_indonesian_language_gap_handoff.md`

Important:
- `0049` is the latest manual QA follow-up closure pointer.
- `0050` is the production timestamp repair decision pointer.
- `0048` remains the previous owner-facing language/reason visibility closure pointer.
- Active lifecycle folders should be reserved for open or in-progress work.

Do not reopen without new bug evidence:
- Do not restart supplier invoice revision/reason/timeline investigation unless a new concrete failing test, production bug, or owner request opens a new workflow.
- Do not run production timestamp repair unless future rows are proven local-like with reliable owner action-time evidence.
- Do not treat empty note mutation/refund tables as UI bugs without row-level evidence.
- Do not shift date-only business fields.

Boundaries still locked:
- No DB enum/key rename.
- No route rename.
- No request field rename.
- No DTO key rename.
- No event literal rename.
- No Mobile API scope.
- No Operational Profit formula change.
- No refund policy change.
- No production write repair from this closure.
- No git operation requested by this document.

Final stop rule:
STOP. This workflow is closed after final local verification passes.

Operator/AI rule:
Use this README pointer as the latest closure guard. Do not reopen 0048/0049/0050 unless there is new concrete failing test evidence, production bug evidence, or explicit owner instruction.
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
- selected-row refund dengan audit reason propagation
- supplier invoice revision reason visibility
- owner-facing Indonesian label consistency
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
- owner-facing Indonesian label cleanup dan reason visibility/audit propagation sudah ditutup untuk flow prioritas
- pekerjaan lanjutan terutama berada pada polish UI/UX, skenario operasional baru, dan pengembangan fitur berikutnya bila ada bukti kebutuhan owner baru

Artinya, project ini sudah cukup matang untuk menunjukkan **arah engineering dan kualitas fondasi**, dengan lapisan presentasi yang terus dipoles tanpa mengorbankan integritas data.

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
