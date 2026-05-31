<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Ports\Out\ClockPort;
use Illuminate\Support\Facades\DB;
use Tests\Support\NoteDetailOperationalPackageFixture;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteDetailOperationalPackageVisibilityFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;
    use NoteDetailOperationalPackageFixture;

    public function test_detail_shows_operational_note_and_store_stock_package_breakdown(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Detail Package',
            'email' => 'kasir-detail-package@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = $this->app->make(ClockPort::class)->now()->format('Y-m-d');
        $this->seedVisibleStoreStockPackageDetailFixture($today);

        $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-detail-package-1']))
            ->assertOk()
            ->assertSee('Keterangan Nota')
            ->assertSee('Keterangan operasional detail package')
            ->assertSee('Paket total')
            ->assertSee('Total sparepart')
            ->assertSee('Sisa jasa')
            ->assertSee('Filter Oli Detail')
            ->assertSee('Busi Iridium Detail')
            ->assertSee('250.000')
            ->assertSee('130.000')
            ->assertSee('120.000');
    }
}
