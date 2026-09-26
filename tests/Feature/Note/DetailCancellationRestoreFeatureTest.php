<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Ports\Out\IdentityAccess\AdminTransactionCapabilityStatePort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class DetailCancellationRestoreFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_rendered_forms_cancel_then_restore_as_new_revision_with_visible_history(): void
    {
        $this->preparePrimitiveFixture();
        $actor = $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[0]], 'ui-create'))->assertSessionHasNoErrors();
        $note = (string) DB::table('notes')->value('id');
        $url = route('cashier.notes.show', ['noteId' => $note]);
        $page = $this->get($url)->assertOk()->assertSee('Batalkan Transaksi')->assertSee('Tagihan aktif menjadi Rp 0');
        $cancel = $this->form($page->getContent(), 'note-lifecycle-form');
        $cancel['reason'] = 'Batal <script>alert(1)</script>';
        $this->from($url)->post(route('cashier.notes.cancel', ['noteId' => $note]), $cancel)->assertRedirect($url)->assertSessionHasNoErrors();
        $page = $this->get($url)->assertSee('Pulihkan')->assertSee('Revisi baru')->assertSee(e($cancel['reason']), false)
            ->assertDontSee($cancel['reason'], false)->assertSee((string) $actor->getAuthIdentifier());
        $restore = $this->form($page->getContent(), 'note-lifecycle-form');
        self::assertSame($cancel['base_revision_id'], $restore['base_revision_id']);
        self::assertSame($restore['base_revision_id'], $restore['source_revision_id']);
        $restore['reason'] = 'Pelanggan melanjutkan transaksi';
        $this->from($url)->post(route('cashier.notes.restore', ['noteId' => $note]), $restore)->assertRedirect($url)->assertSessionHasNoErrors();
        $this->get($url)->assertSee($restore['reason'])->assertSee('Batalkan Transaksi');
        $this->assertDatabaseCount('note_revisions', 2);
        self::assertNotSame($restore['base_revision_id'], DB::table('notes')->value('current_revision_id'));
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => 14]);
        $this->postJson(route('cashier.notes.cancel', ['noteId' => $note]), [...$cancel, 'idempotency_key' => 'stale-ui'])
            ->assertStatus(409)->assertJsonPath('code', 'STALE_REVISION');
        $this->from($url)->post(route('cashier.notes.cancel', ['noteId' => $note]), [...$cancel, 'idempotency_key' => 'stale-form'])
            ->assertRedirect($url)->assertSessionHasErrors();
        $stale = $this->get($url)->assertSee('Muat ulang halaman');
        $retry = $this->form($stale->getContent(), 'note-lifecycle-form');
        self::assertSame('stale-form', $retry['idempotency_key']);
        self::assertSame($cancel['base_revision_id'], $retry['base_revision_id']);
    }

    public function test_paid_and_external_pages_explain_boundary_without_cancel_form(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        foreach ([1, 3] as $index) {
            $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[$index]], 'ui-boundary-'.$index))->assertSessionHasNoErrors();
            $note = (string) DB::table('work_items')->where('transaction_type', $index === 1 ? 'service_only' : 'service_with_external_purchase')->value('note_id');
            if ($index === 1) {
                $this->post(route('cashier.notes.payments.store', ['noteId' => $note]), $this->primitivePayment('ui-pay', 63719, 'transfer'))->assertSessionHasNoErrors();
            }
            $this->get(route('cashier.notes.show', ['noteId' => $note]))->assertOk()->assertDontSee('id="note-lifecycle-form"', false)
                ->assertSee($index === 1 ? 'Lanjutkan melalui Refund' : 'Pembatalan dan refund pembelian luar belum didukung');
        }
    }

    public function test_admin_read_scope_does_not_grant_lifecycle_mutation_capability(): void
    {
        $this->preparePrimitiveFixture();
        $admin = $this->loginAsAuthorizedAdmin();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[1]], 'ui-admin'))->assertSessionHasNoErrors();
        $note = (string) DB::table('notes')->value('id');
        Carbon::setTestNow('2026-09-19 11:00:00');
        $url = route('admin.notes.show', ['noteId' => $note]);
        $page = $this->get($url)->assertOk();
        $form = $this->form($page->getContent(), 'note-lifecycle-form');
        app(AdminTransactionCapabilityStatePort::class)->deactivate((string) $admin->getAuthIdentifier());
        $this->get($url)->assertOk()->assertDontSee('id="note-lifecycle-form"', false)->assertSee('Aksi transaksi tidak tersedia');
        $this->postJson(route('admin.notes.cancel', ['noteId' => $note]), [...$form, 'reason' => 'Unauthorized attempt'])->assertForbidden();
        $this->assertDatabaseMissing('notes', ['id' => $note, 'note_state' => 'cancelled']);
    }

    private function form(string $html, string $id): array
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $inputs = $xpath->query('//form[@id="'.$id.'"]//input[@name]');
        self::assertGreaterThan(0, $inputs->length);
        $values = [];
        foreach ($inputs as $input) {
            if (! $input instanceof \DOMElement) {
                throw new \RuntimeException('Expected form input element.');
            }
            $values[$input->getAttribute('name')] = $input->getAttribute('value');
        }

        return $values;
    }
}
