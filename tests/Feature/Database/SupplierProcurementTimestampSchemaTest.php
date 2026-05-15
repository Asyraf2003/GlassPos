<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has operational timestamps on supplier procurement root tables', function (): void {
    $expectedColumns = [
        'supplier_invoices' => [
            'created_at',
            'updated_at',
        ],
        'supplier_receipts' => [
            'created_at',
            'updated_at',
        ],
        'supplier_payments' => [
            'created_at',
            'updated_at',
        ],
    ];

    $missingColumns = [];

    foreach ($expectedColumns as $table => $columns) {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                $missingColumns[] = "{$table}.{$column}";
            }
        }
    }

    expect($missingColumns)->toBe([]);
});
