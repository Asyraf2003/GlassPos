<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Core\Shared\Exceptions\DomainException;

final class CreateTransactionWorkspaceInlinePaymentContextResolver
{
    public function __construct(
        private readonly CreateTransactionWorkspaceInlinePaymentAmountResolver $amounts,
    ) {}

    /**
     * @return array{
     *   decision:string,
     *   method:string,
     *   amount_paid_rupiah:int,
     *   amount_received_rupiah:int,
     *   change_rupiah:int,
     *   paid_at:string
     * }
     */
    public function resolve(Note $note, mixed $payload): array
    {
        $payment = is_array($payload) ? $payload : [];
        $decision = (string) ($payment['decision'] ?? 'skip');

        if ($decision === 'skip') {
            return [
                'decision' => 'skip',
                'method' => '',
                'amount_paid_rupiah' => 0,
                'amount_received_rupiah' => 0,
                'change_rupiah' => 0,
                'paid_at' => '',
            ];
        }

        $method = (string) ($payment['payment_method'] ?? '');
        if (! in_array($method, ['cash', 'transfer'], true)) {
            throw new DomainException('Metode pembayaran workspace tidak valid.');
        }

        $amount = $this->amounts->resolve($note, $payment);
        $received = (int) ($payment['amount_received_rupiah'] ?? 0);

        return [
            'decision' => $decision,
            'method' => $method,
            'amount_paid_rupiah' => $amount,
            'amount_received_rupiah' => $received,
            'change_rupiah' => $method === 'cash' ? max($received - $amount, 0) : 0,
            'paid_at' => (string) ($payment['paid_at'] ?? ''),
        ];
    }
}
