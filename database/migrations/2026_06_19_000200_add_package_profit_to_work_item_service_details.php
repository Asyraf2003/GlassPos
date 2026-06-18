<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_item_service_details', function (Blueprint $table): void {
            $table->integer('package_profit_rupiah')->default(0)->after('service_price_rupiah');
            $table->integer('package_base_service_price_rupiah')->nullable()->after('package_profit_rupiah');
            $table->integer('package_service_extra_rupiah')->default(0)->after('package_base_service_price_rupiah');
        });
    }

    public function down(): void
    {
        Schema::table('work_item_service_details', function (Blueprint $table): void {
            $table->dropColumn([
                'package_profit_rupiah',
                'package_base_service_price_rupiah',
                'package_service_extra_rupiah',
            ]);
        });
    }
};
