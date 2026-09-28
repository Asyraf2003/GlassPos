<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\PushNotification;

use App\Adapters\In\Http\Requests\PushNotification\DeletePushSubscriptionRequest;
use App\Application\PushNotification\UseCases\GetPushSubscriptionStatusHandler;
use Illuminate\Http\JsonResponse;

final class PushSubscriptionStatusController
{
    public function __invoke(DeletePushSubscriptionRequest $request, GetPushSubscriptionStatusHandler $handler): JsonResponse
    {
        return response()->json(['data' => ['active' => $handler->handle(
            (int) $request->user()->getAuthIdentifier(),
            (string) $request->validated('endpoint'),
        )]]);
    }
}
