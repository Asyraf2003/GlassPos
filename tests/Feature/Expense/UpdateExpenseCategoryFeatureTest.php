<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Expense\DatabaseExpenseCategoryReaderAdapter;
use App\Adapters\Out\Expense\DatabaseExpenseCategoryWriterAdapter;
use App\Application\Expense\UseCases\UpdateExpenseCategoryHandler;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\UuidPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UpdateExpenseCategoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_expense_category_updates_row_and_records_canonical_audit(): void
    {
        $this->seedCategory('cat-1', 'EXP-ELEC', 'Listrik', true, 'Lama');

        $handler = new UpdateExpenseCategoryHandler(
            new DatabaseExpenseCategoryReaderAdapter(),
            new DatabaseExpenseCategoryWriterAdapter(),
            app(AuditEventWriterPort::class),
            app(ClockPort::class),
            app(UuidPort::class),
        );

        $result = $handler->handle('cat-1', 'EXP-UTIL', 'Utilitas', 'Baru', 'admin-1');

        $this->assertTrue($result->isSuccess());
        $this->assertDatabaseHas('expense_categories', [
            'id' => 'cat-1',
            'code' => 'EXP-UTIL',
            'name' => 'Utilitas',
            'description' => 'Baru',
            'is_active' => 1,
        ]);

        $event = DB::table('audit_events')->where('event_name', 'expense_category_updated')->first();

        $this->assertNotNull($event);
        $this->assertSame('expense', $event->bounded_context);
        $this->assertSame('expense_category', $event->aggregate_type);
        $this->assertSame('cat-1', $event->aggregate_id);
        $this->assertSame('admin-1', $event->actor_id);

        $metadata = json_decode((string) $event->metadata_json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('cat-1', $metadata['category_id']);
        $this->assertSame('admin-1', $metadata['performed_by_actor_id']);

        $snapshots = DB::table('audit_event_snapshots')
            ->where('audit_event_id', $event->id)
            ->pluck('payload_json', 'snapshot_kind')
            ->all();

        $this->assertArrayHasKey('before', $snapshots);
        $this->assertArrayHasKey('after', $snapshots);

        $before = json_decode((string) $snapshots['before'], true, 512, JSON_THROW_ON_ERROR);
        $after = json_decode((string) $snapshots['after'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('EXP-ELEC', $before['code']);
        $this->assertSame('EXP-UTIL', $after['code']);
        $this->assertSame('Lama', $before['description']);
        $this->assertSame('Baru', $after['description']);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_update_expense_category_rejects_duplicate_code(): void
    {
        $this->seedCategory('cat-1', 'EXP-ELEC', 'Listrik', true, null);
        $this->seedCategory('cat-2', 'EXP-WIFI', 'Wifi', true, null);

        $handler = new UpdateExpenseCategoryHandler(
            new DatabaseExpenseCategoryReaderAdapter(),
            new DatabaseExpenseCategoryWriterAdapter(),
            app(AuditEventWriterPort::class),
            app(ClockPort::class),
            app(UuidPort::class),
        );

        $result = $handler->handle('cat-1', 'EXP-WIFI', 'Utilitas', null, 'admin-1');

        $this->assertTrue($result->isFailure());
        $this->assertSame(['expense_category' => ['EXPENSE_CATEGORY_CODE_ALREADY_EXISTS']], $result->errors());
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertDatabaseCount('audit_event_snapshots', 0);
    }

    private function seedCategory(string $id, string $code, string $name, bool $isActive, ?string $description): void
    {
        DB::table('expense_categories')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
