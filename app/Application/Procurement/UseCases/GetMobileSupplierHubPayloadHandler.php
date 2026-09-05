<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Ports\Out\Procurement\MobileSupplierHubReaderPort;

final class GetMobileSupplierHubPayloadHandler
{
    public function __construct(
        private readonly MobileSupplierHubReaderPort $reader,
    ) {}

    /**
     * @return array{
     *     outstanding_invoices: list<array<string, int|string>>,
     *     recent_payment_proofs: list<array<string, int|string>>
     * }
     */
    public function handle(int $limit = 100): array
    {
        return [
            'outstanding_invoices' => $this->reader->outstandingInvoices($limit),
            'recent_payment_proofs' => $this->reader->recentPaymentProofs($limit),
        ];
    }
}
