<?php

declare(strict_types=1);

namespace App\Ports\Out;

use RuntimeException;

/** The canonical actor/operation/key uniqueness claim was won by another transaction. */
final class IdempotencyClaimConflictException extends RuntimeException {}
