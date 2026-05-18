<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payment_proof_attachments', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('supplier_payment_id');
            $table->string('storage_path');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->bigInteger('file_size_bytes');
            $table->timestamp('uploaded_at');
            $table->string('uploaded_by_actor_id');

            $table->index('supplier_payment_id', 'sp_pa_payment_idx');
            $table->index('uploaded_at', 'sp_pa_uploaded_idx');
            $table->index(
                ['supplier_payment_id', 'uploaded_at'],
                'sp_pa_payment_uploaded_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_proof_attachments');
    }
};
