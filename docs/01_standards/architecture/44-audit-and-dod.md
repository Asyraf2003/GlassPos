# P1 - Audit and DoD

## Tujuan
Menjadikan auditability dan Definition of Done sebagai bagian wajib dari delivery.

## Mandatory Rule
- Perubahan penting harus dapat diaudit.
- Klaim "selesai" harus ditopang oleh verification yang relevan terhadap scope step.
- DoD mengikuti konteks perubahan, tetapi tidak boleh kosong.

## Typical DoD Components
Tergantung konteks, DoD dapat mencakup:
- format/lint
- test
- audit
- sanity check
- inspection file/output

## Proof Rule
Jika menyebut verifikasi:
- sertakan command atau artefak
- sertakan hasil
- sertakan arti hasil terhadap step aktif

## Forbidden Behavior
- Jangan menulis DoD seolah selesai jika baru rencana.
- Jangan menulis verifikasi abstrak tanpa bukti konkret.
