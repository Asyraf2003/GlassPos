<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class PaymentTransactionRetryRunner
{
    public function __construct(
        private readonly TransactionManagerPort $transactions,
        private readonly PaymentConcurrencyTransientExceptionClassifier $concurrencyErrors,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $started = false;

            try {
                $this->transactions->begin();
                $started = true;

                $result = $callback();

                $this->transactions->commit();

                return $result;
            } catch (Throwable $e) {
                if ($started) {
                    $this->transactions->rollBack();
                }

                if ($attempt < 3 && $this->concurrencyErrors->isTransient($e)) {
                    usleep(25000 * $attempt);

                    continue;
                }

                throw $e;
            }
        }

        throw new \LogicException('Unreachable payment transaction retry state.');
    }
}
