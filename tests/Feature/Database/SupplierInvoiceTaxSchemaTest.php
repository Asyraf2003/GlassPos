<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has supplier invoice tax source columns', function (): void {
    $expectedColumns = [
        'subtotal_before_tax_rupiah',
        'tax_input',
        'tax_mode',
        'tax_rate_basis_points',
        'tax_amount_rupiah',
    ];

    $missingColumns = [];

    foreach ($expectedColumns as $column) {
        if (! Schema::hasColumn('supplier_invoices', $column)) {
            $missingColumns[] = "supplier_invoices.{$column}";
        }
    }

    expect($missingColumns)->toBe([]);
});

it('defaults supplier invoice tax columns to no tax on fresh rows', function (): void {
    DB::table('suppliers')->insert([
        'id' => 'supplier-tax-schema-1',
        'nama_pt_pengirim' => 'PT Tax Schema',
        'nama_pt_pengirim_normalized' => 'pt tax schema',
    ]);

    DB::table('supplier_invoices')->insert([
        'id' => 'invoice-tax-schema-1',
        'supplier_id' => 'supplier-tax-schema-1',
        'supplier_nama_pt_pengirim_snapshot' => 'PT Tax Schema',
        'nomor_faktur' => 'INV-TAX-SCHEMA-1',
        'nomor_faktur_normalized' => 'inv-tax-schema-1',
        'document_kind' => 'invoice',
        'lifecycle_status' => 'active',
        'origin_supplier_invoice_id' => null,
        'superseded_by_supplier_invoice_id' => null,
        'tanggal_pengiriman' => '2030-01-01',
        'jatuh_tempo' => '2030-02-01',
        'grand_total_rupiah' => 100000,
    ]);

    $row = DB::table('supplier_invoices')
        ->where('id', 'invoice-tax-schema-1')
        ->first();

    expect((int) $row->subtotal_before_tax_rupiah)->toBe(0)
        ->and($row->tax_input)->toBeNull()
        ->and((string) $row->tax_mode)->toBe('none')
        ->and($row->tax_rate_basis_points)->toBeNull()
        ->and((int) $row->tax_amount_rupiah)->toBe(0);
});
