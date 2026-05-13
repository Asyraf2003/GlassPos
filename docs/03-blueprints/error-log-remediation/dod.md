# Error Log Remediation Definition of Done

- Status: Planning DoD.
- Scope: Definition of Done untuk eksekusi docs/04-lifecycle/error-log/.
- Non-goal: dokumen ini bukan patch source dan bukan klaim bahwa error log sudah selesai.
## Prinsip DoD

- Sebuah issue tidak selesai hanya karena:
- dokumen menulis Patched
- commit message terdengar meyakinkan
- file source berubah
- php -l pass
- tombol UI disembunyikan
- test baru ditulis tetapi belum jalan
- satu happy path pass
- DoD harus dapat diverifikasi dari source, test, command output, dan dokumentasi.
## DoD Minimum Per Issue

- Setiap issue wajib punya:
- Path error log.
- Status dokumen saat intake.
- Status kepercayaan: trusted, weak, contradicted, atau unknown.
- Root cause final.
- Source map production.
- Affected layer.
- Upstream dependency.
- Downstream dependency.
- RED proof atau alasan RED tidak mungkin.
- Minimal patch boundary.
- Focused test target.
- Wider regression target.
- UI Blade impact decision.
- Native JS impact decision.
- Security/authorization impact decision.
- Audit/log/redaction impact decision.
- Closure proof.
- Residual gap.
- Handoff note.
- Issue belum boleh ditutup jika salah satu item di atas kosong tanpa alasan.
## DoD Source / Backend

- Source/backend DoD terpenuhi hanya jika:
- file production terkait sudah diinspeksi
- route/controller/middleware/use case/service/adapter/query terkait sudah dipetakan
- root cause cocok dengan source sekarang
- patch berada pada boundary yang tepat
- patch minimal, tidak broad refactor tanpa kebutuhan
- source tidak bertentangan dengan ADR terbaru yang relevan
- source tidak menghapus audit/history tanpa keputusan domain
- source tidak mempercayai input client untuk:
- price basis
- row state
- note state
- actor id
- capability performer
- MIME type
- filename
- path
- URL
- date-window authorization
- payment/refund allocation total
- mutation finance berada dalam transaction boundary yang benar
- payment/revision concurrency memakai lock protocol yang konsisten bila relevant
- current vs historical row boundary jelas
- legacy vs component payment compatibility jelas
- refund lifecycle tidak dapat mem-finalize state tanpa financial proof
- terminal states seperti closed/refunded diperlakukan sesuai policy
## DoD UI Blade

- UI Blade DoD terpenuhi hanya jika:
- semua view path terdampak dicatat
- action button/link/form sesuai can_* flag atau policy data
- backend tetap menolak direct request
- UI hiding tidak diklaim sebagai security boundary
- user-controlled text di-escape
- JSON dalam <script> memakai safe encoding seperti @json, Js::from, atau JSON_HEX flags yang tepat
- tidak ada raw json_encode berbahaya dalam script context
- unsafe string seperti </script> tidak muncul literal di response yang relevan
- URL-like attributes seperti href, src, action memakai validated safe URL
- javascript:, data:, external URL, dan protocol-relative URL ditolak atau fallback
- global count/stat tidak bocor ke cashier
- Blade tidak memperkenalkan inline PHP raw block untuk user-controlled data
- istilah UI yang terkunci tetap dipertahankan bila slice menyentuh UI:
- Nota
- Kasus
- Rincian
- Belum Lunas
- Lunas
- Batal
- Refund
## DoD Native JS

- Native JS DoD terpenuhi hanya jika:
- JS file atau inline config terdampak dicatat
- JS hanya progressive enhancement
- form fallback tetap aman tanpa JS
- server tetap source of truth
- JS tidak menjadi satu-satunya guard
- JS tidak mempercayai hidden field untuk financial/security decision
- JSON config aman dari script breakout
- tidak ada eval, dynamic script injection, atau string-to-code
- tidak ada innerHTML dari untrusted input tanpa sanitizer yang disetujui ADR
- selected row behavior tetap divalidasi server-side
- return/back URL tetap divalidasi server-side
- client-side price basis, row ID, date, amount, MIME, or capability tidak menjadi authority
- negative test atau response test membuktikan payload berbahaya tidak executable bila relevant
## DoD Security

