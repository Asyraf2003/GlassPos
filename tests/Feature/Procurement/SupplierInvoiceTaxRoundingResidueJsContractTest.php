<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Tests\TestCase;

final class SupplierInvoiceTaxRoundingResidueJsContractTest extends TestCase
{
    public function test_create_js_confirms_tax_rounding_residue_before_submit(): void
    {
        $this->assertJsContract('public/assets/static/js/pages/admin-procurement-create.js');
    }

    public function test_edit_js_confirms_tax_rounding_residue_before_submit(): void
    {
        $this->assertJsContract('public/assets/static/js/pages/admin-procurement-edit.js');
    }

    private function assertJsContract(string $path): void
    {
        $js = file_get_contents(base_path($path));

        self::assertIsString($js);
        self::assertStringContainsString('data-tax-rounding-residue-confirmed-input', $js);
        self::assertStringContainsString('data-tax-rounding-residue-message', $js);
        self::assertStringContainsString('requiresTaxRoundingResidueConfirmation', $js);
        self::assertStringContainsString('confirmTaxRoundingResidueBeforeSubmit', $js);
        self::assertStringContainsString('window.confirm', $js);
        self::assertStringContainsString('taxRoundingResidueConfirmedInput.value = "1"', $js);
        self::assertStringContainsString('taxRoundingResidueConfirmedInput.value = "0"', $js);
    }
}
