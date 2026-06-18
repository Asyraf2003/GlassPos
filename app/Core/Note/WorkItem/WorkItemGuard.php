<?php

declare(strict_types=1);

namespace App\Core\Note\WorkItem;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class WorkItemGuard
{
    public static function assertValidCommon(string $id, string $noteId, int $lineNo, string $status): void
    {
        if (trim($id) === '') throw new DomainException('Work item id wajib ada.');
        if (trim($noteId) === '') throw new DomainException('Note id pada work item wajib ada.');
        if ($lineNo <= 0) throw new DomainException('Line number pada work item harus lebih besar dari nol.');
        $v = [WorkItem::STATUS_OPEN, WorkItem::STATUS_DONE, WorkItem::STATUS_CANCELED];
        if (!in_array(trim($status), $v, true)) throw new DomainException('Status work item tidak valid.');
    }

    public static function assertValidTransactionType(string $type): void
    {
        $v = [WorkItem::TYPE_SERVICE_ONLY, WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART];
        if (!in_array(trim($type), $v, true)) throw new DomainException('Transaction type pada work item tidak valid.');
    }

    public static function calculateSubtotal(string $t, ?ServiceDetail $sd, array $ext, array $sto): Money
    {
        return match ($t) {
            WorkItem::TYPE_SERVICE_ONLY => $sd->totalPriceRupiah(),
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE => self::calc($sd->totalPriceRupiah(), $ext),
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY => self::calc(Money::zero(), $sto),
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART => self::calc($sd->totalPriceRupiah(), $sto),
            default => throw new DomainException('Tipe transaksi tidak dikenal.')
        };
    }

    public static function validateState(string $t, ?ServiceDetail $sd, array $ext, array $sto): void
    {
        match ($t) {
            WorkItem::TYPE_SERVICE_ONLY => self::guardServiceOnly($sd, $ext, $sto),
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE => self::guardExt($sd, $ext, $sto),
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY => self::guardStoreOnly($sd, $ext, $sto),
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART => self::guardServiceStore($sd, $ext, $sto),
            default => null
        };
    }

    private static function calc(Money $base, array $lines): Money
    {
        foreach ($lines as $l) $base = $base->add($l->lineTotalRupiah());
        return $base;
    }

    private static function guardServiceOnly(?ServiceDetail $sd, array $ext, array $sto): void
    {
        if ($sd === null) throw new DomainException('Service detail wajib ada.');
        if ($ext !== [] || $sto !== []) throw new DomainException('Lines harus kosong untuk service only.');
        $ps = $sd->partSource();
        if ($ps !== ServiceDetail::PART_SOURCE_NONE && $ps !== ServiceDetail::PART_SOURCE_CUSTOMER_OWNED) {
            throw new DomainException('Part source untuk service only tidak valid.');
        }
    }

    private static function guardExt(?ServiceDetail $sd, array $ext, array $sto): void
    {
        if ($sd === null || $ext === []) throw new DomainException('Detail atau External lines tidak boleh kosong.');
        if ($sto !== []) throw new DomainException('Store lines harus kosong.');
        if ($sd->partSource() !== ServiceDetail::PART_SOURCE_NONE) throw new DomainException('Part source harus none.');
    }

    private static function guardStoreOnly(?ServiceDetail $sd, array $ext, array $sto): void
    {
        if ($sd !== null || $ext !== []) throw new DomainException('Service/External harus kosong.');
        if ($sto === []) throw new DomainException('Store lines minimal satu.');
    }

    private static function guardServiceStore(?ServiceDetail $sd, array $ext, array $sto): void
    {
        if ($sd === null || $sto === []) throw new DomainException('Detail atau Store lines tidak boleh kosong.');
        if ($ext !== []) throw new DomainException('External lines harus kosong.');
        if ($sd->partSource() !== ServiceDetail::PART_SOURCE_NONE) throw new DomainException('Part source harus none.');
    }
}
