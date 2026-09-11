<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\Services;

use InvalidArgumentException;

final class BulkProductMaintenanceManifestReader
{
    private const REQUIRED = [
        'product_id',
        'kode_barang',
        'nama_barang',
        'merek',
        'ukuran',
        'expected_old_price',
        'new_price',
        'action',
        'reason',
    ];

    /** @return array<int, array<string, string>> */
    public function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('Manifest tidak ditemukan atau tidak dapat dibaca.');
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('Manifest gagal dibuka.');
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw new InvalidArgumentException('Manifest kosong.');
            }

            $header = array_map(
                static fn (string $value): string => trim(str_replace("\xEF\xBB\xBF", '', $value)),
                $header,
            );

            $missing = array_diff(self::REQUIRED, $header);

            if ($missing !== []) {
                throw new InvalidArgumentException(
                    'Kolom manifest kurang: '.implode(', ', $missing)
                );
            }

            return $this->readRows($handle, $header);
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle
     *  @param array<int, string> $header
     *  @return array<int, array<string, string>>
     */
    private function readRows($handle, array $header): array
    {
        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            if ($values === [null] || $values === ['']) {
                continue;
            }

            if (count($values) !== count($header)) {
                throw new InvalidArgumentException("Jumlah kolom tidak cocok pada baris {$line}.");
            }

            $row = array_combine($header, array_map(
                static fn ($value): string => trim((string) $value),
                $values,
            ));

            if ($row === false) {
                throw new InvalidArgumentException("Manifest gagal dibaca pada baris {$line}.");
            }

            $row['__line'] = (string) $line;
            $rows[] = $row;
        }

        if ($rows === []) {
            throw new InvalidArgumentException('Manifest tidak memiliki data.');
        }

        return $rows;
    }
}
