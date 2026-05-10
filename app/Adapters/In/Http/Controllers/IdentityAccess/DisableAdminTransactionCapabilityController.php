<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\IdentityAccess;

use App\Adapters\In\Http\Presenters\JsonPresenter;
use App\Adapters\In\Http\Requests\IdentityAccess\DisableAdminTransactionCapabilityRequest;
use App\Application\IdentityAccess\UseCases\DisableAdminTransactionCapabilityHandler;
use Illuminate\Http\JsonResponse;

final class DisableAdminTransactionCapabilityController
{
    public function __invoke(
        DisableAdminTransactionCapabilityRequest $request,
        DisableAdminTransactionCapabilityHandler $useCase,
        JsonPresenter $presenter,
    ): JsonResponse {
        $data = $request->validated();
        $performedByActorId = (string) $request->user()->getAuthIdentifier();

        $result = $useCase->handle(
            (string) $data['target_actor_id'],
            $performedByActorId,
        );

        if ($result->isFailure()) {
            return $presenter->failure($result);
        }

        return $presenter->success($result);
    }
}
