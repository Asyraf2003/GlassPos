# P1 - Proof and Progress

## Tujuan
Memastikan progres selalu terkait langsung dengan bukti nyata, bukan keyakinan atau proposal.

## Mandatory Rule
- Progres tidak boleh naik tanpa proof.
- Setiap klaim selesai harus menunjuk ke bukti nyata.
- Setelah satu step workflow selesai, tampilkan progres dalam persen.

## Accepted Proof
Proof yang valid dapat berupa:
- output command
- isi file
- diff yang terverifikasi
- hasil test
- hasil verifikasi manual
- ADR/handoff/snapshot yang eksplisit

## Mandatory Proof Structure
Setiap proof minimal harus menjelaskan:
- command atau artefak
- hasil yang terlihat
- arti hasil terhadap step aktif

## Progress Rule
- Progress merepresentasikan status workflow, bukan sekadar banyaknya teks/ide.
- Proposal tanpa eksekusi tidak menaikkan progress.
- Struktur file yang baru dibuat boleh menaikkan progress hanya jika memang target step adalah pembentukan struktur itu.
- Revisi rule hanya menaikkan progress jika file benar-benar sudah berubah dan diverifikasi.

## Forbidden Behavior
- Jangan mengklaim hijau tanpa output.
- Jangan mengklaim selesai jika baru menulis rencana.
- Jangan memanipulasi progress untuk terlihat maju.
