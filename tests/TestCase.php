<?php

namespace Tests;

use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Procurement\Services\SupplierListProjectionService;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Memenuhi Kontrak Identity & Access (Blueprint 1.3.1)
     * Menciptakan User Laravel + Actor Access Domain dengan Role Kasir.
     */
    protected function loginAsKasir(): User
    {
        $user = User::factory()->create();

        DB::table('actor_accesses')->insert([
            'actor_id' => $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Menciptakan Admin yang memiliki kapabilitas transaksi aktif.
     */
    protected function loginAsAuthorizedAdmin(): User
    {
        $user = User::factory()->create();

        DB::table('actor_accesses')->insert([
            'actor_id' => $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->updateOrInsert(
            ['actor_id' => (string) $user->getAuthIdentifier()],
            ['active' => true],
        );

        $this->actingAs($user);

        return $user;
    }

    protected function revisionBaseForTest(string $noteId): string
    {
        app(\App\Application\Note\Services\EnsureInitialNoteRevisionExists::class)
            ->handle($noteId, $noteId.'-r001');
        return (string) DB::table('notes')->where('id', $noteId)->value('current_revision_id');
    }

    protected function syncSupplierInvoiceProjectionForTest(string $supplierInvoiceId): void
    {
        app(SupplierInvoiceListProjectionService::class)
            ->syncInvoice(trim($supplierInvoiceId));
    }

    protected function syncSupplierListProjectionForTest(string $supplierId): void
    {
        app(SupplierListProjectionService::class)
            ->syncSupplier(trim($supplierId));
    }

    protected function syncNoteProjectionForTest(string $noteId): void
    {
        app(NoteHistoryProjectionService::class)
            ->syncNote(trim($noteId));
    }
}
