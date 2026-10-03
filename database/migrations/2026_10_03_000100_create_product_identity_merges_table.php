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
        Schema::create('product_identity_merges', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('source_product_id')->unique();
            $table->string('canonical_product_id');
            $table->string('actor_id');
            $table->text('reason');
            $table->dateTime('occurred_at');
            $table->string('prior_stock_transfer_source_id')->unique();
            $table->foreign('source_product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('canonical_product_id')->references('id')->on('products')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE product_identity_merges ADD CONSTRAINT pim_distinct_products CHECK (source_product_id <> canonical_product_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_identity_merges');
    }
};
