<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

use Throwable;

interface SupplierPaymentProofFailureReporterPort
{
    /** @param array<string, bool|int|string|null> $context */
    public function report(string $stage, Throwable $exception, array $context = []): void;
}
