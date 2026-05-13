# Feature Continuation Blueprint

## Metadata

- Repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Branch baseline saat blueprint dibuat: `audit-1461-selective-patch`
- Baseline HEAD: `c0ce90a6`
- Source context:
  - Audit 1461 selective patch sudah closed.
  - Cash payment detail sudah persist.
  - Push notification infra sudah ada untuk due note/customer note reminder.
  - Supplier payable report sudah ada.
  - Dashboard operational performance sudah ada.
  - PDF/cetak nota/laporan belum masuk scope aktif.
- UI label stash outside-audit:
  - `stash@{0}: temp-ui-refund-label-outside-audit`

## Locked Workflow

Setiap kasus wajib dikerjakan satu per satu.

Urutan wajib:
1. Snapshot repo.
2. Inspect file terkait.
3. Lock FACT, GAP, DECISION.
4. Blueprint minimum.
5. Implement patch kecil.
6. Run focused tests.
7. Run `make verify`.
8. Commit kecil.
9. Buat handoff kasus di `docs/99-archive/handoff/v2/feature-continuation/`.
10. Update status ledger di file ini.

Jangan menaikkan progress tanpa proof command output.

Jangan campur fitur berbeda dalam satu commit kecuali refactor blocking seperti `audit-lines`.

Jangan pop atau stage stash UI refund label kecuali ada keputusan eksplisit untuk UI wording.

## Priority Rules

### P0

Masalah yang bisa menyebabkan risiko finansial langsung, pembayaran terlambat, laporan salah, domain lifecycle salah, atau fitur operasional kritis tidak berjalan.

P0 harus selesai sebelum P1 kecuali ada blocker teknis yang membuat P1 diperlukan sebagai dependency.

### P1

Masalah operasional penting yang meningkatkan akurasi kerja kasir/admin, mengurangi delay, atau memperjelas dashboard, tapi tidak langsung mengubah lifecycle finansial utama.

### P2

Enhancement, convenience, print/export, UI polish, atau fitur yang belum cukup dibahas kontraknya.

P2 tidak boleh mengganggu P0/P1.

## Status Ledger

| ID | Priority | Case | Status | Last Proof | Handoff |
|---|---:|---|---|---|---|
| FC-000 | P0 | System ambiguity inventory after abandoned feature work | CLOSED | Repo snapshot mapped cash change, dashboard, supplier payable notification, PDF, and UI stash ambiguity | `docs/99-archive/handoff/v2/feature-continuation/01-system-ambiguity-inventory.md` |
| FC-001 | P0 | Supplier payable push notification H-5 sampai lunas | OPEN | Snapshot menemukan supplier payable report dan push infra, belum ada supplier payable push handler/command | Pending |
| FC-002 | P1 | Dashboard potensi uang kembalian di Kinerja Operasional Bulan Ini | OPEN | Snapshot menemukan `change_rupiah`, belum ada dashboard field/metric terkait | Pending |
| FC-003 | P1 | Kalkulator pecahan uang kembalian | OPEN/PARTIAL | Cash change persisted, belum ada denomination calculator proof | Pending |
| FC-004 | P2 | PDF/cetak nota/laporan | OPEN | Snapshot hanya menemukan PDF attachment proof supplier, bukan generate PDF nota/laporan | Pending |
| FC-005 | P2 | UI refund label stash | DEFERRED | Stash masih ada, outside-audit | Pending |

## FC-001 - Supplier Payable Push Notification H-5 Sampai Lunas

### Priority

P0

### Problem

Sistem perlu mengirim laporan/notifikasi jika ada hutang pemasok yang mendekati jatuh tempo H-5, sudah jatuh tempo, atau belum lunas sampai dibayar lunas.

### Known Facts

- `supplier_invoices` memiliki `jatuh_tempo`.
- Supplier payable reporting sudah membaca due date dan outstanding.
- Push notification infra sudah ada.
- Command existing `push-notifications:send-due-note-reminders` adalah untuk nota pelanggan, bukan supplier payable.
- Existing due note payload berbicara tentang nota jatuh tempo, bukan hutang pemasok.

### Gaps

- Belum ada reader khusus supplier payable reminder.
- Belum ada use case `SendSupplierPayableReminderPushHandler`.
- Belum ada payload factory supplier payable.
- Belum ada console command supplier payable reminder.
- Belum ada focused tests untuk H-5 sampai lunas.
- Belum diputus apakah notifikasi dikirim harian atau hanya saat ada perubahan status.

### Required Contract

Reminder harus mengambil invoice supplier aktif dengan:
- `voided_at IS NULL`
- `jatuh_tempo <= today + 5 days`
- outstanding > 0
- tetap muncul sampai outstanding menjadi 0
- invoice lunas tidak muncul
- invoice voided tidak muncul

### Suggested Implementation Plan

1. Inspect supplier payable report query and due status resolver.
2. Tambah application reader/use case atau reuse source reader jika aman.
3. Tambah payload factory supplier payable.
4. Tambah push handler supplier payable.
5. Tambah console command:
   - `push-notifications:send-supplier-payable-reminders`
6. Tambah tests:
   - H-6 tidak dikirim.
   - H-5 dikirim.
   - due today dikirim.
   - overdue dikirim.
   - paid full tidak dikirim.
   - voided tidak dikirim.
   - expired push subscription ditandai expired seperti existing due note flow.
7. Run focused push/procurement/reporting tests.
8. Run `make verify`.
9. Commit.
10. Buat handoff.

### Closure Proof Required

