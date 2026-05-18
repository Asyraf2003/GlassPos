<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dateTime('deleted_at')->nullable();
            $table->string('deleted_by_actor_id')->nullable();
            $table->text('delete_reason')->nullable();

            $table->index('deleted_at', 'products_deleted_at_idx');
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dateTime('deleted_at')->nullable();
            $table->string('deleted_by_actor_id')->nullable();
            $table->text('delete_reason')->nullable();

            $table->index('deleted_at', 'suppliers_deleted_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropIndex('suppliers_deleted_at_idx');
            $table->dropColumn([
                'deleted_at',
                'deleted_by_actor_id',
                'delete_reason',
            ]);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_deleted_at_idx');
            $table->dropColumn([
                'deleted_at',
                'deleted_by_actor_id',
                'delete_reason',
            ]);
        });
    }
};
