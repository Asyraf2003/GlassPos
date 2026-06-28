<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Application\Note\Services\NoteCorrectionHistoryBuilder;
use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierNoteCorrectionHistoryReasonViewFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_note_detail_shows_correction_history_reason(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenServiceOnlyNote();

        DB::table('note_mutation_events')->insert([
            'id' => 'mutation-1',
            'note_id' => 'note-1',
            'mutation_type' => 'paid_service_only_work_item_corrected',
            'actor_id' => 'actor-kasir',
            'actor_role' => 'kasir',
            'reason' => 'Koreksi nominal servis setelah review pelanggan.',
            'occurred_at' => '2026-04-03 09:00:00',
        ]);

        DB::table('note_mutation_snapshots')->insert([
            [
                'id' => 'snapshot-before-1',
                'note_mutation_event_id' => 'mutation-1',
                'snapshot_kind' => 'before',
                'payload_json' => json_encode([
                    'note' => ['total_rupiah' => 50000],
                    'meta' => ['refund_required_rupiah' => 0],
                ], JSON_THROW_ON_ERROR),
                'created_at' => '2026-04-03 09:00:00',
            ],
            [
                'id' => 'snapshot-after-1',
                'note_mutation_event_id' => 'mutation-1',
                'snapshot_kind' => 'after',
                'payload_json' => json_encode([
                    'note' => ['total_rupiah' => 45000],
                    'meta' => ['refund_required_rupiah' => 5000],
                ], JSON_THROW_ON_ERROR),
                'created_at' => '2026-04-03 09:00:00',
            ],
        ]);

        $history = app(NoteCorrectionHistoryBuilder::class)->build('note-1');

        $this->assertSame('Koreksi Nominal Servis', $history[0]['event_label'] ?? null);
        $this->assertSame('Koreksi nominal servis setelah review pelanggan.', $history[0]['reason'] ?? null);

        $page = app(NoteDetailPageDataBuilder::class)->build('note-1');

        $this->assertSame(
            'Koreksi nominal servis setelah review pelanggan.',
            $page['note']['correction_history'][0]['reason'] ?? null
        );

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()
            ->assertSee('Riwayat Mutasi Nota')
            ->assertSee('Koreksi Nominal Servis')
            ->assertSee('Alasan:')
            ->assertSee('Koreksi nominal servis setelah review pelanggan.')
            ->assertSee('Diproses oleh:')
            ->assertSee('actor-kasir');
    }

    private function seedKasir(): User
    {
        $user = User::query()->create([
            'name' => 'Kasir Correction Reason',
            'email' => 'kasir-correction-reason@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedOpenServiceOnlyNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis Lama', 50000, ServiceDetail::PART_SOURCE_NONE);
    }
}
