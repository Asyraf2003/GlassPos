<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierNoteDetailSimplePaymentModalUxFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_detail_payment_modal_uses_simple_create_like_payment_flow(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 70000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 70000);
        $this->seedServiceDetailBase('wi-1', 'Servis Mesin', 70000, ServiceDetail::PART_SOURCE_NONE);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']));

        $response->assertOk();
        $response->assertSee('Proses Nota');
        $response->assertSee('Bayar Sebagian');
        $response->assertSee('Lunasi');
        $response->assertSee('Bayar Transfer');
        $response->assertSee('Bayar Tunai');
        $response->assertSee('Kalkulator Tunai');
        $response->assertSee('Uang Pelanggan');
        $response->assertSee('Kembalian');
        $response->assertSee('id="note-payment-form"', false);
        $response->assertSee('name="idempotency_key"', false);
        self::assertMatchesRegularExpression(
            '/<input[^>]+type="hidden"[^>]+name="idempotency_key"[^>]+value="[^"]{8,}"/',
            (string) $response->getContent(),
        );
        $response->assertDontSee('Billing Row yang Bisa Dipilih');
        $response->assertDontSee('Mode Bayar');
        $response->assertDontSee('Preset DP');
        $response->assertDontSee('Manual');
        $response->assertDontSee('Billing Row Dipilih');
        $response->assertDontSee('Line Terdampak');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Simple Payment Modal',
            'email' => 'kasir-simple-payment-modal@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
