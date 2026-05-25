<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_records', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('actor_id');
            $table->string('operation');
            $table->string('idempotency_key');
            $table->string('request_hash');
            $table->string('status');
            $table->string('response_type')->nullable();
            $table->string('result_note_id')->nullable();
            $table->json('result_payload_json')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['actor_id', 'operation', 'idempotency_key'],
                'idempotency_records_scope_key_unique'
            );
            $table->index(['operation', 'status'], 'idempotency_records_operation_status_idx');
            $table->index(['expires_at'], 'idempotency_records_expires_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_records');
    }
};
