<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;

enum SupplierPaymentProofPublicError: string
{
    case PREPARE_FAILED = 'PREPARE_FAILED';
    case PRESIGN_FAILED = 'PRESIGN_FAILED';

    public function result(): Result
    {
        return Result::failure(
            'Upload bukti pembayaran gagal disiapkan.',
            ['upload_intent' => [$this->value]],
        );
    }
}
