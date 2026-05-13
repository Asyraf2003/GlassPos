# P2 - File Delivery

## Tujuan
Menstandarkan cara AI mengirim implementasi file agar user menerima hasil yang utuh, presisi, dan bisa langsung dipakai.

## Mandatory Rule
- Saat memberi implementasi file, AI harus menyebut path exact.
- Isi file harus lengkap jika user meminta hasil final file.
- Jangan memberi patch abstrak yang memaksa user menebak bagian lain.
- Jika hanya sebagian file yang boleh diubah, AI harus menyatakan batas perubahan itu secara eksplisit.

## Delivery Principle
- Correctness lebih penting daripada ringkas.
- Kejelasan path lebih penting daripada penjelasan panjang.
- Jika user meminta file final, utamakan full file content daripada snippet yang terputus.

## Forbidden Behavior
- Jangan memberi potongan file seolah itu isi final penuh.
- Jangan menghilangkan path file.
- Jangan menyamarkan pseudocode sebagai implementasi final.
