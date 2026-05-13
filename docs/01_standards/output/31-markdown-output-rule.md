# P2 - Markdown Output Rule (Revised)

## Tujuan
Mengunci format khusus saat AI menulis file markdown agar konsisten dengan preferensi user dan memastikan kebersihan kode tanpa karakter yang tidak perlu.

## Mandatory Rule for .md
Jika AI menulis file markdown:
- Output harus berupa FULL contents dari path file.
- Output harus menggunakan SATU code block saja sebagai kontainer utama.
- Outer fence (pembungkus paling luar) wajib menggunakan triple backticks dengan bahasa text.
- Triple backticks (```) HANYA boleh muncul di baris pertama dan baris terakhir dari seluruh pesan sebagai pembungkus copy-paste.
- Tidak boleh ada teks, penjelasan, atau salam pembuka/penutup di luar code block tersebut.
- Jika ada kebutuhan untuk menampilkan blok kode di dalam isi markdown, gunakan alternatif format seperti indentasi 4 spasi atau blok kutipan, untuk menghindari penggunaan triple backticks atau tilde (~~~) yang dapat merusak struktur kontainer luar.

## Scope of Rule
- Aturan ini berlaku khusus saat menulis atau menyajikan file .md untuk disalin user.
- Aturan ini tidak berlaku untuk percakapan diskusi biasa.

## Forbidden Behavior
- DILARANG menyertakan karakter ASCII dekoratif atau simbol non-standar.
- DILARANG menyertakan blok kode Bash (shell script) di dalam konten. Jika harus ada instruksi perintah, gunakan teks biasa tanpa dekorasi kode.
- DILARANG keras menulis penjelasan di luar code block utama saat mengirimkan file markdown.
- DILARANG menggunakan triple backticks di dalam konten markdown; cari metode formatting alternatif agar tidak terjadi breaking pada container luar.
