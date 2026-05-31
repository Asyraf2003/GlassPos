<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;

final class IndonesianDateLabelFormatter
{
    /**
     * @var array<int, string>
     */
    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public static function format(Carbon $date, bool $withTime): string
    {
        $month = self::MONTHS[(int) $date->format('n')] ?? $date->format('m');
        $formatted = $date->format('d') . ' ' . $month . ' ' . $date->format('Y');

        if ($withTime) {
            $formatted .= $date->format(' H:i');
        }

        return $formatted;
    }
}
