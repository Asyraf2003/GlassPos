# CATATAN HANDOFF — KASIR BENGKEL — MASUK STEP 3

## Status Umum
- Domain inti dan keputusan besar **sudah final**.
- Jangan buka ulang diskusi domain kecuali ada konflik nyata.
- **Blueprint Induk**, **Workflow Induk**, **DoD Induk**, dan **ADR-001 s.d. ADR-012** sudah final.

## Status Step Sebelumnya
- **Step 2:** selesai **100%**
- Skeleton hexagonal sudah hidup
- Baseline testing sudah hidup
- Baseline audit-hex sudah hidup
- Makefile baseline sudah hidup
- Ada vertical slice `/health` yang pass end-to-end

## Keputusan Implementasi yang Sudah Terkunci
- `AppServiceProvider` dipisah dari `HexagonalServiceProvider`
- `NullCapabilityPolicyAdapter` default `false`
- `Money` integer-only
- `ensureNotNegative()` eksplisit
- `TransactionManagerPort` memakai `begin / commit / rollBack`, bukan callable closure

## Validasi yang Sudah Pass
- `php scripts/audit-hex.php` => **OK**
- `php artisan test` => **PASS**
- `make check` => **PASS**

## Target Chat Ini
Masuk ke **Workflow Step 3** secara aman.

## Batas Scope Chat Ini
- Fokus hanya fondasi **domain master paling dasar**
- Jangan lompat ke transaksi penuh
- Jangan masuk stock movement penuh
- Jangan masuk costing penuh
- Jangan masuk reporting penuh

## Urutan yang Diizinkan untuk Step 3
1. Product entity / value object minimum
2. Product repository port
3. Product application use case minimum
4. Adapter persistence placeholder
5. Test baseline master product

## Aturan Kerja
- zero assumption
- blueprint dulu untuk step ini
- lalu workflow step-by-step
- satu langkah per balasan
- tunggu feedback sebelum lanjut
