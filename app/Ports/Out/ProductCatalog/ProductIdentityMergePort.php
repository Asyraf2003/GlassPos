<?php

declare(strict_types=1);

namespace App\Ports\Out\ProductCatalog;

use App\Application\ProductCatalog\DTO\ProductIdentityMerge;

interface ProductIdentityMergePort
{
    /** Validate and lock explicit products, actor and prior transfer; append or verify exact retry. */
    public function recordTransferredMerge(ProductIdentityMerge $merge): bool;

    /** Lock/read current ledger after dependent invoice locks; fail if any source stock/value remains. */
    public function assertSourceDrained(ProductIdentityMerge $merge): void;

    public function findBySource(string $sourceProductId): ?ProductIdentityMerge;
}
