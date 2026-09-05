<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed\Support;

use App\Application\Shared\DTO\Result;
use RuntimeException;

final class SeedResultGuard
{
    public static function data(Result $result, string $label): mixed
    {
        if ($result->isFailure()) {
            throw new RuntimeException($label.': '.($result->message() ?? 'unknown failure'));
        }

        return $result->data();
    }
}
