<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;

final class SupplierPaymentProofPrepareIntentService
{
    public function __construct(
        private readonly SupplierPaymentProofUploadIntentPort $intents,
        private readonly SupplierPaymentProofPreparedIntentEvaluator $evaluator,
        private readonly SupplierPaymentProofPrepareScopePreflight $preflight,
        private readonly SupplierPaymentProofPrepareIntentCreator $creator,
        private readonly SupplierPaymentProofFailureReporterPort $failures,
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

        if ($raced !== null) {
            return $this->evaluator->evaluate($raced, $request['request_hash']);
        }

        $this->failures->report(
            'prepare.intent.persist',
            SupplierPaymentProofFailureCode::PREPARE_INTENT_NOT_AVAILABLE,
            null,
            [
                'scope_type' => (string) $request['scope_type'],
                'scope_id' => (string) $request['scope_id'],
                'file_count' => count($request['files']),
            ],
        );

        return $created;
    }
}
