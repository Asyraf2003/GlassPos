<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\Services;

final class BulkProductMaintenanceRowValidator
{
    /** @param array<string,string> $row
     *  @param array<int,string> $errors
     */
    public function validate(array $row, object $product, string $action, int $line, array &$errors): void
    {
        $expectedCode = $row['kode_barang'] === '' ? null : $row['kode_barang'];
        $expectedSize = $row['ukuran'] === ''
            ? null
            : filter_var($row['ukuran'], FILTER_VALIDATE_INT);

        if (($product->kode_barang ?? null) !== $expectedCode
            || trim((string) $product->nama_barang) !== $row['nama_barang']
            || trim((string) $product->merek) !== $row['merek']
            || ! $this->sizeMatches($expectedSize, $row['ukuran'], $product->ukuran)
        ) {
            $errors[] = "Baris {$line}: identitas product tidak cocok dengan database.";
        }

        if (! ctype_digit($row['expected_old_price'])
            || (int) $row['expected_old_price'] !== (int) $product->harga_jual) {
            $errors[] = "Baris {$line}: expected_old_price tidak cocok.";
        }

        if ($row['reason'] === '' || mb_strlen($row['reason']) > 255) {
            $errors[] = "Baris {$line}: reason wajib 1-255 karakter.";
        }

        if ($action === 'UPDATE_PRICE') {
            if (! ctype_digit($row['new_price']) || (int) $row['new_price'] < 1) {
                $errors[] = "Baris {$line}: new_price wajib integer positif.";
            } elseif ((int) $row['new_price'] === (int) $product->harga_jual) {
                $errors[] = "Baris {$line}: UPDATE_PRICE tetapi harga tidak berubah.";
            }

            return;
        }

        if ($row['new_price'] !== '') {
            $errors[] = "Baris {$line}: new_price harus kosong untuk action {$action}.";
        }
    }

    private function sizeMatches(int|false|null $expected, string $raw, mixed $actual): bool
    {
        if ($expected === false) {
            return false;
        }

        if ($raw === '') {
            return $actual === null;
        }

        return (int) $actual === $expected;
    }
}
