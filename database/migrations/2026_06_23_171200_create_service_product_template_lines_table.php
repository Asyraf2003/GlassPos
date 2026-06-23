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
        Schema::create('service_product_template_lines', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('service_product_template_id');
            $table->string('product_id');
            $table->integer('qty')->default(1);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('service_product_template_id', 'svc_product_template_lines_template_idx');
            $table->index('product_id', 'svc_product_template_lines_product_idx');
            $table->unique(
                ['service_product_template_id', 'sort_order'],
                'svc_product_template_lines_template_sort_unique'
            );

            $table->foreign('service_product_template_id', 'fk_svc_product_template_lines_template')
                ->references('id')
                ->on('service_product_templates')
                ->cascadeOnDelete();

            $table->foreign('product_id', 'fk_svc_product_template_lines_product')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();
        });

        $this->addCheckConstraintsWhenSupported();
    }

    public function down(): void
    {
        Schema::dropIfExists('service_product_template_lines');
    }

    private function addCheckConstraintsWhenSupported(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            'ALTER TABLE service_product_template_lines
             ADD CONSTRAINT service_product_template_lines_qty_positive
             CHECK (qty > 0)'
        );

        DB::statement(
            'ALTER TABLE service_product_template_lines
             ADD CONSTRAINT service_product_template_lines_sort_order_range
             CHECK (sort_order BETWEEN 0 AND 2)'
        );
    }
};
