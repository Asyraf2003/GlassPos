<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('work_items')
            ->select('note_id', 'line_no', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('note_id', 'line_no')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException(sprintf(
                'Cannot add work_items note line unique constraint: duplicate note_id=%s line_no=%s count=%s.',
                (string) $duplicate->note_id,
                (string) $duplicate->line_no,
                (string) $duplicate->duplicate_count
            ));
        }

        Schema::table('work_items', function (Blueprint $table): void {
            $table->unique(['note_id', 'line_no'], 'work_items_note_line_no_unique');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table): void {
            $table->dropUnique('work_items_note_line_no_unique');
        });
    }
};
