<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\DTO;

use App\Core\Shared\Exceptions\DomainException;

/** Explicit business attestation of duplicate identities of the same physical product. */
final readonly class ProductIdentityMerge
{
    public function __construct(
        public string $id,
        public string $sourceProductId,
        public string $canonicalProductId,
        public string $actorId,
        public string $reason,
        public string $priorStockTransferSourceId,
    ) {
        foreach (get_object_vars($this) as $value) {
            if (trim($value) === '' || trim($value) !== $value) {
                throw new DomainException('Merge identity fields must be nonempty and normalized.');
            }
        }
        if ($sourceProductId === $canonicalProductId) {
            throw new DomainException('Source and canonical product must differ.');
        }
    }
}
