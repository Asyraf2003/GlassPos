<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

final class ReportDetailTableFormatter
{
    public static function table(string $title, array $columns, array $rows): array
    {
        return ['title' => $title, 'columns' => $columns, 'rows' => array_map(static function (array $row) use ($columns): array {
            $formatted = [];
            foreach ($columns as $key => $label) {
                $value = $row[$key] ?? null;
                $formatted[$key] = $value === null ? '-' : (str_ends_with($key, '_rupiah')
                    ? 'Rp '.number_format((int) $value, 0, ',', '.') : (string) $value);
            }

            return $formatted;
        }, $rows)];
    }
}
