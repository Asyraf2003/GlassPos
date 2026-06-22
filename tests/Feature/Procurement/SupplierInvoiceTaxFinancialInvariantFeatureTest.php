<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierInvoiceTaxFinancialInvariantFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_existing_header_tax_invoice_does_not_double_tax(): void
    {
        $this->seedHeaderTaxInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'tax_input' => '10%',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1000,
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-tax-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(22000, (int) $line->line_total_rupiah);
        $this->assertSame(11000, (int) $line->unit_cost_rupiah);
    }

    public function test_fully_paid_invoice_rejects_revision_total_below_paid_total(): void
    {
        $this->seedHeaderTaxInvoice();
        $this->seedPayment('payment-tax-1', 'invoice-tax-1', 22000);

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'tax_input' => null,
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));
        $response->assertSessionHasErrors(['supplier_invoice']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 1,
        ]);
    }

    public function test_fully_paid_invoice_can_be_revised_upward_and_keeps_existing_payment_amount(): void
    {
        $this->seedHeaderTaxInvoice();
        $this->seedPayment('payment-tax-1', 'invoice-tax-1', 22000);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'tax_input' => '10%',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 22000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'subtotal_before_tax_rupiah' => 22000,
            'tax_input' => '10%',
            'tax_amount_rupiah' => 2200,
            'grand_total_rupiah' => 24200,
            'last_revision_no' => 2,
        ]);

        $this->assertDatabaseHas('supplier_payments', [
            'id' => 'payment-tax-1',
            'supplier_invoice_id' => 'invoice-tax-1',
            'amount_rupiah' => 22000,
        ]);

        $line = $this->currentLine('invoice-tax-1', 'product-tax-1');

        $this->assertSame(22000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(24200, (int) $line->line_total_rupiah);
        $this->assertSame(12100, (int) $line->unit_cost_rupiah);
    }

    public function test_fully_paid_invoice_can_be_revised_when_new_total_equals_active_paid_total(): void
    {
        $this->seedHeaderTaxInvoice();
        $this->seedPayment('payment-tax-1', 'invoice-tax-1', 22000);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'nomor_faktur' => 'INV-TAX-001-RENAMED',
                'tax_input' => '10%',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'nomor_faktur' => 'INV-TAX-001-RENAMED',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 2,
        ]);

        $this->assertDatabaseHas('supplier_payments', [
            'id' => 'payment-tax-1',
            'supplier_invoice_id' => 'invoice-tax-1',
            'amount_rupiah' => 22000,
        ]);
    }

    public function test_partially_paid_invoice_rejects_revision_total_below_active_paid_total(): void
    {
        $this->seedHeaderTaxInvoice();
        $this->seedPayment('payment-tax-1', 'invoice-tax-1', 15000);

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'tax_input' => null,
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 1,
                        'line_total_rupiah' => 10000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));
        $response->assertSessionHasErrors(['supplier_invoice']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 1,
        ]);
    }

    public function test_reversed_supplier_payment_does_not_lock_revision_total(): void
    {
        $this->seedHeaderTaxInvoice();
        $this->seedPayment('payment-tax-1', 'invoice-tax-1', 22000);
        $this->seedPaymentReversal('reversal-tax-1', 'payment-tax-1');

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'tax_input' => null,
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 20000,
            'last_revision_no' => 2,
        ]);

        $this->assertDatabaseHas('supplier_payment_reversals', [
            'id' => 'reversal-tax-1',
            'supplier_payment_id' => 'payment-tax-1',
        ]);
    }

    public function test_header_tax_array_input_is_rejected_instead_of_silently_ignored(): void
    {
        $this->seedLegacyNoTaxInvoice('invoice-legacy-1', 'invoice-legacy-line-1', 20000, 10000);

        $response = $this->actingAs($this->user('admin'))
            ->putJson(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-legacy-1',
            ]), $this->updatePayload([
                'tax_input' => ['amount' => '2000'],
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-legacy-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['tax_input']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-legacy-1',
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 20000,
            'last_revision_no' => 1,
        ]);
    }

    public function test_line_tax_array_input_is_rejected_instead_of_silently_ignored(): void
    {
        $this->seedLegacyNoTaxInvoice('invoice-legacy-1', 'invoice-legacy-line-1', 20000, 10000);

        $response = $this->actingAs($this->user('admin'))
            ->putJson(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-legacy-1',
            ]), $this->updatePayload([
                'tax_input' => null,
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-legacy-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                        'tax_input' => ['amount' => '2000'],
                    ],
                ],
            ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['lines.0.tax_input']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-legacy-1',
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 20000,
            'last_revision_no' => 1,
        ]);
    }

    public function test_legacy_no_tax_invoice_can_add_header_percent_tax_as_landed_cost(): void
    {
        $this->seedLegacyNoTaxInvoice('invoice-legacy-1', 'invoice-legacy-line-1', 20000, 10000);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-legacy-1',
            ]), $this->updatePayload([
                'tax_input' => '10%',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-legacy-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-legacy-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-legacy-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1000,
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-legacy-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(22000, (int) $line->line_total_rupiah);
        $this->assertSame(11000, (int) $line->unit_cost_rupiah);
    }

    public function test_legacy_no_tax_invoice_can_add_header_fixed_tax_as_landed_cost(): void
    {
        $this->seedLegacyNoTaxInvoice('invoice-legacy-1', 'invoice-legacy-line-1', 20000, 10000);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-legacy-1',
            ]), $this->updatePayload([
                'tax_input' => '2000',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-legacy-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-legacy-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-legacy-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '2000',
            'tax_mode' => 'fixed',
            'tax_rate_basis_points' => null,
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-legacy-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(22000, (int) $line->line_total_rupiah);
        $this->assertSame(11000, (int) $line->unit_cost_rupiah);
    }

    public function test_legacy_tax_included_invoice_can_split_fixed_tax_without_changing_grand_total(): void
    {
        $this->seedLegacyNoTaxInvoice('invoice-legacy-1', 'invoice-legacy-line-1', 22000, 11000);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-legacy-1',
            ]), $this->updatePayload([
                'tax_input' => '2000',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-legacy-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-legacy-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-legacy-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '2000',
            'tax_mode' => 'fixed',
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-legacy-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(22000, (int) $line->line_total_rupiah);
        $this->assertSame(11000, (int) $line->unit_cost_rupiah);
    }

    public function test_legacy_tax_included_invoice_can_split_percent_tax_without_changing_grand_total(): void
    {
        $this->seedLegacyNoTaxInvoice('invoice-legacy-1', 'invoice-legacy-line-1', 22000, 11000);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-legacy-1',
            ]), $this->updatePayload([
                'tax_input' => '10%',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-legacy-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-legacy-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-legacy-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1000,
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-legacy-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(22000, (int) $line->line_total_rupiah);
        $this->assertSame(11000, (int) $line->unit_cost_rupiah);
    }

    public function test_legacy_tax_included_invoice_can_move_tax_to_line_without_changing_grand_total(): void
    {
        $this->seedLegacyNoTaxInvoice('invoice-legacy-1', 'invoice-legacy-line-1', 22000, 11000);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-legacy-1',
            ]), $this->updatePayload([
                'tax_input' => null,
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-legacy-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                        'tax_input' => '2000',
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-legacy-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-legacy-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-legacy-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(22000, (int) $line->line_total_rupiah);
        $this->assertSame(11000, (int) $line->unit_cost_rupiah);
        $this->assertSame('2000', (string) $line->tax_input);
        $this->assertSame('fixed', (string) $line->tax_mode);
        $this->assertSame(2000, (int) $line->tax_amount_rupiah);
    }

    public function test_legacy_no_tax_invoice_blank_tax_preserves_no_tax_state(): void
    {
        $this->seedLegacyNoTaxInvoice('invoice-legacy-1', 'invoice-legacy-line-1', 20000, 10000);

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-legacy-1',
            ]), $this->updatePayload([
                'tax_input' => '   ',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-legacy-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-legacy-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-legacy-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_rate_basis_points' => null,
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 20000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-legacy-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(20000, (int) $line->line_total_rupiah);
        $this->assertSame(10000, (int) $line->unit_cost_rupiah);
    }

    public function test_received_invoice_same_qty_tax_revision_is_rejected_until_revaluation_exists(): void
    {
        $this->seedReceivedNoTaxInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-received-tax-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-received-tax-1',
            ]), $this->updatePayload([
                'tax_input' => '10%',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-received-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-received-tax-1',
        ]));
        $response->assertSessionHasErrors(['supplier_invoice']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-received-tax-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 20000,
            'last_revision_no' => 1,
        ]);

        $line = $this->currentLine('invoice-received-tax-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(20000, (int) $line->line_total_rupiah);
        $this->assertSame(10000, (int) $line->unit_cost_rupiah);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-tax-1',
            'qty_on_hand' => 2,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 20000,
        ]);
    }

    public function test_received_invoice_remove_tax_revision_is_rejected_until_revaluation_exists(): void
    {
        $this->seedReceivedHeaderTaxInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'tax_input' => null,
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));
        $response->assertSessionHasErrors(['supplier_invoice']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1000,
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 1,
        ]);

        $line = $this->currentLine('invoice-tax-1', 'product-tax-1');

        $this->assertSame(20000, (int) $line->line_subtotal_before_tax_rupiah);
        $this->assertSame(22000, (int) $line->line_total_rupiah);
        $this->assertSame(11000, (int) $line->unit_cost_rupiah);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 11000,
            'inventory_value_rupiah' => 22000,
        ]);
    }

    public function test_received_invoice_change_tax_amount_revision_is_rejected_until_revaluation_exists(): void
    {
        $this->seedReceivedHeaderTaxInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'tax_input' => '3000',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));
        $response->assertSessionHasErrors(['supplier_invoice']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'tax_input' => '10%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1000,
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 1,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 11000,
            'inventory_value_rupiah' => 22000,
        ]);
    }

    public function test_received_invoice_base_price_decrease_revision_is_rejected_until_revaluation_exists(): void
    {
        $this->seedReceivedHeaderTaxInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-tax-1',
            ]), $this->updatePayload([
                'tax_input' => '10%',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 18000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', [
            'supplierInvoiceId' => 'invoice-tax-1',
        ]));
        $response->assertSessionHasErrors(['supplier_invoice']);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-tax-1',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'last_revision_no' => 1,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 11000,
            'inventory_value_rupiah' => 22000,
        ]);
    }

    public function test_received_invoice_qty_increase_with_same_unit_cost_is_allowed(): void
    {
        $this->seedReceivedNoTaxInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-received-tax-1',
            ]), $this->updatePayload([
                'tax_input' => null,
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-received-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 3,
                        'line_total_rupiah' => 30000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-received-tax-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-received-tax-1',
            'grand_total_rupiah' => 30000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-received-tax-1', 'product-tax-1');

        $this->assertSame(3, (int) $line->qty_pcs);
        $this->assertSame(30000, (int) $line->line_total_rupiah);
        $this->assertSame(10000, (int) $line->unit_cost_rupiah);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-tax-1',
            'qty_on_hand' => 3,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 30000,
        ]);
    }

    public function test_received_invoice_qty_decrease_with_same_unit_cost_is_allowed_when_stock_is_available(): void
    {
        $this->seedReceivedNoTaxInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-received-tax-1',
            ]), $this->updatePayload([
                'tax_input' => null,
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-received-tax-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-tax-1',
                        'qty_pcs' => 1,
                        'line_total_rupiah' => 10000,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-received-tax-1',
        ]));

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-received-tax-1',
            'grand_total_rupiah' => 10000,
            'last_revision_no' => 2,
        ]);

        $line = $this->currentLine('invoice-received-tax-1', 'product-tax-1');

        $this->assertSame(1, (int) $line->qty_pcs);
        $this->assertSame(10000, (int) $line->line_total_rupiah);
        $this->assertSame(10000, (int) $line->unit_cost_rupiah);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-tax-1',
            'qty_on_hand' => 1,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 10000,
        ]);
    }

    private function updatePayload(array $overrides): array
    {
        return array_replace_recursive([
            'expected_revision_no' => 1,
            'change_reason' => 'Regression test pajak faktur supplier.',
            'nomor_faktur' => 'INV-TAX-001',
            'nama_pt_pengirim' => 'PT Supplier Pajak',
            'tanggal_pengiriman' => '2026-03-15',
            'lines' => [
                [
                    'previous_line_id' => 'invoice-tax-line-1',
                    'line_no' => 1,
                    'product_id' => 'product-tax-1',
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
            ],
        ], $overrides);
    }

    private function seedLegacyNoTaxInvoice(
        string $invoiceId,
        string $lineId,
        int $lineTotalRupiah,
        int $unitCostRupiah
    ): void {
        $this->seedSupplierAndProduct();

        DB::table('supplier_invoices')->insert([
            'id' => $invoiceId,
            'supplier_id' => 'supplier-tax-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Pajak',
            'nomor_faktur' => 'INV-LEGACY-001',
            'nomor_faktur_normalized' => 'inv-legacy-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-15',
            'subtotal_before_tax_rupiah' => $lineTotalRupiah,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_rate_basis_points' => null,
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => $lineTotalRupiah,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        $this->insertInvoiceLine(
            $lineId,
            $invoiceId,
            2,
            $lineTotalRupiah,
            $unitCostRupiah,
            $lineTotalRupiah,
        );
    }

    private function seedHeaderTaxInvoice(): void
    {
        $this->seedSupplierAndProduct();

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-tax-1',
            'supplier_id' => 'supplier-tax-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Pajak',
            'nomor_faktur' => 'INV-TAX-001',
            'nomor_faktur_normalized' => 'inv-tax-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-15',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => '10%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1000,
            'tax_amount_rupiah' => 2000,
            'grand_total_rupiah' => 22000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        $this->insertInvoiceLine(
            'invoice-tax-line-1',
            'invoice-tax-1',
            2,
            22000,
            11000,
            20000,
        );
    }

    private function seedReceivedHeaderTaxInvoice(): void
    {
        $this->seedHeaderTaxInvoice();

        DB::table('supplier_receipts')->insert([
            'id' => 'receipt-tax-1',
            'supplier_invoice_id' => 'invoice-tax-1',
            'tanggal_terima' => '2026-03-15',
        ]);

        DB::table('supplier_receipt_lines')->insert([
            'id' => 'receipt-tax-line-1',
            'supplier_receipt_id' => 'receipt-tax-1',
            'supplier_invoice_line_id' => 'invoice-tax-line-1',
            'qty_diterima' => 2,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'movement-tax-1',
            'product_id' => 'product-tax-1',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => 'receipt-tax-line-1',
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 11000,
            'total_cost_rupiah' => 22000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-tax-1',
            'qty_on_hand' => 2,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 11000,
            'inventory_value_rupiah' => 22000,
        ]);
    }

    private function seedReceivedNoTaxInvoice(): void
    {
        $this->seedSupplierAndProduct();

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-received-tax-1',
            'supplier_id' => 'supplier-tax-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Pajak',
            'nomor_faktur' => 'INV-TAX-001',
            'nomor_faktur_normalized' => 'inv-tax-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-15',
            'subtotal_before_tax_rupiah' => 20000,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_rate_basis_points' => null,
            'tax_amount_rupiah' => 0,
            'grand_total_rupiah' => 20000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        $this->insertInvoiceLine(
            'invoice-received-tax-line-1',
            'invoice-received-tax-1',
            2,
            20000,
            10000,
            20000,
        );

        DB::table('supplier_receipts')->insert([
            'id' => 'receipt-tax-1',
            'supplier_invoice_id' => 'invoice-received-tax-1',
            'tanggal_terima' => '2026-03-15',
        ]);

        DB::table('supplier_receipt_lines')->insert([
            'id' => 'receipt-tax-line-1',
            'supplier_receipt_id' => 'receipt-tax-1',
            'supplier_invoice_line_id' => 'invoice-received-tax-line-1',
            'qty_diterima' => 2,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'movement-tax-1',
            'product_id' => 'product-tax-1',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => 'receipt-tax-line-1',
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 20000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-tax-1',
            'qty_on_hand' => 2,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 20000,
        ]);
    }

    private function seedSupplierAndProduct(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-tax-1',
            'nama_pt_pengirim' => 'PT Supplier Pajak',
            'nama_pt_pengirim_normalized' => 'pt supplier pajak',
        ]);

        DB::table('products')->insert([
            'id' => 'product-tax-1',
            'kode_barang' => 'TAX-001',
            'nama_barang' => 'Barang Pajak',
            'nama_barang_normalized' => 'barang pajak',
            'merek' => 'Federal',
            'merek_normalized' => 'federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);
    }

    private function insertInvoiceLine(
        string $id,
        string $supplierInvoiceId,
        int $qtyPcs,
        int $lineTotalRupiah,
        int $unitCostRupiah,
        int $lineSubtotalBeforeTaxRupiah,
    ): void {
        DB::table('supplier_invoice_lines')->insert([
            'id' => $id,
            'supplier_invoice_id' => $supplierInvoiceId,
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 1,
            'product_id' => 'product-tax-1',
            'product_kode_barang_snapshot' => 'TAX-001',
            'product_nama_barang_snapshot' => 'Barang Pajak',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => $qtyPcs,
            'line_total_rupiah' => $lineTotalRupiah,
            'unit_cost_rupiah' => $unitCostRupiah,
            'line_subtotal_before_tax_rupiah' => $lineSubtotalBeforeTaxRupiah,
            'tax_input' => null,
            'tax_mode' => 'none',
            'tax_rate_basis_points' => null,
            'tax_amount_rupiah' => 0,
        ]);
    }

    private function seedPayment(string $id, string $supplierInvoiceId, int $amountRupiah): void
    {
        DB::table('supplier_payments')->insert([
            'id' => $id,
            'supplier_invoice_id' => $supplierInvoiceId,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => '2026-03-16',
            'proof_status' => 'approved',
            'proof_storage_path' => 'proofs/payment-tax.jpg',
        ]);
    }

    private function seedPaymentReversal(string $id, string $supplierPaymentId): void
    {
        DB::table('supplier_payment_reversals')->insert([
            'id' => $id,
            'supplier_payment_id' => $supplierPaymentId,
            'reason' => 'Regression test reversal pembayaran supplier.',
            'performed_by_actor_id' => 'admin-test',
            'created_at' => '2026-03-17 08:00:00',
            'updated_at' => '2026-03-17 08:00:00',
        ]);
    }

    private function currentLine(string $supplierInvoiceId, string $productId): object
    {
        $line = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->where('product_id', $productId)
            ->where('is_current', true)
            ->first();

        $this->assertNotNull($line);

        return $line;
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-supplier-tax-invariant@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
