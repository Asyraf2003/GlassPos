<?php

declare(strict_types=1);

namespace App\Support;

final class NoteStatusBadgeFormatter
{
    public static function label(mixed $label): string
    {
        $text = trim((string) ($label ?? '-'));

        return $text !== '' ? $text : '-';
    }

    public static function tone(mixed $label, mixed $tone = null): mixed
    {
        $key = strtolower(str_replace(['_', '-'], ' ', trim((string) ($label ?? '-'))));

        return $tone ?? match (true) {
            str_contains($key, 'cancel'),
            str_contains($key, 'batal'),
            str_contains($key, 'refund'),
            str_contains($key, 'kembali') => 'danger',
            str_contains($key, 'close'),
            str_contains($key, 'lunas'),
            str_contains($key, 'paid'),
            str_contains($key, 'selesai') => 'success',
            default => 'info',
        };
    }
}
