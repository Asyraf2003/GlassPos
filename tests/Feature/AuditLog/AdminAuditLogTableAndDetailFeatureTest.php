<?php

declare(strict_types=1);

namespace Tests\Feature\AuditLog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminAuditLogTableAndDetailFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_search_prioritizes_exact_event_before_newer_context_fallback(): void
    {
        $admin = $this->admin();
        $exactId = DB::table('audit_logs')->insertGetId([
            'event' => 'FOCUS_EVENT',
            'context' => json_encode(['reason' => 'Older exact identity'], JSON_THROW_ON_ERROR),
            'created_at' => '2026-04-26 10:00:00',
        ]);
        DB::table('audit_logs')->insert([
            'event' => 'other_event',
            'context' => json_encode(['reason' => 'Newer broad FOCUS_EVENT context'], JSON_THROW_ON_ERROR),
            'created_at' => '2026-04-26 12:00:00',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.audit-logs.table', ['q' => 'FOCUS_EVENT']));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.id', (string) $exactId);
        $response->assertJsonPath('data.meta.per_page', 20);
        $response->assertJsonMissingPath('data.rows.0.context_json');
    }

    public function test_index_replaces_raw_context_with_detail_action_and_detail_escapes_pretty_json(): void
    {
        $admin = $this->admin();
        $id = DB::table('audit_logs')->insertGetId([
            'event' => 'unsafe_context_recorded',
            'context' => json_encode(['unsafe' => '<script>alert("audit")</script>', 'nested' => ['proof' => true]], JSON_THROW_ON_ERROR),
            'created_at' => '2026-04-26 10:00:00',
        ]);

        $index = $this->actingAs($admin)->get(route('admin.audit-logs.index'));
        $index->assertOk();
        $index->assertDontSee('<script>alert("audit")</script>', false);
        $index->assertSee(route('admin.audit-logs.show', ['source' => 'audit_logs', 'auditId' => $id]), false);

        $detail = $this->actingAs($admin)->get(route('admin.audit-logs.show', ['source' => 'audit_logs', 'auditId' => $id]));
        $detail->assertOk();
        $detail->assertSee('&lt;script&gt;', false);
        $detail->assertDontSee('<script>alert("audit")</script>', false);
        $detail->assertSee('&quot;nested&quot;', false);
    }

    public function test_nonexistent_or_unknown_source_detail_returns_not_found(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/audit-logs/unknown/1')->assertNotFound();
        $this->actingAs($admin)->get('/admin/audit-logs/audit_logs/999999')->assertNotFound();
    }

    public function test_source_filter_and_explicit_event_sort_are_applied_before_pagination(): void
    {
        $admin = $this->admin();

        for ($index = 1; $index <= 20; $index++) {
            DB::table('audit_logs')->insert([
                'event' => 'event-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'context' => '{}',
                'created_at' => '2026-04-26 10:00:00',
            ]);
        }
        DB::table('audit_logs')->insert(['event' => 'aaa-first', 'context' => '{}', 'created_at' => '2026-04-26 09:00:00']);
        DB::table('audit_events')->insert([
            'id' => 'audit-event-filtered-out', 'bounded_context' => 'audit', 'aggregate_type' => 'test',
            'aggregate_id' => 'test-1', 'event_name' => '000-v2', 'actor_id' => null, 'actor_role' => null,
            'reason' => null, 'source_channel' => 'test', 'request_id' => null, 'correlation_id' => null,
            'occurred_at' => '2026-04-26 12:00:00', 'metadata_json' => null,
        ]);

        $firstPage = $this->actingAs($admin)->getJson(route('admin.audit-logs.table', [
            'source' => 'audit_logs', 'sort_by' => 'event', 'sort_dir' => 'asc',
        ]));
        $firstPage->assertOk()->assertJsonPath('data.rows.0.event', 'aaa-first');
        $firstPage->assertJsonPath('data.meta.total', 21);
        $firstPage->assertJsonMissing(['id' => 'audit-event-filtered-out']);

        $secondPage = $this->actingAs($admin)->getJson(route('admin.audit-logs.table', [
            'source' => 'audit_logs', 'sort_by' => 'event', 'sort_dir' => 'asc', 'page' => 2,
        ]));
        $secondPage->assertOk()->assertJsonCount(1, 'data.rows');
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Audit Admin',
            'email' => 'admin-audit-table-detail@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