- Security DoD terpenuhi hanya jika:
- guest, cashier, admin tanpa capability, dan admin dengan capability diuji sesuai route
- direct route request diuji, bukan hanya UI
- cashier date-window memakai server-side date/application timezone
- client-submitted date tidak menjadi authorization truth
- admin read access dan admin mutation access dibedakan
- transaction-sensitive admin mutation membutuhkan transaction capability
- capability toggle membutuhkan auth/admin/CSRF
- audit performer berasal dari authenticated session
- refund/payment/workspace/procurement mutation punya access gate dan domain eligibility
- private storage tidak diekspos melalui public helper, symlink, asset(), atau raw public path
- private proof attachment disajikan melalui route auth/policy
- attachment MIME dideteksi server-side
- risky/unknown attachment dipaksa download
- response attachment memakai X-Content-Type-Options: nosniff
- filename output disanitasi dan memakai safe Content-Disposition
- unsafe URL ditolak atau fallback
- output context sesuai ADR-0020
- authorization boundary sesuai ADR-0019
- concurrency boundary sesuai ADR-0022 bila payment/revision allocation terlibat
- seeder credential safety tidak diklaim selesai dalam workflow utama
## DoD Audit / Logging / Redaction

- Audit/log/redaction DoD terpenuhi hanya jika:
- sensitive mutation mencatat actor, role, target resource, action, timestamp
- before/after state dicatat bila tersedia dan relevan
- manual reason tetap wajib untuk domain action yang memang membutuhkan reason
- capability toggle mencatat actor, target actor, before state, after state, action, timestamp
- denied sensitive attempt dicatat bila policy/project membutuhkan
- log tidak menyimpan secret, token, raw private path, atau data proof yang tidak perlu
- audit performer tidak dapat dipalsukan lewat request body
- attachment serving tidak membocorkan absolute local path
- error response tidak membocorkan private storage internals
## DoD Test

- Test DoD terpenuhi hanya jika:
- RED characterization ada sebelum patch
- RED failure sesuai root cause, bukan fixture error
- targeted GREEN pass setelah patch
- focused blast-radius pass untuk cluster sensitif
- wider regression pass sesuai affected domain
- command dan output penting dicatat
- assertion count dicatat bila tersedia
- no-mutation assertion ada untuk rejected request
- no-leak assertion ada untuk XSS/disclosure/storage issue
- route-list proof ada untuk route/middleware issue
- grep/static negative search ada untuk Blade/JS/storage pattern bila relevan
- concurrency issue minimal punya lock/source proof dan, jika memungkinkan, true parallel stress test
- test tidak dilemahkan untuk membuat patch terlihat hijau
- syntax check tidak diperlakukan sebagai behavior proof
- missing vendor/dependency berarti verification gap, bukan pass
## DoD Docs

- Docs DoD terpenuhi hanya jika:
- docs/04-lifecycle/error-log/<issue>.md diupdate setelah proof, bukan sebelum
- status memakai bahasa yang tidak melebih-lebihkan proof
- root cause final dicatat
- source reality dicatat
- RED proof dicatat
- GREEN proof dicatat
- focused proof dicatat
- wider proof dicatat bila ada
- UI Blade impact dicatat
- native JS impact dicatat
- security impact dicatat
- audit/log/redaction impact dicatat
- residual gap dicatat
- conflict dengan dokumen lain dicatat
- jika owner menerima defer, defer reason dan scope dicatat
- commit hash hanya dicatat jika commit benar-benar ada
- docs closure tidak mengklaim seeder selesai
## DoD Final Closure

- Final closure seluruh error_log hanya boleh terjadi jika:
- semua 29 error log sudah masuk sequence
- semua issue punya trust status final
- semua weak/contradicted issue sudah resolved atau deferred dengan owner acceptance
- semua active slice punya targeted/focused proof
- semua cluster sensitif punya wider regression proof
- source/docs conflicts diselesaikan
- ADR conflicts diselesaikan
- UI Blade dan native JS review selesai
- security authorization review selesai
- audit/log/redaction review selesai
- final global verification pass
- global blocker seperti PHPStan, audit-lines, audit-blade, contract audit, atau full test failure tidak aktif
- documentation closure packet lengkap
- seeder tetap future scope kecuali owner membuka scope seeder terpisah
## Anti-Claim Rules

- Tidak boleh klaim:
- fixed tanpa targeted proof
- strict fixed tanpa focused proof untuk sensitive issue
- secure tanpa direct request authorization proof
- verified jika test tidak jalan
- global verified jika full gate gagal
- safe jika hanya UI button disembunyikan
- XSS fixed jika rendered response belum diuji atau sink lain belum dicari
- private storage safe jika deployment/public helper/symlink belum dicek
- concurrency fixed jika hanya single-request test dan lock protocol belum dibuktikan
- refund safe jika row eligibility, parent note eligibility, and finalization proof belum lengkap
- payment safe jika existing legacy/component allocation dan outstanding tidak diuji
- audit safe jika actor/performed_by masih client-controlled
- seeder fixed dalam workflow utama sesi ini
- all error logs closed selama masih ada weak/contradicted/unverified issue
## Stop Condition Untuk Closure

- Stop closure jika:
- proof kurang
- source berbeda dari dokumen
- test tidak jalan
- wider regression gagal
- conflict belum diputuskan
- UI/JS impact belum direview
- authorization hanya di Blade/JS
- audit/log/redaction belum jelas
- final docs akan menyembunyikan gap
