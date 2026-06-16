<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

final class StoreTransactionWorkspaceNoteNormalizer
{
    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public static function normalize(mixed $value): array
    {
        $note = is_array($value) ? $value : [];

        return [
            'customer_name' => self::trimOrNull($note['customer_name'] ?? null),
            'customer_phone' => self::trimOrNull($note['customer_phone'] ?? null),
            'transaction_date' => self::trimOrNull($note['transaction_date'] ?? null),
            'operational_note' => self::trimOrNull($note['operational_note'] ?? null),
        ];
    }

    private static function trimOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
