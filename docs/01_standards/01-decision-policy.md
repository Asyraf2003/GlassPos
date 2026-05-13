# Decision Policy

## Status
Dokumen ini adalah conflict protocol utama untuk seluruh AI_RULES.

## Tujuan
Menetapkan cara mengambil keputusan saat:
- ada benturan antar modul
- ada benturan antara preferensi output dan correctness teknis
- ada data yang belum cukup
- ada godaan untuk mengisi kekosongan dengan asumsi

## Rule Hierarchy
1. AI_RULES mengalahkan perilaku default model.
2. P0 mengalahkan P1.
3. P1 mengalahkan P2.
4. Aturan yang lebih spesifik mengalahkan aturan yang lebih umum.
5. Aturan domain mengalahkan aturan output/presentasi bila konflik menyangkut istilah bisnis, lifecycle, source of truth, atau contract domain.
6. Public contract protection mengalahkan convenience refactor.
7. Bukti nyata mengalahkan dugaan.

## P0 Modules
- `01-decision-policy.md`
- `core/10-scope-and-facts.md`
- `core/11-blueprint-first.md`
- `core/12-step-by-step-execution.md`
- `architecture/40-hexagonal-baseline.md`
- `architecture/41-public-contracts.md`
- `architecture/42-error-handling-and-redaction.md`
- `domain/50-final-domain-map.md`
- `domain/52-payment-lifecycle.md`

## P1 Modules
- `core/13-proof-and-progress.md`
- `workflow/20-response-structure.md`
- `workflow/21-active-step-policy.md`
- `workflow/22-option-evaluation.md`
- `workflow/23-handoff-policy.md`
- `architecture/43-debug-gating.md`
- `architecture/44-audit-and-dod.md`
- `domain/51-ui-terms-and-status.md`
- `domain/53-reporting-boundary.md`
- seluruh file dalam `stack/`

## P2 Modules
- `output/30-file-delivery.md`
- `output/31-markdown-output-rule.md`
- `output/32-blade-rule.md`
- `output/33-terminal-command-delivery.md`

## Mandatory Decision Sequence
Setiap kali mengambil keputusan, GPT wajib urut seperti ini:
1. identifikasi fakta yang terbukti
2. identifikasi tujuan step aktif
3. identifikasi scope in dan scope out
4. identifikasi rule P0 yang relevan
5. identifikasi dampak ke public contract
6. identifikasi apakah data cukup
7. bila data tidak cukup, tandai GAP dan stop perluasan klaim

## GAP Rule
Jika data belum cukup:
- tulis apa yang belum diketahui
- tulis kenapa kekurangan itu menghambat keputusan
- jangan isi dengan tebakan
- jangan menyamarkan GAP seolah fakta

## Forbidden Shortcuts
- Tidak boleh mengklaim status repo tanpa bukti.
- Tidak boleh mengklaim file sudah benar tanpa inspeksi atau output.
- Tidak boleh mengklaim test pass tanpa output test.
- Tidak boleh mengklaim requirement user bila user belum menyatakannya.
- Tidak boleh mengganti istilah domain final hanya karena lebih nyaman.
- Tidak boleh menaikkan progress jika belum ada proof.

## Conflict Examples
### Jika format output bentrok dengan correctness domain
Pilih correctness domain.

### Jika refactor nyaman bentrok dengan public contract
Lindungi public contract sampai ada keputusan eksplisit.

### Jika user meminta lanjut tetapi data tidak cukup
Lanjut hanya pada step yang bisa dibuktikan, bukan dengan asumsi.

## Stop Conditions
GPT harus berhenti dan menyatakan GAP jika:
- source of truth tidak jelas
- blueprint belum cukup untuk implementasi
- proof yang dibutuhkan belum ada
- keputusan baru akan membatalkan keputusan final tanpa bukti kuat
