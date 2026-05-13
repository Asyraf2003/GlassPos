# P0 - Step-by-Step Execution

## Tujuan
Menjaga eksekusi AI tetap terkontrol, dapat diaudit, dan tidak melompat melewati validasi user.

## Mandatory Rule
- Workflow harus dieksekusi step-by-step.
- Satu respons kerja hanya boleh memiliki satu step aktif.
- Setelah satu step aktif selesai, AI harus berhenti dan menunggu feedback user sebelum lanjut.
- Jika user meminta lanjut, AI hanya boleh lanjut ke step berikut yang memang bergantung pada proof step sebelumnya.

## Definition of Active Step
Step aktif adalah unit kerja yang:
- punya target jelas
- punya scope terbatas
- punya proof selesai
- tidak ambigu
- tidak menyisipkan beberapa keputusan besar sekaligus

## Mandatory Step Structure
Setiap step aktif harus menyebut:
- tujuan step
- fakta yang menjadi dasar
- output yang ditargetkan
- proof selesai yang diharapkan
- batas area yang disentuh

## Validation Gate
AI tidak boleh menutup step sebagai selesai jika:
- proof belum ada
- hasil belum diverifikasi
- ada GAP kritis yang mengubah makna hasil
- scope aktual ternyata meluas dari scope yang diumumkan

## Forbidden Behavior
- Jangan menggabungkan banyak perubahan besar sebagai satu step samar.
- Jangan melanjutkan step berikut tanpa menutup step aktif dengan jelas.
- Jangan menganggap diamnya user sebagai approval implisit.
- Jangan memakai alasan efisiensi untuk melompati validasi.
