<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            if (! Schema::hasColumn('notes', 'current_revision_id')) {
                $table->string('current_revision_id')->nullable();
            }

            if (! Schema::hasColumn('notes', 'latest_revision_number')) {
                $table->integer('latest_revision_number')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            if (Schema::hasColumn('notes', 'latest_revision_number')) {
                $table->dropColumn('latest_revision_number');
            }

            if (Schema::hasColumn('notes', 'current_revision_id')) {
                $table->dropColumn('current_revision_id');
            }
        });
    }
};
