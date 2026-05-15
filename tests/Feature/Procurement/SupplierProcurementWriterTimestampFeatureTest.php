<?php

declare(strict_types=1);

use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptWriterAdapter;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Procurement\SupplierPayment\SupplierPayment;
use App\Core\Procurement\SupplierReceipt\SupplierReceipt;
use App\Core\Procurement\SupplierReceipt\SupplierReceiptLine;
use App\Core\Shared\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('supplier invoice writer stores operational timestamps on create', function (): void {
    seedSupplierAndProduct();

    $line = SupplierInvoiceLine::create(
        'sil-writer-ts-1',
        1,
        'product-writer-ts-1',
        'P-WRITER-1',
        'Oli Mesin',
        'Federal',
        1,
        2,
        Money::fromInt(50_000),
    );

    $invoice = SupplierInvoice::create(
        'supplier-invoice-writer-ts-1',
        'supplier-writer-ts-1',
        'PT Supplier Timestamp',
        'INV-WRITER-1',
        new DateTimeImmutable('2026-05-15'),
        [$line],
    );

    (new DatabaseSupplierInvoiceWriterAdapter())->create($invoice);

    $row = DB::table('supplier_invoices')
        ->where('id', 'supplier-invoice-writer-ts-1')
        ->first();

    expect($row)->not->toBeNull();
    expect($row->created_at)->not->toBeNull();
    expect($row->updated_at)->not->toBeNull();
});

it('supplier receipt writer stores operational timestamps on create', function (): void {
    seedSupplierInvoiceLineFixture();

    $line = SupplierReceiptLine::create(
        'supplier-receipt-line-writer-ts-1',
        'supplier-invoice-line-fixture-ts-1',
        2,
    );

    $receipt = SupplierReceipt::create(
        'supplier-receipt-writer-ts-1',
        'supplier-invoice-fixture-ts-1',
        new DateTimeImmutable('2026-05-16'),
        [$line],
    );

    (new DatabaseSupplierReceiptWriterAdapter())->create($receipt);

    $row = DB::table('supplier_receipts')
        ->where('id', 'supplier-receipt-writer-ts-1')
        ->first();

    expect($row)->not->toBeNull();
    expect($row->created_at)->not->toBeNull();
    expect($row->updated_at)->not->toBeNull();
});

it('supplier payment writer stores operational timestamps on create', function (): void {
    seedSupplierInvoiceFixture();

    $payment = SupplierPayment::create(
        'supplier-payment-writer-ts-1',
        'supplier-invoice-fixture-ts-1',
        Money::fromInt(25_000),
        new DateTimeImmutable('2026-05-17'),
        SupplierPayment::PROOF_STATUS_PENDING,
        null,
    );

    (new DatabaseSupplierPaymentWriterAdapter())->create($payment);

    $row = DB::table('supplier_payments')
        ->where('id', 'supplier-payment-writer-ts-1')
        ->first();

    expect($row)->not->toBeNull();
    expect($row->created_at)->not->toBeNull();
    expect($row->updated_at)->not->toBeNull();
});

it('supplier invoice writer preserves created at and updates updated at on update', function (): void {
    seedSupplierInvoiceLineFixture();

    DB::table('supplier_invoices')
        ->where('id', 'supplier-invoice-fixture-ts-1')
        ->update([
            'created_at' => '2026-05-01 10:00:00',
            'updated_at' => '2026-05-01 10:00:00',
        ]);

    $line = SupplierInvoiceLine::create(
        'supplier-invoice-line-fixture-ts-1',
        1,
        'product-writer-ts-1',
        'P-WRITER-1',
        'Oli Mesin Updated',
        'Federal',
        1,
        2,
        Money::fromInt(60_000),
    );

    $invoice = SupplierInvoice::create(
        'supplier-invoice-fixture-ts-1',
        'supplier-writer-ts-1',
        'PT Supplier Timestamp',
        'INV-FIXTURE-UPDATED',
        new DateTimeImmutable('2026-05-18'),
        [$line],
    );

    Carbon::setTestNow('2026-05-20 12:34:56');

    try {
        (new DatabaseSupplierInvoiceWriterAdapter())->update($invoice);
    } finally {
        Carbon::setTestNow();
    }

    $row = DB::table('supplier_invoices')
        ->where('id', 'supplier-invoice-fixture-ts-1')
        ->first();

    expect($row)->not->toBeNull();
    expect((string) $row->created_at)->toBe('2026-05-01 10:00:00');
    expect((string) $row->updated_at)->toBe('2026-05-20 12:34:56');
});

