# Error Log Remediation Workflow

- **Status:** Planning workflow.
- **Scope:** workflow eksekusi perbaikan docs/error_log/.
- **Non-goal:** dokumen ini bukan patch source, bukan klaim fix, dan bukan instruksi membuat seeder.
## Tujuan

- Workflow ini menjadi prosedur rigid untuk memproses seluruh masalah di docs/error_log/ sebagai satu rangkaian masalah yang saling bergantung, bukan daftar issue independen.
**Tujuan utamanya:**
- memastikan setiap klaim berbasis dokumen, source, dan command proof
- memisahkan status dokumen dari status sebenarnya di source
- mencegah patch yang hanya memindahkan bug antar flow
- menjaga perbaikan dari domain, application, infrastructure, HTTP/controller, Blade, native JS, security, audit, sampai dokumentasi
- menghentikan closure ketika proof tidak cukup
## Non-Goals

**Sesi ini tidak boleh:**
- mengimplementasikan bugfix
- membuat atau mengubah seeder
- menjalankan perubahan production source
- menganggap status Patched, Fixed, atau Fixed with proof sebagai benar tanpa verifikasi silang
- mengklaim semua docs/error_log/ selesai
- melakukan commit atau push
- memakai UI hiding sebagai security boundary
- menutup issue tanpa RED proof, focused proof, dan docs alignment yang sesuai scope
- Seeder berada di luar workflow utama sesi ini. Seeder hanya boleh disebut sebagai dependency, residual risk, atau future scope.
## Source Priority

**Keputusan harus mengikuti urutan prioritas ini:**
- docs/error_log/
- sumber utama daftar masalah
- petakan issue, status, scope, proof, gap, risiko, dan relasi
- status dokumen tidak otomatis dipercaya
- docs/blueprint/security/
- batasan security, auditability, redaction, authorization, upload/proof, access control, hardening
- blueprint 2026-05-06 ADR-0019 dipakai untuk access boundary
- blueprint 2026-05-06 ADR-0020 dipakai untuk output, URL, storage, attachment, dan disclosure
- Dokumen bertanggal 2026-05-05 dan 2026-05-06
- cari patch notes, workflow, owner decision, dan blueprint yang relevan
- dokumen tanggal baru punya bobot lebih kuat bila lebih spesifik
- docs/adr/
- ADR terbaru menang atas ADR lama jika konflik
- jika ADR lama dan baru konflik, tulis konflik, path dokumen, dan keputusan yang dipakai
- jika ada dokumen lebih spesifik daripada ADR umum, dokumen spesifik dapat menang untuk slice itu
- docs/workflow/
**workflow baru harus selaras dengan:**
- docs/workflow/error-log-strict-closure-protocol.md
- docs/workflow/finance-residual-error-log.md
- workflow ADR/security yang relevan
- source dan local command output tetap menang atas workflow lama jika ada konflik
## Evidence Labels

**Gunakan label ini secara konsisten:**
- **FACT:** terbukti dari dokumen, source, atau command output
- **GAP:** belum terbukti, proof kurang, test belum jalan, source belum dicek, atau scope belum jelas
- **RISK:** potensi bug, security issue, regression, data corruption, leakage, atau audit failure
- **DECISION:** keputusan workflow yang direkomendasikan untuk eksekusi
- **DOD:** syarat selesai yang bisa diverifikasi
## Trust Status Untuk Error Log

**Setiap error log harus diberi status kepercayaan kerja:**
- **trusted:** dokumen punya root cause, source map, RED/GREEN atau targeted/focused proof yang cukup untuk scope yang diklaim
- **weak:** ada patch atau klaim fixed, tetapi proof kurang, test gagal, hanya syntax check, atau ada residual besar
- **contradicted:** dokumen bertentangan dengan dokumen lain, source, atau test proof
- **unknown:** dokumen/source belum cukup dibaca
- Status kepercayaan tidak sama dengan status dokumen.
## Prinsip Utama

