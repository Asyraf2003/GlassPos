<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropColumn(['bank_name', 'bank_account_number']);
        });
    }
};
