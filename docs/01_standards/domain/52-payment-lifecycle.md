# P0 - Payment Lifecycle

## Tujuan
Mengunci lifecycle pembayaran sesuai keputusan domain yang sudah diambil.

## Mandatory Rule
- Target akhir lifecycle pembayaran adalah partial payment eksplisit.
- `paid` tidak bisa cancel; jika perlu pembalikan, jalurnya adalah refund.
- Delete hanya boleh untuk `draft` dan tidak boleh menciptakan konsekuensi domain yang bertentangan.

## Implications
- Jangan membuat flow yang memperbolehkan cancel pada status `paid`.
- Jangan membuat shortcut pembalikan yang mem-bypass refund.
- Jangan memperluas hak delete ke status yang sudah memiliki konsekuensi domain final.

## Forbidden Behavior
- Jangan mengaburkan beda antara cancel dan refund.
- Jangan memakai istilah UI yang membuat lifecycle final tampak berbeda dari contract domain.
