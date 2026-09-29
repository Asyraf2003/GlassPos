<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Ports\Out\Note\TransactionWorkspaceDraftReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionWorkspaceDraftTtlFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_draft_updated_exactly_24_hours_ago_is_still_restorable(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');

        $this->insertDraft(
            actorId: 'actor-fresh-boundary',
            workspaceKey: 'create',
            updatedAt: '2026-09-29 12:00:00',
            customerName: 'Fresh Boundary Customer',
            transactionDate: '2026-09-29',
            paidAt: '2026-09-29',
        );

        $draft = app(TransactionWorkspaceDraftReaderPort::class)
            ->findByActorAndWorkspaceKey('actor-fresh-boundary', 'create');

        self::assertNotNull($draft);
        self::assertSame('Fresh Boundary Customer', $draft['payload']['note']['customer_name']);
    }

    public function test_draft_older_than_24_hours_is_not_restored(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');

        $this->insertDraft(
            actorId: 'actor-stale',
            workspaceKey: 'create',
            updatedAt: '2026-09-29 11:59:59',
            customerName: 'Zombie Draft Customer',
            transactionDate: '2026-06-01',
            paidAt: '2026-06-01',
        );

        $draft = app(TransactionWorkspaceDraftReaderPort::class)
            ->findByActorAndWorkspaceKey('actor-stale', 'create');

        self::assertNull($draft);
    }

    public function test_create_workspace_resets_to_fresh_defaults_when_saved_draft_is_stale(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');

        $user = User::query()->create([
            'name' => 'Kasir Draft TTL',
            'email' => 'draft-ttl@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $this->insertDraft(
            actorId: (string) $user->getAuthIdentifier(),
            workspaceKey: 'create',
            updatedAt: '2026-09-29 11:59:59',
            customerName: 'Zombie Draft Customer',
            transactionDate: '2026-06-01',
            paidAt: '2026-06-01',
        );

        $response = $this->actingAs($user)->get(route('cashier.notes.workspace.create'));

        $response->assertOk();
        $response->assertViewHas('oldNote', static function (array $note): bool {
            return ($note['customer_name'] ?? null) === 'Pelanggan baru'
                && ($note['transaction_date'] ?? null) !== '2026-06-01';
        });
        $response->assertViewHas('oldInlinePayment', static function (array $payment): bool {
            return ($payment['paid_at'] ?? null) !== '2026-06-01';
        });
        $response->assertDontSee('Zombie Draft Customer', false);
    }

    private function insertDraft(
        string $actorId,
        string $workspaceKey,
        string $updatedAt,
        string $customerName,
        string $transactionDate,
        string $paidAt,
    ): void {
        DB::table('transaction_workspace_drafts')->insert([
            'id' => 'draft-'.$actorId,
            'actor_id' => $actorId,
            'workspace_mode' => 'create',
            'workspace_key' => $workspaceKey,
            'note_id' => null,
            'payload_json' => json_encode([
                'note' => [
                    'customer_name' => $customerName,
                    'customer_phone' => '',
                    'transaction_date' => $transactionDate,
                ],
                'items' => [],
                'inline_payment' => [
                    'decision' => 'pay_partial',
                    'payment_method' => 'cash',
                    'paid_at' => $paidAt,
                    'amount_paid_rupiah' => '100000',
                    'amount_received_rupiah' => '100000',
                ],
            ], JSON_THROW_ON_ERROR),
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);
    }
}
