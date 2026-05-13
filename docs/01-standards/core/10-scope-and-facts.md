# P0 - Scope and Facts

## Tujuan
Memastikan semua respons kerja memisahkan fakta, gap, keputusan, dan batas kerja dengan tegas.

## Mandatory Classification
Setiap respons kerja harus membedakan minimal:
- FACT
- SCOPE-IN
- SCOPE-OUT
- GAP
- DECISION
- PROOF
- NEXT

## Definitions
### FACT
Informasi yang didukung oleh:
- file yang terlihat
- output command
- dokumen/ADR/handoff yang eksplisit
- requirement user yang tertulis jelas

### GAP
Informasi penting yang belum tersedia dan mempengaruhi kualitas keputusan.

### DECISION
Pilihan yang sengaja diambil berdasarkan fakta, tujuan step, dan rules.

### PROOF
Artefak yang membuktikan status saat ini, misalnya:
- output command
- isi file
- hasil test
- hasil verifikasi

## Mandatory Behavior
- Sebelum memberi langkah, sebutkan kondisi saat ini dan tujuan step.
- Sebelum menyimpulkan, pastikan ada proof.
- Jika ada bagian yang belum diketahui, tandai sebagai GAP.
- Jangan memperlakukan kebiasaan umum sebagai fakta project.

## Scope Rule
### SCOPE-IN
Hanya area yang sedang aktif dikerjakan pada step saat ini.

### SCOPE-OUT
Area yang sengaja tidak disentuh walau terkait secara umum.

## Forbidden Behavior
- Jangan mengarang state aplikasi.
- Jangan mengarang isi file yang belum dilihat.
- Jangan mengarang hasil verifikasi.
- Jangan memperluas scope diam-diam.
- Jangan menyamakan inference dengan fact.

## Inference Rule
Inference boleh dipakai hanya jika:
- basis faktanya jelas
- disebut eksplisit sebagai inference
- tidak dipresentasikan sebagai fakta final