it('supplier payment writer preserves created at and updates updated at on update', function (): void {
    seedSupplierInvoiceFixture();

    DB::table('supplier_payments')->insert([
        'id' => 'supplier-payment-fixture-ts-1',
        'supplier_invoice_id' => 'supplier-invoice-fixture-ts-1',
        'amount_rupiah' => 25_000,
        'paid_at' => '2026-05-17',
        'proof_status' => SupplierPayment::PROOF_STATUS_PENDING,
        'proof_storage_path' => null,
        'created_at' => '2026-05-01 11:00:00',
        'updated_at' => '2026-05-01 11:00:00',
    ]);

    $payment = SupplierPayment::create(
        'supplier-payment-fixture-ts-1',
        'supplier-invoice-fixture-ts-1',
        Money::fromInt(25_000),
        new DateTimeImmutable('2026-05-17'),
        SupplierPayment::PROOF_STATUS_UPLOADED,
        'supplier-payments/proofs/proof-1.jpg',
    );

    Carbon::setTestNow('2026-05-21 13:45:01');

    try {
        (new DatabaseSupplierPaymentWriterAdapter())->update($payment);
    } finally {
        Carbon::setTestNow();
    }

    $row = DB::table('supplier_payments')
        ->where('id', 'supplier-payment-fixture-ts-1')
        ->first();

    expect($row)->not->toBeNull();
    expect((string) $row->created_at)->toBe('2026-05-01 11:00:00');
    expect((string) $row->updated_at)->toBe('2026-05-21 13:45:01');
});

function seedSupplierAndProduct(): void
{
    DB::table('suppliers')->insert([
        'id' => 'supplier-writer-ts-1',
        'nama_pt_pengirim' => 'PT Supplier Timestamp',
        'nama_pt_pengirim_normalized' => 'pt supplier timestamp',
    ]);

    DB::table('products')->insert([
        'id' => 'product-writer-ts-1',
        'kode_barang' => 'P-WRITER-1',
        'nama_barang' => 'Oli Mesin',
        'merek' => 'Federal',
        'ukuran' => 1,
        'harga_jual' => 75_000,
    ]);
}

function seedSupplierInvoiceFixture(): void
{
    seedSupplierAndProduct();

    DB::table('supplier_invoices')->insert([
        'id' => 'supplier-invoice-fixture-ts-1',
        'supplier_id' => 'supplier-writer-ts-1',
        'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Timestamp',
        'nomor_faktur' => 'INV-FIXTURE-1',
        'nomor_faktur_normalized' => 'inv-fixture-1',
        'document_kind' => 'invoice',
        'lifecycle_status' => 'active',
        'origin_supplier_invoice_id' => null,
        'superseded_by_supplier_invoice_id' => null,
        'tanggal_pengiriman' => '2026-05-15',
        'jatuh_tempo' => '2026-06-14',
        'grand_total_rupiah' => 50_000,
        'voided_at' => null,
        'void_reason' => null,
        'last_revision_no' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function seedSupplierInvoiceLineFixture(): void
{
    seedSupplierInvoiceFixture();

    DB::table('supplier_invoice_lines')->insert([
        'id' => 'supplier-invoice-line-fixture-ts-1',
        'supplier_invoice_id' => 'supplier-invoice-fixture-ts-1',
        'line_no' => 1,
        'product_id' => 'product-writer-ts-1',
        'product_kode_barang_snapshot' => 'P-WRITER-1',
        'product_nama_barang_snapshot' => 'Oli Mesin',
        'product_merek_snapshot' => 'Federal',
        'product_ukuran_snapshot' => 1,
        'qty_pcs' => 2,
        'line_total_rupiah' => 50_000,
        'unit_cost_rupiah' => 25_000,
    ]);
}
