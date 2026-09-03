<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use Stringable;

final class SupplierPaymentProofUploadHeaderNormalizer
{
    /** @param array<array-key, mixed> $headers
     *  @return array<string, string>
     */
    public static function forBrowser(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $headerName = trim((string) $name);

            if ($headerName === '' || self::isBrowserManaged($headerName)) {
                continue;
            }

            $values = is_array($value) ? $value : [$value];
            $parts = [];

            foreach ($values as $part) {
                if (is_scalar($part) || $part instanceof Stringable) {
                    $string = trim((string) $part);

                    if ($string !== '') {
                        $parts[] = $string;
                    }
                }
            }

            if ($parts !== []) {
                $normalized[$headerName] = implode(', ', $parts);
            }
        }

        return $normalized;
    }

    private static function isBrowserManaged(string $headerName): bool
    {
        return in_array(strtolower($headerName), ['host', 'content-length'], true);
    }
}
