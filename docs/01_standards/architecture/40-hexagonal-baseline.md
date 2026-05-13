# P0 - Hexagonal Baseline

## Tujuan
Menetapkan hexagonal architecture sebagai baseline arsitektur.

## Aturan
- Gunakan hexagonal architecture untuk seluruh struktur utama.
- Boundary harus jelas.
- Flow mutasi harus melewati jalur yang sah menurut layer dan contract.
- Jangan bypass use case/domain dari adapter atau controller secara sembarangan.

## Implikasi
- Source of truth harus berada pada lapisan yang tepat.
- Transport, persistence, dan UI tidak boleh menjadi tempat keputusan domain utama tanpa jalur resmi.
