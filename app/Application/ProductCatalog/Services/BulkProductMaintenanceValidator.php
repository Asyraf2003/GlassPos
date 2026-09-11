<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\Services;

use Illuminate\Support\Facades\DB;

final class BulkProductMaintenanceValidator
{
    public const ACTIONS = [
        'UPDATE_PRICE',
        'DELETE',
        'SKIP_UNKNOWN_PRICE',
        'UNCHANGED',
    ];

    /** @param array<int, array<string, string>> $rows
     *  @return array{errors:array<int,string>,counts:array<string,int>,actor_role:?string}
     */
    public function validate(array $rows, string $actorId): array
    {
        $errors = [];
        $counts = array_fill_keys(self::ACTIONS, 0);
        $actorRole = DB::table('actor_accesses')
            ->where('actor_id', $actorId)
            ->value('role');

        if (! is_string($actorRole) || trim($actorRole) === '') {
            $errors[] = 'Actor tidak memiliki role pada actor_accesses.';
        }

        $ids = array_map(static fn (array $row): string => trim($row['product_id']), $rows);
        $products = DB::table('products')->whereIn('id', array_values(array_unique($ids)))
            ->get()->keyBy('id');
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['__line'];
            $id = trim($row['product_id']);
            $action = strtoupper(trim($row['action']));

            if ($id === '') {
                $errors[] = "Baris {$line}: product_id kosong.";
                continue;
            }

            if (isset($seen[$id])) {
                $errors[] = "Baris {$line}: product_id {$id} duplikat.";
                continue;
            }
            $seen[$id] = true;

            if (! in_array($action, self::ACTIONS, true)) {
                $errors[] = "Baris {$line}: action {$action} tidak valid.";
                continue;
            }
            $counts[$action]++;

            $product = $products->get($id);

            if ($product === null) {
                $errors[] = "Baris {$line}: product {$id} tidak ditemukan.";
                continue;
            }

            if ($product->deleted_at !== null) {
                $errors[] = "Baris {$line}: product {$id} sudah dihapus.";
            }

            $this->validateIdentity($row, $product, $line, $errors);
            $this->validatePricesAndReason($row, $product, $action, $line, $errors);
        }

        return [
            'errors' => $errors,
            'counts' => $counts,
            'actor_role' => is_string($actorRole) ? trim($actorRole) : null,
        ];
    }

    /** @param array<string,string> $row
     *  @param array<int,string> $errors
     */
    private function validateIdentity(array $row, object $product, int $line, array &$errors): void
    {
        $expectedCode = $row['kode_barang'] === '' ? null : $row['kode_barang'];
        $expectedSize = $row['ukuran'] === '' ? null : filter_var($row['ukuran'], FILTER_VALIDATE_INT);

        if (($product->kode_barang ?? null) !== $expectedCode
            || trim((string) $product->nama_barang) !== $row['nama_barang']
            || trim((string) $product->merek) !== $row['merek']
            || ($expectedSize === false ? '__invalid__' : (string) ($product->ukuran ?? '')) !== ($row['ukuran'] === '' ? '' : (string) $expectedSize)
        ) {
            $errors[] = "Baris {$line}: identitas product tidak cocok dengan database.";
        }
    }

    /** @param array<string,string> $row
     *  @param array<int,string> $errors
     */
    private function validatePricesAndReason(array $row, object $product, string $action, int $line, array &$errors): void
    {
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
}
