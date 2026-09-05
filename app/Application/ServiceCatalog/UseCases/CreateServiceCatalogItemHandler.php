<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\UseCases;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Core\ServiceCatalog\ServiceCatalogItem;
use App\Core\ServiceCatalog\ServiceNameNormalizer;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\ServiceCatalog\ServiceCatalogReaderPort;
use App\Ports\Out\ServiceCatalog\ServiceCatalogWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Throwable;

final class CreateServiceCatalogItemHandler
{
    public function __construct(
        private readonly ServiceCatalogReaderPort $reader,
        private readonly ServiceCatalogWriterPort $writer,
        private readonly ServiceNameNormalizer $normalizer,
        private readonly AuditEventWriterPort $audit,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
        private readonly TransactionManagerPort $transactions,
    ) {}

    public function handle(
        string $name,
        int $defaultPriceRupiah,
        ?string $actorId = null,
        ?string $sourceChannel = null,
    ): ServiceCatalogItem {
        $existing = $this->reader->findByNormalizedName($this->normalizer->normalize($name));
        if ($existing !== null) {
            return $existing;
        }

        $this->transactions->begin();

        try {
            $item = $this->writer->createIfMissing($name, $defaultPriceRupiah);
            $snapshot = [
                'id' => $item->id(),
                'name' => $item->name(),
                'normalized_name' => $item->normalizedName(),
                'default_price_rupiah' => $item->defaultPriceRupiah(),
                'is_active' => $item->active(),
            ];

            $this->audit->write(new AuditEventWrite(
                id: $this->uuid->generate(),
                boundedContext: 'service_catalog',
                aggregateType: 'service_catalog_item',
                aggregateId: $item->id(),
                eventName: 'service_catalog_item_created',
                actorId: $this->nullable($actorId),
                actorRole: null,
                reason: null,
                sourceChannel: $this->nullable($sourceChannel),
                requestId: null,
                correlationId: null,
                occurredAt: $this->clock->now(),
                metadata: ['service_catalog_item_id' => $item->id()],
                snapshots: [new AuditEventSnapshotWrite('after', $snapshot)],
            ));

            $this->transactions->commit();

            return $item;
        } catch (Throwable $e) {
            $this->transactions->rollBack();
            throw $e;
        }
    }

    private function nullable(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
