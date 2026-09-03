<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payment_proof_upload_intents', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('actor_id', 100);
            $table->string('scope_type', 32);
            $table->string('scope_id', 100);
            $table->string('reserved_supplier_payment_id', 100)->nullable();
            $table->string('idempotency_key', 120);
            $table->string('request_hash', 64);
            $table->string('status', 32);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('expires_at');
            $table->json('result_payload_json')->nullable();
            $table->timestamps();

            $table->unique(
                ['actor_id', 'scope_type', 'scope_id', 'idempotency_key'],
                'sp_pui_scope_key_uq'
            );
            $table->index(['status', 'expires_at'], 'sp_pui_status_exp_idx');
            $table->index(['scope_type', 'scope_id'], 'sp_pui_scope_idx');
        });

        Schema::create('supplier_payment_proof_upload_intent_files', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('upload_intent_id', 64);
            $table->smallInteger('ordinal');
            $table->string('staging_path', 512);
            $table->string('final_storage_path', 512)->nullable();
            $table->string('original_filename');
            $table->string('declared_mime_type', 100);
            $table->bigInteger('declared_size_bytes');
            $table->string('verified_mime_type', 100)->nullable();
            $table->bigInteger('verified_size_bytes')->nullable();
            $table->timestamps();

            $table->unique(['upload_intent_id', 'ordinal'], 'sp_puif_intent_ord_uq');
            $table->unique('staging_path', 'sp_puif_staging_uq');
            $table->unique('final_storage_path', 'sp_puif_final_uq');
            $table->index('upload_intent_id', 'sp_puif_intent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_proof_upload_intent_files');
        Schema::dropIfExists('supplier_payment_proof_upload_intents');
    }
};
