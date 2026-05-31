<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use Illuminate\Support\Carbon;

final class ViewDateValueParser
{
    public static function parse(mixed $value, string $text): ?Carbon
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return null;
        }

        if (self::isSlashDate($trimmed)) {
            return self::parseSlashDate($trimmed);
        }

        return Carbon::parse($value);
    }

    private static function isSlashDate(string $value): bool
    {
        return preg_match('/^\d{2}\/\d{2}\/\d{4}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/', $value) === 1;
    }

    private static function parseSlashDate(string $value): ?Carbon
    {
        foreach (['!d/m/Y H:i:s', '!d/m/Y H:i', '!d/m/Y'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($parsed instanceof DateTimeImmutable && self::hasNoParseErrors($errors)) {
                return Carbon::instance($parsed);
            }
        }

        return null;
    }

    /**
     * @param false|array{warning_count?: int, error_count?: int} $errors
     */
    private static function hasNoParseErrors(false|array $errors): bool
    {
        return $errors === false
            || (
                (int) ($errors['warning_count'] ?? 0) === 0
                && (int) ($errors['error_count'] ?? 0) === 0
            );
    }
}
