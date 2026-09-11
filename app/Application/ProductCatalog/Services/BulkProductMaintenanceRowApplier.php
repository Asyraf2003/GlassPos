<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\Services;

use App\Application\ProductCatalog\Context\ProductChangeContext;
use App\Application\ProductCatalog\UseCases\SoftDeleteProductHandler;
use App\Application\ProductCatalog\UseCases\UpdateProductHandler;
use App\Application\Shared\DTO\Result;
use RuntimeException;

final class BulkProductMaintenanceRowApplier
{
    public function __construct(
        private readonly UpdateProductHandler $updates,
        private readonly SoftDeleteProductHandler $deletes,
        private readonly ProductChangeContext $context,
    ) {}

    /** @param array<string,string> $row */
    public function apply(
        array $row,
        object $product,
        string $actorId,
        string $actorRole,
        bool $uppercaseMaster,
    ): void {
        $action = strtoupper($row['action']);
        $normalize = $uppercaseMaster && $this->needsUppercase($product);

        if ($normalize || $action === 'UPDATE_PRICE') {
            $reason = $action === 'UPDATE_PRICE'
                ? $row['reason'].($normalize ? ' Sekaligus normalisasi master ke huruf besar.' : '')
                : 'Normalisasi kode barang, nama barang, dan merek ke huruf besar.';

            $this->setContext($actorId, $actorRole, $reason);
            $price = $action === 'UPDATE_PRICE'
                ? (int) $row['new_price']
                : (int) $product->harga_jual;
            $result = $this->update($product, $price, $uppercaseMaster);
            $this->assertSuccess($result, (string) $product->id);

            if ($action === 'UPDATE_PRICE') {
                return;
            }
        }

        if ($action !== 'DELETE') {
            return;
        }

        $this->setContext($actorId, $actorRole, $row['reason']);
        $this->assertSuccess(
            $this->deletes->handle($row['product_id'], $actorId),
            $row['product_id'],
        );
    }

    private function update(object $product, int $price, bool $uppercase): Result
    {
        $code = $product->kode_barang !== null ? (string) $product->kode_barang : null;
        $name = (string) $product->nama_barang;
        $brand = (string) $product->merek;

        return $this->updates->handle(
            (string) $product->id,
            $uppercase && $code !== null ? mb_strtoupper($code, 'UTF-8') : $code,
            $uppercase ? mb_strtoupper($name, 'UTF-8') : $name,
            $uppercase ? mb_strtoupper($brand, 'UTF-8') : $brand,
            $product->ukuran !== null ? (int) $product->ukuran : null,
            $price,
            $product->reorder_point_qty !== null ? (int) $product->reorder_point_qty : null,
            $product->critical_threshold_qty !== null ? (int) $product->critical_threshold_qty : null,
        );
    }

    private function needsUppercase(object $product): bool
    {
        $code = $product->kode_barang;

        return ($code !== null && (string) $code !== mb_strtoupper((string) $code, 'UTF-8'))
            || (string) $product->nama_barang !== mb_strtoupper((string) $product->nama_barang, 'UTF-8')
            || (string) $product->merek !== mb_strtoupper((string) $product->merek, 'UTF-8');
    }

    private function setContext(string $actorId, string $actorRole, string $reason): void
    {
        $this->context->set($actorId, $actorRole, 'cli_bulk_product_maintenance', $reason);
    }

    private function assertSuccess(Result $result, string $productId): void
    {
        if ($result->isFailure()) {
            throw new RuntimeException($result->message() ?? "Mutasi {$productId} gagal.");
        }
    }
}
