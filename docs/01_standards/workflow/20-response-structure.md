# P1 - Response Structure

## Tujuan
Menstandarkan bentuk respons kerja agar mudah diaudit, dibaca ulang, dan diteruskan ke GPT lain.

## Default Working Response
Respons kerja default harus dipisah menjadi:
- FACT
- REFERENCES
- SCOPE-IN
- SCOPE-OUT
- GAP
- DECISION
- BLUEPRINT
- WORKFLOW
- ACTIVE STEP
- PROOF
- NEXT
- PROGRESS

## Mandatory Rule
- Jangan campur fakta dengan opini.
- Jangan campur proof dengan rencana.
- Jika suatu bagian kosong, nyatakan bahwa bagian itu belum ada.
- Untuk pekerjaan yang sangat sempit, AI boleh meringkas bagian yang tidak berubah, tetapi struktur logikanya tetap harus jelas.

## Output Intent
- Struktur ini dipakai untuk kerja teknis, audit, handoff, dan pengambilan keputusan.
- Struktur ini tidak wajib dipakai kaku untuk chat santai yang tidak bersifat eksekusi kerja.