- Focused tests pass.
- `make verify` pass.
- Commit hash.
- Handoff file path.

## FC-002 - Dashboard Potensi Uang Kembalian

### Priority

P1

### Problem

Dashboard admin bagian `Kinerja Operasional Bulan Ini` perlu diganti atau ditambah metric untuk potensi uang kembalian.

### Known Facts

- Cash payment detail persisted di `customer_payment_cash_details`.
- Field tersedia:
  - `amount_paid_rupiah`
  - `amount_received_rupiah`
  - `change_rupiah`
- Dashboard operational performance sudah punya chart `Kinerja Operasional Bulan Ini`.
- Belum ada proof bahwa chart/dataset memakai `change_rupiah`.

### Gaps

- Definisi "potensial uang kembalian" belum locked.
- Belum diputus apakah metric adalah:
  - total `change_rupiah` bulan ini,
  - total cash received minus paid,
  - estimasi pecahan cash drawer,
  - atau rekomendasi minimum uang kecil.
- Belum ada dashboard test untuk metric ini.

### Required Decision Before Patch

Pilih salah satu:
- Option A: tampilkan total `change_rupiah` bulan ini sebagai "Potensi Kembalian".
- Option B: tampilkan total dan breakdown pecahan.
- Option C: dashboard hanya tampil total, pecahan ada di kalkulator terpisah.

### Suggested Default Decision

Option C.

Alasan:
- Dashboard cukup memberi indikator.
- Pecahan lebih cocok jadi kalkulator/helper khusus.
- Risiko UI dashboard terlalu ramai lebih kecil.

### Closure Proof Required

- Dataset/read model test.
- Dashboard page test.
- `make verify` pass.
- Commit hash.
- Handoff file path.

## FC-003 - Kalkulator Pecahan Uang Kembalian

### Priority

P1

### Problem

Kasir/admin perlu kalkulator pecahan uang kembalian agar bisa menyiapkan nominal kecil secara praktis.

### Known Facts

- Nilai kembalian sudah dihitung dan persisted.
- Belum ada proof implementation denomination calculator.

### Gaps

- Belum diputus pecahan yang didukung.
- Belum diputus apakah kalkulator berbasis:
  - single transaction change,
  - total harian,
  - total bulanan,
  - atau manual input.
- Belum diputus letak UI:
  - modal pembayaran kasir,
  - dashboard admin,
  - atau halaman laporan kas.

### Suggested Contract

Denomination default:
- 100000
- 50000
- 20000
- 10000
- 5000
- 2000
- 1000
- 500

Calculator harus deterministic:
- input integer rupiah
- output list pecahan dan count
- sisa tidak boleh negatif
- jika sisa tidak bisa dipecah oleh denom minimum, tampilkan remainder

### Suggested Implementation Plan

1. Buat pure service/value calculator kecil.
2. Unit test matrix.
3. Integrasi ke UI setelah pure logic locked.
4. Jika dipakai dashboard, ambil input dari aggregate `change_rupiah`.

### Closure Proof Required

- Unit tests denomination matrix.
- Feature/UI test jika di-render.
- `make verify` pass.
- Commit hash.
- Handoff file path.

## FC-004 - PDF/Cetak Nota/Laporan

### Priority

P2

### Problem

Ada kebutuhan cetak/PDF, tapi belum dibahas kontrak final.

### Known Facts

- Search menemukan PDF pada supplier payment proof attachment.
- Belum ada proof generate PDF nota/laporan.
- Belum ada keputusan library/rendering.

### Gaps

- Belum jelas PDF untuk:
  - nota pelanggan,
  - transaksi/kasus,
  - laporan profit,
  - supplier payable,
  - atau semua.
- Belum jelas output:
  - browser print,
  - generated PDF download,
  - stored PDF artifact,
  - atau template HTML printable.
- Belum jelas library:
  - dompdf/barryvdh,
  - browser print,
  - external renderer.

### Suggested Rule

Jangan mulai PDF sebelum P0 supplier payable notification dan P1 cash change dashboard/kalkulator jelas.

### Closure Proof Required

- Separate blueprint.
- Route/controller/view tests.
- Rendering smoke test.
- `make verify` pass.
- Commit hash.
- Handoff file path.

## FC-005 - UI Refund Label Stash

### Priority

P2

### Problem

Ada stash UI-only:
`Catat Refund / Batalkan Line` menjadi `Refund`.

### Known Facts

- Stash sengaja tidak dicampur ke audit 1461.
- Perubahan ini pernah menyebabkan test false negative karena expected label lama.

### Decision

Deferred.

Jangan pop sebelum ada keputusan UI wording dan update test terkait.

## Handoff Template

Setiap selesai satu kasus, buat file:

`docs/99-archive/handoff/v2/feature-continuation/YYYY-MM-DD-FC-XXX-short-name.md`

Template:

### Handoff FC-XXX - Title

## Metadata

- Branch:
- Start HEAD:
- End HEAD:
- Commit:
- Date:
- Scope:

## Final Decision

## Files Changed

## Tests / Proof

## What Was Closed

## What Was Not Closed

## Known Caveats

## Next Safe Step

## Opening Prompt For Next Session

Lanjutkan dari repo `/home/asyraf/Code/laravel/bengkel2/app`.

State terakhir:
- Branch:
- HEAD:
- Last commit:
- Pending:

Aturan:
- Zero assumption.
- Blueprint first.
- One active step.
- Jangan klaim progress tanpa command output.
- Jalankan snapshot dulu sebelum patch.

