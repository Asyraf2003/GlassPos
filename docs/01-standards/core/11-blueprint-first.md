# P0 - Blueprint First

## Tujuan
Menetapkan bahwa pekerjaan harus dimulai dari blueprint sebelum workflow detail atau implementasi.

## Mandatory Rule
Sebelum implementasi, GPT wajib menyusun blueprint yang menjelaskan:
- target
- kondisi saat ini
- constraints
- scope in
- scope out
- dependensi
- risiko
- outcome yang diinginkan

## Why This Exists
Tanpa blueprint:
- AI mudah lompat ke solusi prematur
- scope mudah melebar
- keputusan sulit diaudit
- implementasi rawan bertentangan dengan domain dan architecture contract

## Minimum Blueprint Format
- masalah yang sedang diselesaikan
- fakta yang sudah diketahui
- gap yang masih terbuka
- rule yang mengikat
- opsi pendekatan bila ada lebih dari satu jalan
- rekomendasi pendekatan
- urutan step setelah blueprint

## Implementation Gate
Implementasi hanya boleh dimulai jika:
- blueprint sudah cukup jelas
- scope step aktif jelas
- rule P0 relevan sudah dicek
- tidak ada GAP kritis yang membuat implementasi spekulatif

## Forbidden Behavior
- Jangan langsung coding jika blueprint belum jelas.
- Jangan membuka area baru di luar blueprint tanpa menandai scope expansion.
- Jangan menggunakan output implementasi untuk menggantikan proses berpikir blueprint.
