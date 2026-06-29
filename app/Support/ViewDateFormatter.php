<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

final class ViewDateFormatter
{
    public static function display(mixed $value, bool $withTime = false): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $text = (string) $value;

        try {
            $date = ViewDateValueParser::parse($value, $text);

            if ($date === null) {
                return $text;
            }

            if (self::shouldUseDisplayTimezone($value, $text, $withTime)) {
                $date = $date->setTimezone(self::displayTimezone());
            }

            return IndonesianDateLabelFormatter::format($date, $withTime);
        } catch (Throwable) {
            return $text;
        }
    }

    public static function range(mixed $from, mixed $to): string
    {
        return self::display($from) . ' s/d ' . self::display($to);
    }

    private static function shouldUseDisplayTimezone(mixed $value, string $text, bool $withTime): bool
    {
        if (! $withTime) {
            return false;
        }

        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        $trimmed = trim($text);

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/', $trimmed) === 1) {
            return false;
        }

        return preg_match('/\d{1,2}:\d{2}/', $trimmed) === 1;
    }

    private static function displayTimezone(): string
    {
        if (function_exists('config')) {
            $configured = config('app.display_timezone');

            if (is_string($configured) && trim($configured) !== '') {
                return trim($configured);
            }
        }

        return 'Asia/Makassar';
    }
}
