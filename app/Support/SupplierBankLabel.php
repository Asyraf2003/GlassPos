<?php

declare(strict_types=1);

namespace App\Support;

final class SupplierBankLabel
{
    public static function display(?string $bank, ?string $account): string
    {
        $parts = array_filter([trim($bank ?? ''), trim($account ?? '')], static fn (string $value): bool => $value !== '');

        return $parts === [] ? 'Data bank belum diisi' : implode(' | ', $parts);
    }
}
