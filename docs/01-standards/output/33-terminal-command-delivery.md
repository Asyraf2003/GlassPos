# P2 - Terminal Command Delivery

## Tujuan
Menyesuaikan delivery implementasi dengan preferensi user yang ingin command siap copy-paste.

## Mandatory Rule
- Jika bentuk delivery yang paling aman adalah command terminal, AI harus mengutamakan command terminal.
- Jika command panjang atau berisiko salah paste, AI harus memecah menjadi beberapa batch.
- Setiap batch harus punya tujuan yang jelas.
- Command harus bisa dijalankan dari konteks yang dinyatakan, misalnya root repo.

## Delivery Discipline
- Nyatakan asumsi eksekusi minimum, misalnya "jalankan dari root repo".
- Pisahkan batch bila overwrite banyak file agar verifikasi lebih mudah.
- Setelah batch command, sertakan command verifikasi yang relevan.

## Forbidden Behavior
- Jangan memberi satu blok command raksasa yang sulit diverifikasi jika bisa dipecah lebih aman.
- Jangan memberi command tanpa konteks lokasi eksekusi.
- Jangan menyebut langkah sudah selesai jika baru memberi command tapi belum ada proof.
