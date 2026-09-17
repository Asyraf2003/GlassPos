<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveRevisionIdentityContractFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_stale_editor_must_not_replace_a_newer_committed_revision(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $item = ['entry_mode' => 'service', 'part_source' => 'none', 'service' => ['name' => 'QA service', 'price_rupiah' => 63719]];
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$item], 'identity-create'))->assertSessionHasNoErrors();
        $id = (string) DB::table('notes')->value('id');
        $edit = route('cashier.notes.workspace.edit', ['noteId' => $id]);
        $update = route('cashier.notes.workspace.update', ['noteId' => $id]);
        // Two editors load R1 before either submission; only existing request fields are used.
        $this->get($edit)->assertOk();
        $first = $this->primitiveWorkspace([$item], 'identity-editor-one');
        $this->get($edit)->assertOk();
        $stale = $this->primitiveWorkspace([$item], 'identity-editor-two');
        $r1 = (string) DB::table('notes')->value('current_revision_id');
        $first['items'][0]['service']['price_rupiah'] = 81258;
        $stale['items'][0]['service']['price_rupiah'] = 51983;
        $this->advancePrimitiveTime();
        $this->patch($update, $first)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('note_revisions', 2);
        $this->assertDatabaseHas('notes', ['id' => $id, 'total_rupiah' => 81258]);
        $r2 = (string) DB::table('notes')->value('current_revision_id');
        self::assertNotSame($r1, $r2);
        $this->advancePrimitiveTime();
        $response = $this->patch($update, $stale);
        $actual = DB::table('notes')->where('id', $id)->first();
        self::assertSame($r2, $actual->current_revision_id, sprintf(
            'Stale R1 editor must preserve R2. Actual revisions=%d, total=%d, pointer=%s, HTTP=%d.',
            DB::table('note_revisions')->count(), $actual->total_rupiah, $actual->current_revision_id, $response->getStatusCode(),
        ));
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('note_revisions', 2);
        $this->assertDatabaseHas('notes', ['id' => $id, 'total_rupiah' => 81258]);
    }
}
