<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;

final class SupplierPaymentProofPrepareIntentService
{
    public function __construct(
        private readonly SupplierPaymentProofUploadIntentPort $intents,
        private readonly SupplierPaymentProofPreparedIntentEvaluator $evaluator,
        private readonly SupplierPaymentProofPrepareScopePreflight $preflight,
        private readonly SupplierPaymentProofPrepareIntentCreator $creator,
    ) {}

    /** @param array<string,mixed> $request */
    public function resolve(array $request): Result
    {
        $existing = $this->intents->findForPrepare(
            $request['actor_id'],
            $request['scope_type'],
            $request['scope_id'],
            $request['idempotency_key'],
        );

        if ($existing !== null) {
            return $this->evaluator->evaluate($existing, $request['request_hash']);
        }

        $eligible = $this->preflight->check($request['scope_type'], $request['scope_id']);

        if ($eligible->isFailure()) {
            return $eligible;
        }

        $created = $this->creator->create($request);

        if ($created->isSuccess()) {
            return $created;
        }

        $raced = $this->intents->findForPrepare(
            $request['actor_id'],
            $request['scope_type'],
            $request['scope_id'],
            $request['idempotency_key'],
        );

        return $raced === null ? $created : $this->evaluator->evaluate($raced, $request['request_hash']);
    }
}
