<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

final class CreateNoteRevisionPaymentResultFactory
{
    /**
     * @param array{decision:string,amount_paid_rupiah:int,change_rupiah:int} $paymentSummary
     */
    public function withPaymentSummary(
        CreateNoteRevisionResult $result,
        array $paymentSummary,
    ): CreateNoteRevisionResult {
        if ($result->isFailure()) {
            return $result;
        }

        $data = $result->data();
        $data['inline_payment'] = $paymentSummary;

        if (($paymentSummary['decision'] ?? 'skip') === 'skip') {
            return CreateNoteRevisionResult::success($data, $result->message());
        }

        if (($paymentSummary['change_rupiah'] ?? 0) > 0) {
            return CreateNoteRevisionResult::success(
                $data,
                'Revisi nota dan pembayaran berhasil dicatat. Kembalian: '
                    . number_format((int) $paymentSummary['change_rupiah'], 0, ',', '.')
            );
        }

        return CreateNoteRevisionResult::success($data, 'Revisi nota dan pembayaran berhasil dicatat.');
    }
}
