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
        $settlementCommitter = (string) file_get_contents(
            base_path('app/Application/Note/UseCases/CreateNoteRevisionSettlementCommitter.php'),
        );

        $applyPosition = strpos($workflow, '$this->applier->apply(');
        $paymentPosition = strpos($workflow, '$this->payments->record(');
        $settlementCommitPosition = strpos($workflow, '$this->settlementCommits->commit(');

        self::assertIsInt($applyPosition);
        self::assertIsInt($paymentPosition);
        self::assertIsInt($settlementCommitPosition);
        self::assertLessThan($paymentPosition, $applyPosition);
        self::assertLessThan($settlementCommitPosition, $paymentPosition);
        self::assertStringContainsString(
            '$this->paymentResults->withPaymentSummary($result, $paymentSummary)',
            $workflow,
        );

        $settlementBuildPosition = strpos($settlementCommitter, '$this->settlements->build(');
        $revisionCommitPosition = strpos($settlementCommitter, '$this->committer->commit(');
        $autoSurplusRefundPosition = strpos($settlementCommitter, '$this->autoSurplusRefund->settle(');

        self::assertIsInt($settlementBuildPosition);
        self::assertIsInt($revisionCommitPosition);
        self::assertIsInt($autoSurplusRefundPosition);
        self::assertLessThan($revisionCommitPosition, $settlementBuildPosition);
        self::assertLessThan($autoSurplusRefundPosition, $revisionCommitPosition);
    }
}
