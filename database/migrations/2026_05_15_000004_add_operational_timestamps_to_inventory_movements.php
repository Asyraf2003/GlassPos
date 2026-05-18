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
        Schema::table('inventory_movements', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventory_movements', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('inventory_movements', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        $now = now()->toDateTimeString();

        DB::table('inventory_movements')
            ->whereNull('created_at')
            ->update(['created_at' => $now]);

        DB::table('inventory_movements')
            ->whereNull('updated_at')
            ->update(['updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            if (Schema::hasColumn('inventory_movements', 'updated_at')) {
                $table->dropColumn('updated_at');
            }

            if (Schema::hasColumn('inventory_movements', 'created_at')) {
                $table->dropColumn('created_at');
            }
        });
    }
};
