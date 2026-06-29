# 030 - Locked dependency security advisories remain open

Status: Strict Fixed
Keparahan: High
Klasifikasi: dependency security / supply-chain hygiene

## Ringkasan

`composer.lock` saat ini masih berisi package yang terkena advisory keamanan aktif.

Temuan ini bukan proof exploit lokal terhadap fitur tertentu. Ini adalah boundary security hygiene: dependency yang dipakai aplikasi berada pada rentang versi terdampak menurut `composer audit --locked`.

Karena package terdampak mencakup Laravel framework dan beberapa komponen Symfony inti, issue ini perlu ditutup dengan update dependency terkendali dan verification suite penuh.

## Bukti awal

Command:

`composer audit --locked`

Result:

`Found 12 security vulnerability advisories affecting 8 packages`

Exit code:

`1`

Locked runtime packages yang terdampak:

`composer show --locked --no-dev | rg '^(laravel/framework|symfony/http-foundation|symfony/http-kernel|symfony/mailer|symfony/mime|symfony/polyfill-intl-idn|symfony/routing)\s'`

Output:

- `laravel/framework 12.53.0`
- `symfony/http-foundation 7.4.7`
- `symfony/http-kernel 7.4.7`
- `symfony/mailer 7.4.6`
- `symfony/mime 7.4.7`
- `symfony/polyfill-intl-idn 1.33.0`
- `symfony/routing 7.4.6`

Locked dev package yang juga muncul di audit:

`composer show --locked | rg '^(symfony/yaml)\s'`

Output:

- `symfony/yaml 8.0.6`

## Advisory yang tercatat

| Package | Locked version | Advisory / CVE | Ringkasan |
|---|---:|---|---|
| `laravel/framework` | `12.53.0` | `PKSA-mdq4-51ck-6kdq`, `CVE-2026-48019` | Laravel CRLF injection in default email rule; affected `<12.60.0` for Laravel 12. |
| `symfony/http-foundation` | `7.4.7` | `PKSA-y6py-qpv1-h52p`, `CVE-2026-48736` | SSRF bypass in `NoPrivateNetworkHttpClient`; affected `<7.4.13` for Symfony 7.4. |
| `symfony/http-kernel` | `7.4.7` | `PKSA-dw7n-x7f5-zf63`, `CVE-2026-45075` | HEAD request bypass in method filters; affected `<7.4.12`. |
| `symfony/mailer` | `7.4.6` | `PKSA-28rh-rzzn-djk4`, `CVE-2026-45068` | Sendmail argument injection via dash-prefixed recipient; affected `<7.4.12`. |
| `symfony/mime` | `7.4.7` | `PKSA-wtxr-p26d-nn42`, `CVE-2026-45070` | Email header injection via MIME parameter names; affected `<7.4.12`. |
| `symfony/mime` | `7.4.7` | `PKSA-2n2k-66v2-bwg3`, `CVE-2026-45067` | Email header / SMTP command injection via CRLF in `Address`; affected `<7.4.12`. |
| `symfony/polyfill-intl-idn` | `1.33.0` | `PKSA-dwsq-ppd2-mb1x`, `CVE-2026-46644` | Insecure equivalence in IDN polyfill; affected `<1.38.1`. |
| `symfony/routing` | `7.4.6` | `PKSA-bf7t-jnpz-492k`, `CVE-2026-48784` | URL generation dot-segment encoding issue; affected `<7.4.13`. |
| `symfony/routing` | `7.4.6` | `PKSA-yc7t-91v9-99xs`, `CVE-2026-45065` | URL generation route-requirement bypass; affected `<7.4.12`. |
| `symfony/yaml` | `8.0.6` | `PKSA-v5yj-8nmz-sk2q`, `CVE-2026-45304` | YAML recursive collection alias memory allocation issue; affected `<8.0.12`. |
| `symfony/yaml` | `8.0.6` | `PKSA-ft77-7h5f-p3r6`, `CVE-2026-45305` | YAML parser ReDoS; affected `<8.0.12`. |
| `symfony/yaml` | `8.0.6` | `PKSA-b14r-zh1d-vdrc`, `CVE-2026-45133` | YAML parser stack exhaustion; affected `<8.0.12`. |

