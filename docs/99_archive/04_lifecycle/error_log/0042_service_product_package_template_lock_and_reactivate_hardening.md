# 0042 Service Product Package Template Lock and Reactivate Hardening

## Status

Selesai dan verified.

Final verification:

- make verify: PASS
- Pest: 1367 passed, 8119 assertions
- Duration: 72.01s

## Scope

Hardening Service Product Template dan Service x Product Package flow.

Area yang disentuh:

- Admin service product template reactivate guard.
- Cashier service store stock package auto split template lock.
- Create workspace package exact-match validation.
- Edit/revision workspace package exact-match validation.
- Revision failure error key compatibility.
- File split untuk menjaga audit-lines limit 100 baris.

## Findings

Audit menemukan beberapa gap:

1. Admin reactivate hanya mengecek duplicate active template, belum mengecek ulang apakah product masih tersedia dan service masih active.
2. Package template lock backend sebelumnya terlalu longgar karena cukup Product 1 punya active template.
3. Hidden payload package bisa berisiko tidak cocok dengan template aktif jika backend tidak melakukan exact-match.
4. Edit/revision package auto split perlu mempertahankan template-lock behavior tanpa merusak revision settlement, stock reversal, dan payment allocation.
5. File package template rules melewati audit-lines limit setelah exact-match logic ditambahkan, sehingga perlu dipecah.

## Fix Summary

Perbaikan yang dilakukan:

- Reactivate admin template sekarang menolak template stale jika:
  - Product 1 sudah soft-deleted.
  - Service sudah inactive.
- Package template validation diperketat agar payload harus sesuai template aktif.
- Product lines package diverifikasi terhadap template aktif.
- Service payload package diverifikasi terhadap template aktif.
- Revision error key compatibility dikembalikan agar error revision existing tetap memakai key `revision`.
- Logic exact-match dipecah ke class guard terpisah agar file tetap sesuai audit-lines.

## Tests

Focused dan full verification:

- Admin service product template management test.
- Service product template package lookup reader test.
- Cashier package lookup service product template test.
- Cashier workspace service product template minimum contract test.
- Create transaction workspace service store stock feature test.
- Edit transaction workspace package auto split characterization test.
- Revision/payment/backdated price regression.
- Full make verify.

Final result:

```text
Tests: 1367 passed (8119 assertions)
Duration: 72.01s
```

## Non-Scope Confirmed

Tidak mengubah:

- Mobile API.
- Refund policy.
- Supplier invoice payment proof.
- Operational Profit formula.
- Report Excel formula.
- Payment allocation policy selain menjaga compatibility error handling revision.

## Remaining Notes

Refund mechanics hanya diverifikasi dalam konteks package/service_store_stock/product_lines yang sudah ada. Tidak ada perubahan policy refund.

Operational Profit tetap untouched.
