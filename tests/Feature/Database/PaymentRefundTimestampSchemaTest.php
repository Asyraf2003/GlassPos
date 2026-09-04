<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has operational timestamps on customer payment and refund persistence tables', function (): void {
    $expectedColumns = [
        'customer_payments' => [
            'recorded_at',
            'created_at',
            'updated_at',
        ],
        'customer_refunds' => [
            'created_at',
            'updated_at',
        ],
        'customer_payment_cash_details' => [
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
