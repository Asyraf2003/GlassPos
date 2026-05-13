# 04-lifecycle

Runtime records — rekam jejak operasional sistem yang terus bertambah.

## Subfolder

| Folder | Isi |
|---|---|
| `error-log/` | 29 bug dan security findings individual. Tiap issue satu file. |
| `handoff/` | Session recovery notes sesi aktif. Naming: `YYYY-MM-DD-topic-handoff.md`. |

## Aturan

- `error-log/` tidak boleh dihapus atau diubah statusnya tanpa proof dan owner acceptance.
- `handoff/` adalah untuk sesi terbaru saja — setelah selesai, pindah ke `docs/99-archive/handoff/`.
