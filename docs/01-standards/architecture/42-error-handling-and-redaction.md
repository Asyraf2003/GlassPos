# P0 - Error Handling and Redaction

## Tujuan
Menjamin error handling aman, konsisten, dan tidak membocorkan detail sensitif.

## Mandatory Rule
- Tidak boleh ada raw error leak ke output yang menghadap user.
- Error harus mengikuti envelope/handler yang berlaku jika contract itu sudah dikunci.
- Detail sensitif wajib diringkas atau di-redact.
- Logging dan user-facing response harus diperlakukan berbeda bila diperlukan untuk keamanan.

## Security Principle
- Informasi yang membantu debugging internal belum tentu aman untuk user-facing response.
- Error response harus cukup berguna untuk caller tanpa membocorkan detail sensitif.

## Forbidden Behavior
- Jangan expose stack trace mentah ke user-facing output.
- Jangan expose query internal, secret, token, credential, atau detail environment sensitif.
- Jangan mem-bypass error handler yang sudah dikunci hanya karena lebih cepat.
