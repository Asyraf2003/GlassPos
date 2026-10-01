<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Application\Procurement\Services\SupplierService;
use App\Ports\Out\Procurement\SupplierReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierBankMasterFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_edit_clear_and_find_or_create_preserve_string_accounts(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.suppliers.store'), [
            'nama_pt_pengirim' => 'PT Bank', 'bank_name' => ' BCA ', 'bank_account_number' => '0012345',
        ])->assertRedirect(route('admin.suppliers.index'))->assertSessionHasNoErrors();
        $supplier = DB::table('suppliers')->first();
        self::assertSame('0012345', $supplier->bank_account_number);
        self::assertSame('BCA', app(SupplierReaderPort::class)->getById($supplier->id)->bankName());
        self::assertSame($supplier->id, app(SupplierService::class)->resolve('pt bank')->id());
        $this->post(route('admin.suppliers.store'), ['nama_pt_pengirim' => 'PT BANK', 'bank_name' => 'Other'])
            ->assertSessionHasNoErrors();
        self::assertSame(1, DB::table('suppliers')->count());
        self::assertSame('BCA', DB::table('suppliers')->value('bank_name'));
        $this->get(route('admin.suppliers.table'))->assertJsonPath('data.rows.0.bank_account_number', '0012345');
        $this->get(route('admin.suppliers.edit', $supplier->id))->assertSee('0012345')->assertSee('BCA');
        foreach ([['Mandiri', '0009'], ['', ''], ['BRI', '0001']] as [$bank, $account]) {
            $this->put(route('admin.suppliers.update', $supplier->id), [
                'nama_pt_pengirim' => 'PT Bank', 'bank_name' => $bank, 'bank_account_number' => $account,
            ])->assertSessionHasNoErrors();
            $this->assertDatabaseHas('suppliers', [
                'id' => $supplier->id, 'bank_name' => $bank === '' ? null : $bank,
                'bank_account_number' => $account === '' ? null : $account,
            ]);
        }
        $this->put(route('admin.suppliers.update', $supplier->id), ['nama_pt_pengirim' => 'PT Renamed'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'bank_name' => 'BRI', 'bank_account_number' => '0001']);
        self::assertSame(0, DB::table('supplier_invoices')->count());
    }

    public function test_optional_fields_and_validation(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.suppliers.store'), ['nama_pt_pengirim' => 'PT Empty'])->assertSessionHasNoErrors();
        $this->get(route('admin.suppliers.table'))->assertJsonPath('data.rows.0.bank_name', null)
            ->assertJsonPath('data.rows.0.bank_account_number', null);
        $this->get(route('admin.suppliers.index'))->assertSee('Tambah Pemasok')->assertSee('No. Rekening');
        $this->post(route('admin.suppliers.store'), ['nama_pt_pengirim' => 'PT Invalid', 'bank_account_number' => 123])
            ->assertSessionHasErrors('bank_account_number');
    }

    private function admin(): User
    {
        $user = User::query()->create(['name' => 'Admin', 'email' => 'bank@test.local', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);

        return $user;
    }
}
