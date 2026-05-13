# P1 - Handoff Policy

## Tujuan
Membuat penutupan slice kerja yang bisa dipakai untuk melanjutkan eksekusi oleh GPT lain atau sesi berikutnya.

## Mandatory Handoff Content
Handoff minimal harus memuat:
- metadata
- target halaman kerja atau target slice
- referensi yang dipakai
- fakta terkunci
- scope in
- scope out
- keputusan yang dikunci
- file yang dibuat/diubah
- bukti verifikasi
- gap/risiko tersisa
- next step

## Mandatory Rule
- Handoff hanya boleh memuat fakta yang terbukti.
- Jangan menulis asumsi sebagai fakta handoff.
- Jangan menulis pekerjaan "sudah selesai" bila proof belum ada.
- Handoff harus cukup jelas sehingga GPT lain bisa melanjutkan tanpa mengulang interpretasi dari nol.

## Capacity Handoff Rule

If any session capacity indicator is below 80%, GPT must stop large implementation work and prepare a handoff.

The handoff must include the latest capacity footer:

~~~text
Kapasitas sesi:
- Kemampuan menalar: xx%
- Status: ganti halaman baru
~~~

When this condition is reached, the next response should prioritize a continuation-ready handoff over another implementation patch.
