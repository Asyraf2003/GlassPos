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
        Schema::create('transaction_workspace_drafts', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('actor_id');
            $table->string('workspace_mode');
            $table->string('workspace_key');
            $table->string('note_id')->nullable();
            $table->json('payload_json');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('actor_id');
            $table->index('workspace_mode');
            $table->index('note_id');
            $table->unique(['actor_id', 'workspace_key'], 'twd_actor_workspace_key_unique');
        });

        $this->addMysqlJsonValidityCheck('transaction_workspace_drafts', 'payload_json', 'twd_payload_json_valid_chk');
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_workspace_drafts');
    }
    private function addMysqlJsonValidityCheck(string $table, string $column, string $constraint): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` CHECK (JSON_VALID(`%s`))',
            $table,
            $constraint,
            $column
        ));
    }

};
