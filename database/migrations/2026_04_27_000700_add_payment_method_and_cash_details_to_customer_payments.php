<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->string('payment_method', 20)
                ->default('unknown');

            $table->index(['payment_method', 'paid_at']);
        });

        Schema::create('customer_payment_cash_details', function (Blueprint $table): void {
            $table->string('customer_payment_id')->primary();
            $table->integer('amount_paid_rupiah');
            $table->integer('amount_received_rupiah');
            $table->integer('change_rupiah');

            $table->foreign('customer_payment_id')
                ->references('id')
                ->on('customer_payments')
                ->cascadeOnDelete();

            $table->index('change_rupiah');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_cash_details');

        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->dropIndex(['payment_method', 'paid_at']);
            $table->dropColumn('payment_method');
        });
    }
};
