<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Validation\Validator;

final class StoreTransactionWorkspacePaymentValidator
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function validate(array $payload, Validator $validator): void
    {
        $payment = $payload['inline_payment'] ?? null;

        if (! is_array($payment)) {
            $validator->errors()->add('inline_payment', 'Format pembayaran workspace tidak valid.');
            return;
        }

        $decision = (string) ($payment['decision'] ?? 'skip');

        if ($decision === 'skip') {
            return;
        }

        if (! in_array((string) ($payment['payment_method'] ?? ''), ['cash', 'transfer'], true)) {
            $validator->errors()->add('inline_payment.payment_method', 'Metode pembayaran workspace tidak valid.');
        }

        if (! is_string($payment['paid_at'] ?? null) || trim((string) $payment['paid_at']) === '') {
            $validator->errors()->add('inline_payment.paid_at', 'Tanggal bayar wajib diisi.');
        }

        $grandTotal = StoreTransactionWorkspaceGrandTotalCalculator::calculate($payload['items'] ?? []);
        $amountPaid = self::intValue($payment['amount_paid_rupiah'] ?? null);

        if ($decision === 'pay_partial') {
            if ($amountPaid <= 0) {
                $validator->errors()->add('inline_payment.amount_paid_rupiah', 'Nominal pembayaran sebagian wajib lebih dari 0.');
            }

            if ($grandTotal > 0 && $amountPaid >= $grandTotal) {
                $validator->errors()->add('inline_payment.amount_paid_rupiah', 'Nominal pembayaran sebagian harus lebih kecil dari grand total nota.');
            }
        }

        $cashTargetAmount = match ($decision) {
            'pay_partial' => $amountPaid,
            'pay_full' => $grandTotal,
            default => 0,
        };

        if (($payment['payment_method'] ?? null) !== 'cash') {
            return;
        }

        $received = self::intValue($payment['amount_received_rupiah'] ?? null);

        if ($received <= 0) {
            $validator->errors()->add('inline_payment.amount_received_rupiah', 'Uang masuk cash wajib lebih dari 0.');
        }

        if ($cashTargetAmount > 0 && $received < $cashTargetAmount) {
            $validator->errors()->add('inline_payment.amount_received_rupiah', 'Uang masuk cash tidak boleh kurang dari total yang dibayar.');
        }
    }

    private static function intValue(mixed $value): int
    {
        return is_int($value) ? $value : 0;
    }
}
