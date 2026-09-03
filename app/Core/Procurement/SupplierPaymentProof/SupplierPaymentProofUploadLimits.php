<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierPaymentProof;

final class SupplierPaymentProofUploadLimits
{
    public const MAX_FILES = 3;

    public const MAX_BYTES_PER_FILE = 10_485_760;
}
