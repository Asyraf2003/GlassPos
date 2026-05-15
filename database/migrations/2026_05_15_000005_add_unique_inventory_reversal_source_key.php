<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventory_movements', 'reversal_source_id')) {
                $table->string('reversal_source_id', 255)
                    ->nullable()
                    ->virtualAs("CASE WHEN source_type = 'work_item_store_stock_line_reversal' THEN source_id ELSE NULL END");
            }
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->unique(['source_type', 'reversal_source_id'], 'im_unique_reversal_source');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropUnique('im_unique_reversal_source');
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            if (Schema::hasColumn('inventory_movements', 'reversal_source_id')) {
                $table->dropColumn('reversal_source_id');
            }
        });
    }
};
