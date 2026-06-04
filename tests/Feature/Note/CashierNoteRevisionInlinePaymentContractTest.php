<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierNoteRevisionInlinePaymentContractTest extends TestCase
{
    public function test_revision_request_preserves_inline_payment_payload(): void
    {
        $request = (string) file_get_contents(base_path('app/Adapters/In/Http/Requests/Note/StoreNoteRevisionRequest.php'));

        self::assertStringContainsString('UpdateTransactionWorkspaceInputNormalizer::normalize($this->all())', $request);
        self::assertStringNotContainsString('$normalized[\'inline_payment\'] = [', $request);
    }

    public function test_revision_workflow_records_inline_payment_after_replacement_before_settlement(): void
    {
        $workflow = (string) file_get_contents(base_path('app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php'));

        $applyPosition = strpos($workflow, '$this->applier->apply(');
        $paymentPosition = strpos($workflow, '$this->payments->record(');
        $settlementPosition = strpos($workflow, '$this->settlements->build(');

        self::assertIsInt($applyPosition);
        self::assertIsInt($paymentPosition);
        self::assertIsInt($settlementPosition);
        self::assertLessThan($paymentPosition, $applyPosition);
        self::assertLessThan($settlementPosition, $paymentPosition);
        self::assertStringContainsString('withPaymentSummary($result, $paymentSummary)', $workflow);
    }
}