## Dampak

Risiko utama adalah aplikasi membawa dependency yang sudah diketahui rentan. Dampak spesifik bergantung pada apakah aplikasi memakai jalur yang terdampak, misalnya email validation, email address construction, URL generation, HTTP client network filtering, atau YAML parsing.

Meski exploit lokal belum dibuktikan, dependency advisories tetap harus dibaca sebagai blocker security hygiene sebelum klaim project production-ready.

## Root cause

`composer.lock` belum diperbarui ke versi patch yang keluar setelah advisory tersebut.

## Kontrol yang sudah ada

- `make verify` lulus pada baseline saat audit.
- Full Pest suite lulus pada baseline saat audit.
- Tidak ada proof lokal bahwa advisory ini sudah dieksploitasi pada fitur aplikasi.

## Remediasi yang disarankan

Lakukan update dependency terkendali, minimal menutup rentang affected versions yang tercatat oleh audit.

Candidate command untuk fase patch:

`composer update laravel/framework symfony/http-foundation symfony/http-kernel symfony/mailer symfony/mime symfony/polyfill-intl-idn symfony/routing symfony/yaml --with-all-dependencies`

Setelah update, ulangi:

- `composer audit --locked`
- `composer check-platform-reqs --lock`
- `make verify`
- Focused smoke untuk login, email/validation path, report export, dan route generation bila ada perubahan transitive signifikan.

## Keputusan owner yang mungkin dibutuhkan

- Apakah update dependency boleh langsung dilakukan pada branch sekarang.
- Apakah `symfony/yaml` dev-only tetap harus ditutup dalam patch yang sama atau boleh dipisah.
- Apakah production PHP target tetap mengikuti `composer.json` atau perlu dibakukan ulang setelah dependency update.

## Verification gap

Tidak ada verification gap tersisa untuk scope 0030 setelah dependency update, clean Composer audit, platform check, dan full verification gate.

## Patch Proof

Command:

```text
composer update \
  laravel/framework \
  symfony/http-foundation \
  symfony/http-kernel \
  symfony/mailer \
  symfony/mime \
  symfony/polyfill-intl-idn \
  symfony/routing \
  symfony/yaml \
  --with-all-dependencies
```

Result:

```text
lock file operations: 0 installs, 44 updates, 0 removals;
laravel/framework upgraded from 12.53.0 to 12.61.1;
symfony/http-foundation upgraded from 7.4.7 to 7.4.13;
symfony/http-kernel upgraded from 7.4.7 to 7.4.13;
symfony/mailer upgraded from 7.4.6 to 7.4.12;
symfony/mime upgraded from 7.4.7 to 7.4.13;
symfony/polyfill-intl-idn upgraded from 1.33.0 to 1.38.1;
symfony/routing upgraded from 7.4.6 to 7.4.13;
symfony/yaml upgraded from 8.0.6 to 8.1.0;
Composer completed package discovery;
Composer reported No security vulnerability advisories found.
```

## Post-Patch Audit Proof

Command:

```text
composer audit --locked
```

Result:

```text
No security vulnerability advisories found.
```

Meaning:

the previously reported dependency advisories are no longer present in the locked dependency set.

## Platform Proof

Command:

```text
composer check-platform-reqs --lock
```

Result:

```text
Composer platform requirement check returned success for PHP, Composer APIs, and required PHP extensions.
```

Meaning:

the updated lock file remains compatible with the current local PHP platform.

## Full Verification Proof

Command:

```text
make verify
```

Result:

```text
local operator reported make verify green after dependency update.
```

Meaning:

dependency update did not break the repository verification gate in the local environment.

## Strict Closure Decision

0030 ditutup sebagai Strict Fixed.

Dasar closure:

dependency update executed successfully;
vulnerable locked package versions were upgraded beyond the affected ranges recorded in this log;
composer audit --locked now reports no security vulnerability advisories;
composer check-platform-reqs --lock passes;
make verify reported green after the dependency update.
