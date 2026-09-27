<?php

declare(strict_types=1);

namespace App\Application\PushNotification\Services;

use App\Application\Procurement\DTO\SupplierPayableReminderRow;
use App\Application\PushNotification\DTO\PushNotificationPayload;
use App\Ports\Out\RouteUrlGeneratorPort;

final class SupplierPayableReminderPushPayloadFactory
{
    public function __construct(private readonly RouteUrlGeneratorPort $urls)
    {
    }

    /**
     * @param list<SupplierPayableReminderRow> $reminders
     */
    public function make(string $today, array $reminders): PushNotificationPayload
    {
        $count = count($reminders);
        $overdue = count(array_filter(
            $reminders,
            fn (SupplierPayableReminderRow $row): bool => $row->daysOverdue > 0,
        ));
        $total = array_sum(array_map(
            fn (SupplierPayableReminderRow $row): int => $row->outstandingRupiah,
            $reminders,
        ));

        $body = 'Ada '.$count.' faktur pemasok jatuh tempo/perlu dicek. Total outstanding Rp '
            .number_format($total, 0, ',', '.').'.';

        if ($overdue > 0) {
            $body .= ' '.$overdue.' faktur sudah lewat jatuh tempo.';
        }

        return new PushNotificationPayload(
            title: 'Reminder Jatuh Tempo Hutang Pemasok',
            body: $body,
            icon: '/assets/compiled/svg/favicon.svg',
            badge: '/assets/compiled/svg/favicon.svg',
            url: $this->urls->route('admin.procurement.supplier-invoices.index', [
                'payment_status' => 'outstanding',
                'sort_by' => 'due_date',
                'sort_dir' => 'asc',
            ], false),
            tag: 'supplier-payable-reminder-'.$today,
        );
    }
}
