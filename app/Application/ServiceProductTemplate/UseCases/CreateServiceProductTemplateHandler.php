<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\UseCases;

use App\Application\ServiceProductTemplate\Services\CreateServiceProductTemplateAuditRecorder;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateCreatePort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Throwable;

final class CreateServiceProductTemplateHandler
{
    public function __construct(
        private readonly ServiceProductTemplateCreatePort $templates,
        private readonly CreateServiceProductTemplateAuditRecorder $auditRecorder,
        private readonly UuidPort $uuid,
        private readonly TransactionManagerPort $transactions,
    ) {}

    /** @param list<array{product_id:string,qty:int,sort_order:int}> $lines */
    public function handle(
        array $lines,
        string $serviceCatalogItemId,
        ?string $actorId = null,
        ?string $sourceChannel = null,
    ): Result {
        if ($lines === []) {
            return Result::failure('Produk paket wajib diisi.', ['product_id' => ['PRODUCT_REQUIRED']]);
        }

        $productId = trim($lines[0]['product_id']);
        $serviceId = trim($serviceCatalogItemId);

        if ($this->templates->activeTemplateExists($productId, $serviceId)) {
            return Result::failure(
                'Produk 1 dan jasa ini sudah punya paket aktif.',
                ['service_catalog_item_id' => ['ACTIVE_TEMPLATE_ALREADY_EXISTS']],
            );
        }

        $servicePrice = $this->templates->serviceDefaultPriceRupiah($serviceId);
        if ($servicePrice === null) {
            return Result::failure('Jasa tidak ditemukan atau tidak aktif.', ['service_catalog_item_id' => ['SERVICE_NOT_FOUND']]);
        }

        $productTotal = $this->templates->productLinesTotalRupiah($lines);
        if ($productTotal === null) {
            return Result::failure('Produk paket tidak ditemukan atau sudah dihapus.', ['product_id' => ['PRODUCT_NOT_FOUND']]);
        }

        $templateId = $this->uuid->generate();
        $packageTotal = $productTotal + $servicePrice;
        $snapshot = [
            'id' => $templateId,
            'product_id' => $productId,
            'service_catalog_item_id' => $serviceId,
            'default_service_price_rupiah' => $servicePrice,
            'default_package_total_rupiah' => $packageTotal,
            'is_active' => true,
            'sort_order' => 0,
            'lines' => $lines,
        ];

        $this->transactions->begin();

        try {
            $this->templates->create($templateId, $serviceId, $servicePrice, $packageTotal, $lines);
            $this->auditRecorder->record($templateId, $snapshot, $actorId, $sourceChannel);
            $this->transactions->commit();
        } catch (Throwable $e) {
            $this->transactions->rollBack();
            throw $e;
        }

        return Result::success($snapshot, 'Service berhasil dibuat.');
    }
}
