# 023 - Public helper can expose private storage

## Status

Fixed and locally verified for repository-tracked public helper exposure.

## Severity

High.

## Ringkasan

File `public/a.php` adalah helper publik yang dapat dieksekusi langsung dari document root, di luar Laravel routing, middleware, authentication, authorization, dan CSRF protection.

File tersebut memakai token GET hardcoded, lalu mencoba membuat symlink `public/storage` menuju private storage path. Jika helper tetap ter-deploy, token diketahui, target path ada, `symlink()` aktif, dan web server mengikuti symlink, file private dapat menjadi static file yang bisa diakses langsung dari `/storage/`.

Risiko utama adalah exposure file private seperti supplier payment proof atau upload sensitif lain yang semestinya hanya diakses lewat route Laravel yang memakai auth/admin middleware.

## Jalur rentan

Unauthenticated HTTP request
-> akses langsung `public/a.php?token=...`
-> file berjalan di luar Laravel middleware
-> token GET hardcoded cocok
-> script membuat symlink `public/storage`
-> symlink menunjuk ke private storage target
-> web server menyajikan isi target sebagai static files
-> file private bisa diakses lewat `/storage/...`

## Root cause

Temporary helper ditempatkan di `public/` dan bergantung pada instruksi manual “hapus setelah dipakai”.

Kontrolnya hanya token hardcoded yang ikut committed ke source. Ini bukan security boundary yang layak karena token dapat bocor melalui repository, log, chat, deploy artifact, atau akses source.

## Dampak

Successful exploitation dapat mengekspos file private dan dokumen sensitif.

Contoh kelas data terdampak dari laporan:

- supplier payment proof uploads
- file di Laravel local private disk
- upload private lain yang berada di bawah target symlink

Selain disclosure, script juga membuat perubahan persistent di document root dengan membuat symlink publik.

## Patch Summary

`public/a.php` dihapus sepenuhnya.

Patch commit yang dilaporkan:

`4c90af5 - Remove public symlink helper endpoint`

PR metadata dibuat dengan judul:

`Remove publicly accessible storage-link helper script`

## Verification

Reported proof:

- `sed -n '1,200p' public/a.php`
- `git rm public/a.php && git status --short`
- `git commit -m "Remove public symlink helper endpoint"`
- `git status --short`

Reported final state:

Working tree clean after commit.

## Verification Notes

Patch berupa penghapusan file public helper. Ini cukup untuk menutup endpoint langsung selama deploy benar-benar memakai commit yang sudah menghapus file tersebut dan tidak ada copy helper tersisa di document root/server.

## Local Verification Update - 2026-05-10

Current source reality supersedes the earlier reported proof.

Repository proof:

- HEAD: `04382df9`
- `public/a.php` absent in HEAD
- `public/a.php` absent in worktree
- `public/storage` absent in HEAD
- local worktree `public/storage` symlink target observed as `/home/asyraf/Code/laravel/bengkel2/app/storage/app/public`
- no local proof showed `public/storage` pointing to private storage
- working tree clean after source deletion landed in HEAD/origin

Classification:

Fixed for repository-tracked public helper exposure.

Remaining boundary:

Deployment/runtime cleanup is not proven by repository proof alone and remains a deployment verification gap until production/staging document root proof is provided.

## Residual / Deployment Check

Perlu cek deploy/runtime di luar repo:

- pastikan `public/a.php` tidak ada di server produksi/staging
- pastikan tidak ada helper serupa di `public/`
- pastikan `public/storage` tidak menunjuk ke private storage
- pastikan private upload tetap disajikan lewat route Laravel ber-auth, bukan static symlink
- pastikan web server tidak menyajikan private storage path langsung

## Relations

No direct relation to prior note/payment/refund error-log files.

This starts a separate public helper / private storage exposure cluster.
