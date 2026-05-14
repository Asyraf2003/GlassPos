<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('customer_refunds', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('customer_payment_cash_details', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('customer_payments')
            ->whereNull('created_at')
            ->update([
                'created_at' => DB::raw('paid_at'),
                'updated_at' => DB::raw('paid_at'),
            ]);

        DB::table('customer_refunds')
            ->whereNull('created_at')
            ->update([
                'created_at' => DB::raw('refunded_at'),
                'updated_at' => DB::raw('refunded_at'),
            ]);

        DB::statement(<<<'SQL'
UPDATE customer_payment_cash_details
SET
    created_at = (
        SELECT customer_payments.paid_at
        FROM customer_payments
        WHERE customer_payments.id = customer_payment_cash_details.customer_payment_id
    ),
    updated_at = (
        SELECT customer_payments.paid_at
        FROM customer_payments
        WHERE customer_payments.id = customer_payment_cash_details.customer_payment_id
    )
WHERE created_at IS NULL
SQL);
    }

    public function down(): void
    {
        Schema::table('customer_payment_cash_details', function (Blueprint $table): void {
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Schema::table('customer_refunds', function (Blueprint $table): void {
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