- Satu active slice saja.
- Tidak pindah slice sebelum proof slice aktif lengkap.
- Source dan test proof menang atas status dokumen.
- ADR terbaru menang atas ADR lama, kecuali dokumen yang lebih spesifik memberi aturan lebih tepat.
- RED proof harus ada sebelum patch, kecuali mustahil dan alasan mustahil dicatat.
- Patch harus minimal dan berada pada boundary yang benar.
- UI Blade dan native JS wajib direview bila issue punya dampak layar, form, link, config, atau action.
- Security boundary selalu server-side.
- Audit/log/redaction harus diverifikasi untuk mutation, payment, refund, proof attachment, capability, dan sensitive read.
- Closure dokumen hanya setelah source, test, UI/security review, dan residual gap dicatat.
## Step-by-Step Workflow

### Step 0 - Baseline Intake

**FACT yang harus dikumpulkan:**
- branch dan HEAD saat eksekusi
- git status --short --untracked-files=all
- daftar seluruh docs/error_log/*.md
- jumlah error log aktual
- daftar dokumen blueprint/security/ADR/workflow relevan
- status dokumen per issue
- proof yang diklaim per issue
- issue yang punya konflik atau residual gap
**Gate:**
- semua error log sudah terpetakan
- semua issue diberi trust status awal
- semua relasi antar issue dicatat
- tidak ada source patch
**Stop condition:**
- jumlah error log tidak cocok dengan dokumen audit tanpa penjelasan
- ada file error_log yang tidak terbaca
- ada dokumen yang mengklaim fixed tetapi proof tidak bisa dilacak
### Step 1 - Cluster dan Dependency Mapping

- Kelompokkan issue berdasarkan dependency dan domain impact, bukan nomor file.
**Minimal cluster:**
- current vs historical operational rows
- settlement/payment basis
- revision and payment concurrency
- access/capability/date-window boundary
- refund lifecycle and terminal state
- price basis authority
- output context, Blade, JS, unsafe URL
- storage/public helper/attachment proof
- seeder credential safety as future scope
- final global verification
**Gate:**
- setiap issue punya upstream dan downstream dependency
- issue yang boleh digabung dalam satu slice sudah jelas
- issue yang harus dipisah sudah jelas
- issue yang tidak boleh dikerjakan dulu karena proof/source map/dependency belum jelas sudah ditandai
**Stop condition:**
- ada issue finance/refund/access yang diperlakukan independen padahal bergantung pada settlement, current projection, atau access boundary
- ada issue UI yang dikerjakan sebelum server-side guard jelas
- ada seeder masuk active workflow tanpa instruksi eksplisit
### Step 2 - Source Inspection

- Untuk active slice, baca source sekarang, bukan hanya dokumen.
**Wajib cari:**
- file produksi terkait
- route/controller/middleware
- policy/use case/service
- adapter/repository/query
- Blade/view partial
- native JS/config sink
- audit/logging/redaction path
- existing tests
**Gate:**
- root cause sementara cocok dengan source sekarang
- source map lengkap sampai layer terdampak
- jika source berbeda dari dokumen, konflik dicatat
**Stop condition:**
- source tidak menunjukkan root cause yang sama dengan dokumen
- patch lama diklaim ada tetapi source sekarang tidak memilikinya
- source conflict menyentuh ADR dan belum ada keputusan
### Step 3 - RED Proof

- Buat atau jalankan characterization test yang membuktikan bug di source sekarang.
**RED proof harus menunjukkan:**
- command
- failure yang relevan
- assertion atau output penting
- alasan failure sesuai root cause, bukan fixture error
**Untuk issue yang sudah diklaim fixed:**
- jika proof kuat, re-run targeted/focused proof
- jika proof lemah, masukkan ke verification slice
- jika source/test bertentangan, treat as contradicted
**Gate:**
- RED valid atau alasan RED mustahil dicatat
- fixture tidak palsu
- failure bukan karena dependency environment, missing vendor, atau setup yang tidak relevan
**Stop condition:**
- test gagal karena alasan yang tidak dipahami
- test hanya membuktikan syntax
- test mengunci behavior yang bertentangan dengan ADR/domain decision
### Step 4 - Minimal Production Patch Boundary

- Patch hanya boleh dimulai setelah RED proof valid.
**Aturan patch:**
- patch boundary harus sesuai root cause
- jangan patch reader generik jika consumer semantics belum jelas
- jangan patch Blade/JS untuk menutup authorization server-side
- jangan patch UI untuk menutupi data corruption
- jangan refactor luas tanpa source map dan proof
- jangan ubah seeder dalam workflow utama
**Gate:**
- patch file sesuai active slice
- tidak ada file luar scope
- patch tidak melemahkan test existing
- patch tidak menghapus audit/history tanpa keputusan domain
**Stop condition:**
- patch perlu redesign domain besar tanpa owner decision
- patch memerlukan ADR baru atau konflik ADR
- patch membuat admin read access rusak saat memperbaiki mutation
- patch memblokir global cashier edit/refund tanpa policy
### Step 5 - UI Blade Impact Check

**Wajib dilakukan jika issue menyentuh:**
- tombol/link/action
- form
- workspace
- note detail
- refund/payment UI
- JSON config di Blade
- data yang dirender ke HTML/attribute/script
- public/sensitive attachment link
- count/stat yang terlihat user
**Checklist:**
- action tidak dirender ketika backend policy menolak
- backend tetap menolak direct request
- tidak ada raw user-controlled HTML
- JSON in script context memakai safe encoding
- URL-like attribute tidak menerima unsafe scheme
- can_edit_workspace atau flag UI selaras dengan server policy
- tidak ada hidden input yang menjadi authority domain/security
**Gate:**
- view path dicatat
- rendered response dites bila relevan
- negative search dilakukan untuk unsafe string/action/link
- UI tidak menjadi satu-satunya guard
**Stop condition:**
- direct route tetap bisa mutasi meski tombol disembunyikan
- raw JSON/HTML sink masih ada
- JS config bisa breakout dengan </script>
### Step 6 - Native JS Impact Check

**Wajib dilakukan jika issue menyentuh:**
- workspace JS
- selected row behavior
- inline payment/refund
- page config JSON
- return/back URL
- form submission enhancement
- dynamic action buttons
**Checklist:**
- JS hanya progressive enhancement
- server tetap source of truth
- JS tidak mempercayai hidden field untuk authorization, price basis, row state, MIME, URL, atau capability
- config JSON aman untuk script context
- tidak ada innerHTML dari untrusted data tanpa sanitizer yang disetujui ADR
- no eval, string-to-code, atau dynamic script injection
- fallback form submit tetap aman
**Gate:**
- JS file/sink dicatat
- payload XSS/unsafe URL diuji bila relevan
- server rejection diuji tanpa mengandalkan JS
**Stop condition:**
- perbaikan hanya ada di JS
- server menerima payload bila JS dilewati
- JS config tetap raw/unsafe
### Step 7 - Security and Authorization Review

**Gunakan ADR/security blueprint sesuai domain:**
**ADR-0019 / access boundary:**
- auth
- role admin/kasir
- transaction capability
- cashier today/yesterday window
- direct route request
- route placement
- server-side policy
**ADR-0020 / public surface:**
- output encoding
- unsafe URL
- storage boundary
- attachment serving
- MIME/content-disposition
- count/stat disclosure
**ADR-0022 / payment concurrency:**
- same-note lock
- allocation invariant
- transaction boundary
- idempotency gap
**Gate:**
- direct GET/POST/PATCH/DELETE behavior diuji bila relevan
- unauthorized request tidak mutasi data
- admin read vs mutation boundary tidak tercampur
- cashier date-window tidak memakai client date sebagai truth
- sensitive proof attachment tidak disajikan tanpa policy
**Stop condition:**
- route mutation lolos tanpa gate
- unauth/authz proof tidak ada
- audit performer berasal dari client input
- attachment MIME/filename/path berasal dari client sebagai authority
### Step 8 - Audit Trail, Logging, Redaction Check

**Wajib untuk:**
- payment
- refund
- note revision
- row mutation
- inventory movement
- capability toggle
- supplier invoice/payment/proof
- private file serving
- rejected sensitive attempt bila policy membutuhkan
**Checklist:**
- actor berasal dari authenticated session
- target resource tercatat
- before/after state tersedia bila relevan
- manual reason tetap wajib untuk aksi domain yang membutuhkannya
- no secret, token, private path, or raw proof metadata leaked in logs
- redaction dilakukan untuk data sensitif
**Gate:**
- audit/log path dicatat
- mutation sukses dan gagal punya expected audit behavior
- performer spoof dari request body ditolak
**Stop condition:**
- audit actor/client performer spoofable
- sensitive mutation tidak punya audit path
- log mengandung secret/private path/token
### Step 9 - Regression Test Selection

- Pilih test berdasarkan blast radius, bukan asal banyak.
**Jenis test:**
- **focused test:** file test yang langsung membuktikan issue
- **related test:** test pada service/route/projection terdekat
- **wider test:** suite cluster seperti tests/Feature/Note, tests/Feature/Payment, tests/Feature/Procurement
- **full verification:** global lint/static/test/audit sesuai project gate
**Gate:**
- test yang dipilih relevan dengan changed files
- minimal focused proof ada
- wider proof untuk sensitive domain/security ada
- failing unrelated test tidak diabaikan tanpa catatan
**Stop condition:**
- hanya php -l dipakai sebagai proof fixed
- test diubah untuk menutupi failure
- wider regression gagal dan belum dianalisis
### Step 10 - Focused Test

- Run targeted/focused proof.
**Gate:**
- targeted test pass
- assertion count dicatat
- output command dicatat
- behavior yang diuji cocok dengan root cause
**Stop condition:**
- targeted test tidak jalan
- pass hanya karena test fixture tidak melewati vulnerable path
- issue sensitive tapi tidak ada no-mutation/no-leak assertion
### Step 11 - Wider Test

- Run wider tests sesuai domain.
**Contoh:**
- **finance/payment:** tests/Feature/Note, tests/Feature/Payment
- **procurement/proof:** tests/Feature/Procurement
- **identity/access:** feature tests route/capability
- **Blade/output:** relevant feature response tests plus grep/static audit
- **attachment:** upload/serve/download matrix
- **concurrency:** lock/source proof plus concurrency stress bila tersedia
**Gate:**
- suite relevan pass
- jika gap seperti true concurrency/browser/manual/global suite belum dilakukan, gap dicatat eksplisit
- tidak ada failure tersembunyi
**Stop condition:**
- wider suite gagal karena source slice
- global blocker muncul dan belum punya owner decision
- proof tidak cukup untuk klaim closure scope
### Step 12 - Full Verification Gate

- Full verification hanya boleh diklaim jika semua gate project lewat.
**Minimal final global gate:**
- source anchors untuk semua slice
- targeted proof per issue
- focused proof per sensitive cluster
- wider test sesuai affected domain
- static/grep negative search untuk Blade/JS/storage/URL
- route-list check untuk access/capability
- audit/log/redaction review
- docs aligned
- no unresolved contradiction
- no active global blocker
- Jika make verify, PHPStan, audit-lines, audit-blade, contract audit, atau full test gagal, jangan klaim full global verified.
**Stop condition:**
- ada issue dengan trust weak/contradicted yang belum deferred dengan owner acceptance
- ada conflict source vs docs
- global verify gagal
- final docs menghapus gap tanpa proof
### Step 13 - Documentation Update Gate

- Update docs/error_log/ hanya setelah proof.
**Error log closure harus mencatat:**
- status baru dengan format konsisten
- root cause final
- source reality
- patch scope
- RED proof
- GREEN proof
- focused blast-radius proof
- UI Blade impact
- native JS impact
- security/authorization impact
- audit/log/redaction impact
- residual gaps
- conflict resolution
- closure decision
**Gate:**
- dokumen tidak mengklaim lebih dari proof
- residual out-of-scope tetap terlihat
- jika issue fixed tetapi proof lemah, status tetap verification gap atau masuk verification slice
**Stop condition:**
- dokumen diklaim fixed dari patch existence saja
- docs closure dilakukan sebelum tests
- status free-form mengaburkan gap
### Step 14 - Closure Criteria

**Issue boleh ditutup hanya jika:**
- root cause terbukti dan final
- source file production terkait sudah dipetakan
- RED proof ada atau alasan mustahil dicatat
- targeted GREEN proof pass
- focused blast-radius pass untuk sensitive issue
- UI Blade check selesai bila ada impact
- native JS check selesai bila ada impact
- security/auth check selesai bila ada impact
- audit/log/redaction check selesai bila ada impact
- docs updated with proof
- residual gap eksplisit dan tidak memblokir scope
- source/test proof tidak konflik dengan ADR
- active slice handoff lengkap
## Handling Untuk Issue Status Patched/Fixed Tapi Proof Lemah

**Jika error log berstatus Patched, Fixed, Fixed with proof, atau sejenisnya:**
- Jangan percaya status mentah.
- Cek root cause.
- Cek source file production.
- Cek test proof.
- Cek UI/Blade/JS impact.
- Cek security/auth/audit impact.
- Cek wider regression.
- Cek conflict dengan issue lain.
**Jika proof kuat:**
- masukkan ke trusted verification list
- re-run targeted/focused proof saat slice aktif
- jangan reopen tanpa bukti baru
**Jika proof lemah:**
- masukkan ke verification slice
- status kepercayaan weak
- closure dilarang sampai proof tersedia
**Jika source/test bertentangan:**
- status kepercayaan contradicted
- source/test proof menang
- buat conflict note sebelum patch
## Conflict Handling

**Wajib tulis:**
- path dokumen A
- path dokumen B
- isi konflik
- source/test proof yang menang
- keputusan workflow
**Known conflict yang harus dijaga:**
- docs/error_log/001-refunds-counted-as-paid-in-note-totals.md vs docs/error_log/003-refunded-revised-notes-are-misclassified-as-underpaid.md
- #001 butuh active refund mengurangi settlement
- #003 butuh historical refund tidak double-subtracted
- fix valid harus membuktikan keduanya
- docs/error_log/021-refunds-can-be-recorded-on-open-notes.md vs docs/error_log/022-cashier-refund-route-bypasses-note-access-guard.md
- #021 mengklaim controller menolak parent note open
- #022 menyatakan open-note refund behavior tidak berubah dan current test masih membolehkan open-note refund
- source/test proof saat eksekusi harus menang
- docs/error_log/011-cashier-revision-path-mutates-settled-note-state.md
- guard cashier settled-note harus tetap aktif
- admin official correction/revision route tidak boleh diblokir global
- route-scoped decision terbaru harus menang
## Documentation Closure Rules

**Allowed closure language:**
- Reported
- Characterized RED
- Patched Unverified
- Targeted Verified
- Focused Verified
- Docs Aligned
- Strict Fixed
- Deferred with owner acceptance
**Forbidden behavior:**
- mengklaim fixed dari commit message
- mengklaim secure dari UI hiding
- mengklaim verified dari php -l
- menghapus residual gap tanpa proof
- mengklaim full global suite bila global gate gagal
- mengklaim source clean jika git status belum dicek
## Final Global Verification

**Final global verification baru boleh ditulis jika:**
- semua active slices selesai
- semua issue weak/contradicted sudah resolved atau deferred dengan owner acceptance
- all targeted tests per issue pass
- all focused cluster tests pass
- wider domain suites pass
- access route-list proof tersedia
- Blade/JS negative search tersedia
- storage/attachment serving proof tersedia
- audit/log/redaction proof tersedia
- docs updated
- no seeder work diklaim selesai
- no unresolved source/docs contradiction
- Jika ada blocker, final status harus menyebut blocker itu secara eksplisit.
