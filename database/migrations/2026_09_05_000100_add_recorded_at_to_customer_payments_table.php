<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->dateTime('recorded_at', 6)->nullable()->after('paid_at');
            $table->index(['recorded_at', 'id'], 'customer_payments_recorded_at_id_idx');
        });

        DB::table('customer_payments')
            ->whereNull('recorded_at')
            ->update(['recorded_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->dropIndex('customer_payments_recorded_at_id_idx');
            $table->dropColumn('recorded_at');
        });
    }
};
