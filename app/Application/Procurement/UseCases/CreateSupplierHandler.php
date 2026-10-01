<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\SupplierListProjectionService;
use App\Application\Procurement\Services\SupplierService;
use App\Core\Procurement\Supplier\Supplier;

final class CreateSupplierHandler
{
    public function __construct(
        private readonly SupplierService $suppliers,
        private readonly SupplierListProjectionService $projection,
    ) {}

    public function handle(string $name, ?string $bankName = null, ?string $accountNumber = null): Supplier
    {
        $supplier = $this->suppliers->resolve($name, $bankName, $accountNumber);
        $this->projection->syncSupplier($supplier->id());

        return $supplier;
    }
}
