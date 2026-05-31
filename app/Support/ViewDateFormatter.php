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

            return IndonesianDateLabelFormatter::format($date, $withTime);
        } catch (Throwable) {
            return $text;
        }
    }

    public static function range(mixed $from, mixed $to): string
    {
        return self::display($from) . ' s/d ' . self::display($to);
    }
}
