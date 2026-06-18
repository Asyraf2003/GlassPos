<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\Note\WorkItemWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseWorkItemWriterAdapter implements WorkItemWriterPort
{
    use WorkItemDeletesTrait;
    use WorkItemLineInsertsTrait;
    use WorkItemServiceUpdateGuardsTrait;

    public function create(WorkItem $workItem): void
    {
        $now = now();

        DB::table('work_items')->insert([
            'id' => $workItem->id(),
            'note_id' => $workItem->noteId(),
            'line_no' => $workItem->lineNo(),
            'transaction_type' => $workItem->transactionType(),
            'status' => $workItem->status(),
            'subtotal_rupiah' => $workItem->subtotalRupiah()->amount(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $serviceDetail = $workItem->serviceDetail();

        if ($serviceDetail !== null) {
            DB::table('work_item_service_details')->insert([
                'work_item_id' => $workItem->id(),
                'service_name' => $serviceDetail->serviceName(),
                'service_price_rupiah' => $serviceDetail->servicePriceRupiah()->amount(),
                'package_profit_rupiah' => $serviceDetail->packageProfitRupiah()->amount(),
                'package_base_service_price_rupiah' => $serviceDetail->packageBaseServicePriceRupiah()?->amount(),
                'package_service_extra_rupiah' => $serviceDetail->packageServiceExtraRupiah()->amount(),
                'part_source' => $serviceDetail->partSource(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->insertExternalPurchaseLines($workItem, $now);
        $this->insertStoreStockLines($workItem, $now);
    }

    public function updateStatus(WorkItem $workItem): void
    {
        DB::table('work_items')->where('id', $workItem->id())->update([
            'status' => $workItem->status(),
            'updated_at' => now(),
        ]);
    }

    public function updateServiceOnly(WorkItem $workItem): void
    {
        $this->updateSubtotalAndServiceDetail($workItem, $this->assertServiceOnlyUpdatable($workItem));
    }

    public function updateServiceWithStoreStockPartServiceFeeOnly(WorkItem $workItem): void
    {
        $this->updateSubtotalAndServiceDetail($workItem, $this->assertServiceWithStoreStockPartFeeOnlyUpdatable($workItem));
    }

    public function updateServiceWithExternalPurchaseServiceFeeOnly(WorkItem $workItem): void
    {
        $this->updateSubtotalAndServiceDetail($workItem, $this->assertServiceWithExternalPurchaseFeeOnlyUpdatable($workItem));
    }
}
