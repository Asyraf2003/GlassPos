<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsSupplierPayablePrecisionMatrixFixture;
use Tests\TestCase;

final class ExtremeSupplierPayablePrecisionMatrixFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsSupplierPayablePrecisionMatrixFixture;

    public function test_due_today_outstanding_one_rupiah_is_precise(): void
    {
        $this->seedProduct();
        $this->seedSupplier('supplier-1', 'PT A');
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-20', '2026-03-20', 10000);
        $this->seedLine('line-1', 'invoice-1', 1, 10000, 10000);
        $this->seedPayment('payment-1', 'invoice-1', 9999, '2026-03-20');
        $row = $this->summary('2026-03-20', '2026-03-20', '2026-03-20')[0];
        $this->assertSame(1, $row['outstanding_rupiah']);
        $this->assertSame('due_today', $row['due_status']);
    }

    public function test_settled_invoice_has_zero_outstanding(): void
    {
        $this->seedProduct();
        $this->seedSupplier('supplier-1', 'PT A');
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-20', '2026-03-21', 10000);
        $this->seedLine('line-1', 'invoice-1', 1, 10000, 10000);
        $this->seedPayment('payment-1', 'invoice-1', 10000, '2026-03-20');
        $row = $this->summary('2026-03-20', '2026-03-20', '2026-03-20')[0];
        $this->assertSame(0, $row['outstanding_rupiah']);
        $this->assertSame('settled', $row['due_status']);
    }

    public function test_not_due_invoice_stays_not_due_when_outstanding_positive(): void
    {
        $this->seedProduct();
        $this->seedSupplier('supplier-1', 'PT A');
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-20', '2026-03-21', 10000);
        $this->seedLine('line-1', 'invoice-1', 1, 10000, 10000);
        $row = $this->summary('2026-03-20', '2026-03-20', '2026-03-20')[0];
        $this->assertSame(10000, $row['outstanding_rupiah']);
        $this->assertSame('not_due', $row['due_status']);
    }

    public function test_overdue_invoice_stays_overdue_when_outstanding_positive(): void
    {
        $this->seedProduct();
        $this->seedSupplier('supplier-1', 'PT A');
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-20', '2026-03-19', 10000);
        $this->seedLine('line-1', 'invoice-1', 1, 10000, 10000);
        $this->seedPayment('payment-1', 'invoice-1', 1000, '2026-03-20');
        $row = $this->summary('2026-03-20', '2026-03-20', '2026-03-20')[0];
        $this->assertSame(9000, $row['outstanding_rupiah']);
        $this->assertSame('overdue', $row['due_status']);
    }

    public function test_receipt_aggregation_remains_precise_for_same_invoice(): void
    {
        $this->seedProduct();
        $this->seedSupplier('supplier-1', 'PT A');
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-20', '2026-03-25', 30000);
        $this->seedLine('line-1', 'invoice-1', 3, 30000, 10000);
        $this->seedReceipt('receipt-1', 'invoice-1', '2026-03-20');
        $this->seedReceipt('receipt-2', 'invoice-1', '2026-03-21');
        $this->seedReceiptLine('receipt-line-1', 'receipt-1', 'line-1', 1);
        $this->seedReceiptLine('receipt-line-2', 'receipt-2', 'line-1', 2);
        $row = $this->summary('2026-03-20', '2026-03-20', '2026-03-20')[0];
        $this->assertSame(1, $row['receipt_count']);
        $this->assertSame(1, $row['total_received_qty']);
    }

    public function test_same_supplier_same_product_still_returns_invoice_grain_rows(): void
    {
        $this->seedProduct();
        $this->seedSupplier('supplier-1', 'PT A');
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-20', '2026-03-21', 10000);
        $this->seedInvoice('invoice-2', 'supplier-1', '2026-03-20', '2026-03-22', 20000);
        $this->seedLine('line-1', 'invoice-1', 1, 10000, 10000);
        $this->seedLine('line-2', 'invoice-2', 2, 20000, 10000);
        $rows = $this->summary('2026-03-20', '2026-03-20', '2026-03-20');
        $this->assertCount(2, $rows);
        $this->assertSame('invoice-1', $rows[0]['supplier_invoice_id']);
        $this->assertSame('invoice-2', $rows[1]['supplier_invoice_id']);
    }
}
