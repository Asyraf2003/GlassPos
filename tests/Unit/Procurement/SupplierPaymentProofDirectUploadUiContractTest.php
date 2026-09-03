<?php

declare(strict_types=1);

namespace Tests\Unit\Procurement;

use PHPUnit\Framework\TestCase;

final class SupplierPaymentProofDirectUploadUiContractTest extends TestCase
{
    public function test_browser_script_prepares_puts_to_presigned_url_then_finalizes_without_multipart(): void
    {
        $script = file_get_contents(dirname(__DIR__, 3)
            .'/resources/views/admin/procurement/partials/supplier_payment_proof_direct_upload_script.blade.php');
        self::assertIsString($script);
        $prepare = strpos($script, 'postJson(form.dataset.prepareUrl');
        $put = strpos($script, "method: 'PUT'");
        $finalize = strpos($script, 'postJson(finalizeUrl');

        self::assertIsInt($prepare);
        self::assertIsInt($put);
        self::assertIsInt($finalize);
        self::assertLessThan($put, $prepare);
        self::assertLessThan($finalize, $put);
        self::assertStringContainsString('body: files[index]', $script);
        self::assertStringNotContainsString('FormData', $script);
        self::assertStringNotContainsString('supplier-payment-proofs/', $script);
    }
}
