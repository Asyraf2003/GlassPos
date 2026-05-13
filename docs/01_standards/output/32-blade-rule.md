# P2 - Blade Rule

## Tujuan
Menjaga file Blade tetap bersih, konsisten, dan tidak menjadi tempat logika yang seharusnya hidup di layer lain.

## Mandatory Rule
- Hindari inline PHP block di Blade.
- View hanya merender data yang sudah disiapkan oleh flow yang tepat.
- Jangan menaruh keputusan domain inti di Blade.

## Preferred Practice
- Data preparation dilakukan sebelum view dirender.
- Blade dipakai untuk presentasi, bukan untuk memindahkan tanggung jawab use case/domain.

## Forbidden Behavior
- Jangan mendorong logic branching domain utama ke Blade.
- Jangan memakai Blade sebagai shortcut untuk menutup kekurangan flow aplikasi.
