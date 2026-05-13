<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_revision_surplus_dispositions', function (Blueprint $table): void {
            $table->string('id')->primary();

            $table->string('note_revision_settlement_id');
            $table->string('note_root_id');
            $table->string('note_revision_id');

            $table->string('disposition_type', 32);
            $table->bigInteger('amount_rupiah');
            $table->bigInteger('before_pending_rupiah');
            $table->bigInteger('after_pending_rupiah');

            $table->string('status', 32);
            $table->dateTime('occurred_at');

            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->string('audit_event_id');

            $table->unique('audit_event_id', 'note_revision_surplus_dispositions_audit_event_unique');

            $table->index(
                'note_revision_settlement_id',
                'note_revision_surplus_dispositions_settlement_idx'
            );
            $table->index(
                'note_root_id',
                'note_revision_surplus_dispositions_root_idx'
            );
            $table->index(
                ['note_root_id', 'status'],
                'note_revision_surplus_dispositions_root_status_idx'
            );
            $table->index(
                ['note_revision_settlement_id', 'status'],
                'note_revision_surplus_dispositions_settlement_status_idx'
            );
            $table->index(
                ['note_root_id', 'occurred_at'],
                'note_revision_surplus_dispositions_root_occurred_idx'
            );

            $table->foreign(
                'note_revision_settlement_id',
                'fk_note_revision_surplus_dispositions_settlement'
            )
                ->references('id')
                ->on('note_revision_settlements')
                ->restrictOnDelete();

            $table->foreign(
                'note_revision_id',
                'fk_note_revision_surplus_dispositions_revision'
            )
                ->references('id')
                ->on('note_revisions')
                ->restrictOnDelete();

            $table->foreign(
                'note_root_id',
                'fk_note_revision_surplus_dispositions_note_root'
            )
                ->references('id')
                ->on('notes')
                ->restrictOnDelete();

            $table->foreign(
                'audit_event_id',
                'fk_note_revision_surplus_dispositions_audit_event'
            )
                ->references('id')
                ->on('audit_events')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('note_revision_surplus_dispositions', function (Blueprint $table): void {
            $table->dropForeign('fk_note_revision_surplus_dispositions_audit_event');
            $table->dropForeign('fk_note_revision_surplus_dispositions_note_root');
            $table->dropForeign('fk_note_revision_surplus_dispositions_revision');
            $table->dropForeign('fk_note_revision_surplus_dispositions_settlement');
        });

        Schema::dropIfExists('note_revision_surplus_dispositions');
    }
};
