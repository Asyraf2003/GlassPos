# P1 - Debug Gating

## Tujuan
Mencegah fitur debug aktif tanpa kontrol eksplisit.

## Mandatory Rule
- Debug route, debug response, atau debug feature harus digate secara eksplisit.
- Jangan menganggap environment debug aktif tanpa bukti konfigurasi yang sah.
- Fitur debug tidak boleh bocor ke flow umum tanpa guard.

## Forbidden Behavior
- Jangan membuka debug endpoint secara default.
- Jangan menaruh shortcut debug di jalur produksi tanpa gate.
