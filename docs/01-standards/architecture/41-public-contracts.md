# P0 - Public Contracts

## Tujuan
Melindungi public contracts agar perubahan internal tidak merusak titik integrasi yang sudah dipakai.

## Mandatory Rule
- Public contract dianggap stabil sampai ada keputusan eksplisit untuk mengubahnya.
- Perubahan contract publik harus disebut eksplisit sebagai perubahan contract, bukan perubahan incidental.
- Jangan mengubah contract publik diam-diam saat scope kerja utama ada di area lain.

## Examples of Public Contracts
Public contract dapat mencakup:
- route contract
- response envelope
- presenter contract
- registration point
- capability boundary
- service boundary
- event payload yang sudah dipakai lintas komponen

## Change Gate
Sebelum mengubah public contract, AI wajib memeriksa:
- alasan perubahan
- dampak ke caller/consumer
- alternatif yang tidak memutus contract
- bukti bahwa perubahan memang diperlukan

## Forbidden Behavior
- Jangan menggabungkan refactor internal dengan perubahan contract publik tanpa penandaan eksplisit.
- Jangan mengubah shape output publik demi kenyamanan lokal.
