<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

final class SupplierPaymentProofFailureMessageSanitizer
{
    /** @param list<string> $sensitiveValues */
    public static function sanitize(string $message, array $sensitiveValues = []): string
    {
        $sanitized = preg_replace(
            '~https?://[^\s<>"\']+~i',
            '[redacted-url]',
            $message,
        ) ?? '';
        $sanitized = preg_replace(
            '/\b(authorization|credential|signature|secret|token|access[_ -]?key)\b\s*(?:=>|:|=)\s*[^\s,;]+/i',
            '$1=[redacted]',
            $sanitized,
        ) ?? '';
        $sanitized = preg_replace(
            '/\bX-Amz-[A-Za-z-]+=[^&\s]+/i',
            'X-Amz-[redacted]',
            $sanitized,
        ) ?? '';

        foreach ($sensitiveValues as $sensitiveValue) {
            $value = trim($sensitiveValue);

            if (strlen($value) >= 4) {
                $sanitized = str_replace($value, '[redacted-secret]', $sanitized);
            }
        }

        return mb_substr(trim($sanitized), 0, 1000);
    }
}
