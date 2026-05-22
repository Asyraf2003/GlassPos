<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CreateAuditBaselineSeeder extends Seeder
{
    private const CORRELATION_ID = 'seed-create-only-audit-baseline-v1';
    private const REASON = 'Create-only seed audit baseline';

    public function run(): void
    {
        $this->assertLocalOrTesting();

        $this->assertTableExists('audit_events');
        $this->assertTableExists('audit_event_snapshots');

        DB::transaction(function (): void {
            foreach ($this->auditSpecs() as $spec) {
                $this->seedAuditEventsForTable($spec);
            }

            $this->seedEmployeeVersions();
            $this->seedSupplierInvoiceVersions();
        });

        $this->command?->info('Create-only audit baseline seeded.');
    }

    private function assertLocalOrTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(static::class.' is only allowed in local/testing environments.');
        }
    }

    private function assertTableExists(string $table): void
    {
        if (! Schema::hasTable($table)) {
            throw new RuntimeException('Required table missing: '.$table);
        }
    }

    /**
     * @return list<array{table:string,id_column:string,bounded_context:string,aggregate_type:string,event_name:string}>
     */
    private function auditSpecs(): array
    {
        return [
            ['table' => 'users', 'id_column' => 'id', 'bounded_context' => 'identity_access', 'aggregate_type' => 'user', 'event_name' => 'user_seeded'],
            ['table' => 'actor_accesses', 'id_column' => 'actor_id', 'bounded_context' => 'identity_access', 'aggregate_type' => 'actor_access', 'event_name' => 'actor_access_seeded'],
            ['table' => 'admin_transaction_capability_states', 'id_column' => 'actor_id', 'bounded_context' => 'identity_access', 'aggregate_type' => 'admin_transaction_capability_state', 'event_name' => 'admin_transaction_capability_seeded'],
            ['table' => 'admin_cashier_area_access_states', 'id_column' => 'actor_id', 'bounded_context' => 'identity_access', 'aggregate_type' => 'admin_cashier_area_access_state', 'event_name' => 'admin_cashier_area_access_seeded'],

            ['table' => 'suppliers', 'id_column' => 'id', 'bounded_context' => 'procurement', 'aggregate_type' => 'supplier', 'event_name' => 'supplier_seeded'],
            ['table' => 'supplier_invoices', 'id_column' => 'id', 'bounded_context' => 'procurement', 'aggregate_type' => 'supplier_invoice', 'event_name' => 'supplier_invoice_seeded'],
            ['table' => 'supplier_invoice_lines', 'id_column' => 'id', 'bounded_context' => 'procurement', 'aggregate_type' => 'supplier_invoice_line', 'event_name' => 'supplier_invoice_line_seeded'],
            ['table' => 'supplier_receipts', 'id_column' => 'id', 'bounded_context' => 'procurement', 'aggregate_type' => 'supplier_receipt', 'event_name' => 'supplier_receipt_seeded'],
            ['table' => 'supplier_receipt_lines', 'id_column' => 'id', 'bounded_context' => 'procurement', 'aggregate_type' => 'supplier_receipt_line', 'event_name' => 'supplier_receipt_line_seeded'],
            ['table' => 'supplier_payments', 'id_column' => 'id', 'bounded_context' => 'procurement', 'aggregate_type' => 'supplier_payment', 'event_name' => 'supplier_payment_seeded'],
            ['table' => 'supplier_payment_proof_attachments', 'id_column' => 'id', 'bounded_context' => 'procurement', 'aggregate_type' => 'supplier_payment_proof_attachment', 'event_name' => 'supplier_payment_proof_attachment_seeded'],

            ['table' => 'products', 'id_column' => 'id', 'bounded_context' => 'product_catalog', 'aggregate_type' => 'product', 'event_name' => 'product_seeded'],
            ['table' => 'product_inventory', 'id_column' => 'product_id', 'bounded_context' => 'inventory', 'aggregate_type' => 'product_inventory', 'event_name' => 'product_inventory_seeded'],
            ['table' => 'product_inventory_costing', 'id_column' => 'product_id', 'bounded_context' => 'inventory', 'aggregate_type' => 'product_inventory_costing', 'event_name' => 'product_inventory_costing_seeded'],
            ['table' => 'inventory_movements', 'id_column' => 'id', 'bounded_context' => 'inventory', 'aggregate_type' => 'inventory_movement', 'event_name' => 'inventory_movement_seeded'],

            ['table' => 'employees', 'id_column' => 'id', 'bounded_context' => 'employee_finance', 'aggregate_type' => 'employee', 'event_name' => 'employee_seeded'],
            ['table' => 'employee_debts', 'id_column' => 'id', 'bounded_context' => 'employee_finance', 'aggregate_type' => 'employee_debt', 'event_name' => 'employee_debt_seeded'],
            ['table' => 'employee_debt_payments', 'id_column' => 'id', 'bounded_context' => 'employee_finance', 'aggregate_type' => 'employee_debt_payment', 'event_name' => 'employee_debt_payment_seeded'],
            ['table' => 'employee_debt_adjustments', 'id_column' => 'id', 'bounded_context' => 'employee_finance', 'aggregate_type' => 'employee_debt_adjustment', 'event_name' => 'employee_debt_adjustment_seeded'],

            ['table' => 'expense_categories', 'id_column' => 'id', 'bounded_context' => 'expense', 'aggregate_type' => 'expense_category', 'event_name' => 'expense_category_seeded'],
            ['table' => 'operational_expenses', 'id_column' => 'id', 'bounded_context' => 'expense', 'aggregate_type' => 'operational_expense', 'event_name' => 'operational_expense_seeded'],

            ['table' => 'payroll_disbursements', 'id_column' => 'id', 'bounded_context' => 'payroll', 'aggregate_type' => 'payroll_disbursement', 'event_name' => 'payroll_disbursement_seeded'],
        ];
    }

    /**
     * @param array{table:string,id_column:string,bounded_context:string,aggregate_type:string,event_name:string} $spec
     */
    private function seedAuditEventsForTable(array $spec): void
    {
        if (! Schema::hasTable($spec['table']) || ! Schema::hasColumn($spec['table'], $spec['id_column'])) {
            $this->command?->warn('Skipped audit baseline table: '.$spec['table']);

            return;
        }

        DB::table($spec['table'])
            ->orderBy($spec['id_column'])
            ->chunk(200, function ($rows) use ($spec): void {
                foreach ($rows as $row) {
                    $payload = (array) $row;
                    $aggregateId = (string) ($payload[$spec['id_column']] ?? '');

                    if ($aggregateId === '') {
                        continue;
                    }

                    $this->insertAuditBaselineEvent(
                        table: $spec['table'],
                        aggregateId: $aggregateId,
                        boundedContext: $spec['bounded_context'],
                        aggregateType: $spec['aggregate_type'],
                        eventName: $spec['event_name'],
                        payload: $payload,
                        occurredAt: $this->occurredAt($payload),
                    );
                }
            });
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function insertAuditBaselineEvent(
        string $table,
        string $aggregateId,
        string $boundedContext,
        string $aggregateType,
        string $eventName,
        array $payload,
        string $occurredAt,
    ): void {
        $auditEventId = $this->auditEventId($table, $aggregateId);
        $snapshotId = $this->snapshotId($auditEventId, 'after');

        if (! DB::table('audit_events')->where('id', $auditEventId)->exists()) {
            DB::table('audit_events')->insert([
                'id' => $auditEventId,
                'bounded_context' => $boundedContext,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'event_name' => $eventName,
                'actor_id' => null,
                'actor_role' => 'system',
                'reason' => self::REASON,
                'source_channel' => 'seed',
                'request_id' => null,
                'correlation_id' => self::CORRELATION_ID,
                'occurred_at' => $occurredAt,
                'metadata_json' => $this->json([
                    'seed' => true,
                    'source' => 'create-only',
                    'table' => $table,
                    'correlation_id' => self::CORRELATION_ID,
                ]),
            ]);
        }

        if (! DB::table('audit_event_snapshots')
            ->where('audit_event_id', $auditEventId)
            ->where('snapshot_kind', 'after')
            ->exists()) {
            DB::table('audit_event_snapshots')->insert([
                'id' => $snapshotId,
                'audit_event_id' => $auditEventId,
                'snapshot_kind' => 'after',
                'payload_json' => $this->json([
                    'table' => $table,
                    'row' => $payload,
                ]),
                'created_at' => $occurredAt,
            ]);
        }
    }

    private function seedEmployeeVersions(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasTable('employee_versions')) {
            return;
        }

        DB::table('employees')
            ->orderBy('id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    $payload = (array) $row;
                    $employeeId = (string) ($payload['id'] ?? '');

                    if ($employeeId === '') {
                        continue;
                    }

                    if (DB::table('employee_versions')
                        ->where('employee_id', $employeeId)
                        ->where('revision_no', 1)
                        ->exists()) {
                        continue;
                    }

                    $changedAt = $this->occurredAt($payload);

                    DB::table('employee_versions')->insert([
                        'id' => $this->versionId('employee', $employeeId, 1),
                        'employee_id' => $employeeId,
                        'revision_no' => 1,
                        'event_name' => 'employee_seeded',
                        'changed_by_actor_id' => null,
                        'change_reason' => self::REASON,
                        'changed_at' => $changedAt,
                        'snapshot_json' => $this->json($payload),
                    ]);
                }
            });
    }

    private function seedSupplierInvoiceVersions(): void
    {
        if (
            ! Schema::hasTable('supplier_invoices')
            || ! Schema::hasTable('supplier_invoice_lines')
            || ! Schema::hasTable('supplier_invoice_versions')
        ) {
            return;
        }

        DB::table('supplier_invoices')
            ->orderBy('id')
            ->chunk(100, function ($rows): void {
                foreach ($rows as $row) {
                    $invoice = (array) $row;
                    $invoiceId = (string) ($invoice['id'] ?? '');

                    if ($invoiceId === '') {
                        continue;
                    }

                    $revisionNo = (int) ($invoice['last_revision_no'] ?? 0);

                    if (DB::table('supplier_invoice_versions')
                        ->where('supplier_invoice_id', $invoiceId)
                        ->where('revision_no', $revisionNo)
                        ->exists()) {
                        continue;
                    }

                    $lines = DB::table('supplier_invoice_lines')
                        ->where('supplier_invoice_id', $invoiceId)
                        ->orderBy('line_no')
                        ->get()
                        ->map(static fn (object $line): array => (array) $line)
                        ->all();

                    $changedAt = $this->occurredAt($invoice);

                    DB::table('supplier_invoice_versions')->insert([
                        'id' => $this->versionId('supplier-invoice', $invoiceId, $revisionNo),
                        'supplier_invoice_id' => $invoiceId,
                        'revision_no' => $revisionNo,
                        'event_name' => 'supplier_invoice_seeded',
                        'changed_by_actor_id' => null,
                        'change_reason' => self::REASON,
                        'changed_at' => $changedAt,
                        'snapshot_json' => $this->json([
                            'invoice' => $invoice,
                            'lines' => $lines,
                        ]),
                    ]);
                }
            });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function occurredAt(array $row): string
    {
        foreach ([
            'created_at',
            'updated_at',
            'paid_at',
            'tanggal_terima',
            'tanggal_pengiriman',
            'expense_date',
            'tanggal_mutasi',
            'started_at',
        ] as $key) {
            $value = $row[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $value = trim($value);

                return strlen($value) === 10 ? $value.' 00:00:00' : $value;
            }
        }

        return now()->format('Y-m-d H:i:s');
    }

    private function auditEventId(string $table, string $aggregateId): string
    {
        return 'seed-audit-'.sha1($table.'|'.$aggregateId);
    }

    private function snapshotId(string $auditEventId, string $kind): string
    {
        return 'seed-snapshot-'.sha1($auditEventId.'|'.$kind);
    }

    private function versionId(string $type, string $id, int $revisionNo): string
    {
        return 'seed-version-'.sha1($type.'|'.$id.'|'.$revisionNo);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
