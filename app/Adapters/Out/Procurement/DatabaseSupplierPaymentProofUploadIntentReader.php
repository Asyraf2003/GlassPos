<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentProofUploadIntentReader
{
    private const INTENTS = 'supplier_payment_proof_upload_intents';

    public function __construct(
        private readonly DatabaseSupplierPaymentProofUploadIntentHydrator $hydrator,
    ) {
    }

    /** @return array<string,mixed>|null */
    public function findForPrepare(
        string $actorId,
        string $scopeType,
        string $scopeId,
        string $idempotencyKey,
    ): ?array {
        $row = DB::table(self::INTENTS)
            ->where('actor_id', $actorId)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        return $row === null ? null : $this->hydrator->hydrate($row);
    }

    /** @return array<string,mixed>|null */
    public function findByIdForActor(string $uploadIntentId, string $actorId): ?array
    {
        $row = DB::table(self::INTENTS)
            ->where('id', $uploadIntentId)
            ->where('actor_id', $actorId)
            ->first();

        return $row === null ? null : $this->hydrator->hydrate($row);
    }
}
