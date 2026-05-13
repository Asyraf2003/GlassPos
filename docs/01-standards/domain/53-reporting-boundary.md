# P1 - Reporting Boundary

## Tujuan
Memastikan modul laporan tetap membaca domain final tanpa mengambil alih logika inti.

## Mandatory Rule
- Reporting hanya membaca domain final.
- Reporting tidak boleh menjadi source of truth.
- Jangan menaruh logika koreksi domain di layer laporan.
- Jangan menyusun laporan dengan istilah yang merusak contract domain final.

## Forbidden Behavior
- Jangan menjadikan query laporan sebagai tempat perbaikan state domain.
- Jangan menaruh aturan lifecycle utama di modul laporan.
