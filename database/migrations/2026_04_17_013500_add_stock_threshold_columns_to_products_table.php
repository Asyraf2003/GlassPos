<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'reorder_point_qty')) {
                $table->integer('reorder_point_qty')->nullable();
            }

            if (!Schema::hasColumn('products', 'critical_threshold_qty')) {
                $table->integer('critical_threshold_qty')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'critical_threshold_qty')) {
                $table->dropColumn('critical_threshold_qty');
            }

            if (Schema::hasColumn('products', 'reorder_point_qty')) {
                $table->dropColumn('reorder_point_qty');
            }
        });
    }
};
