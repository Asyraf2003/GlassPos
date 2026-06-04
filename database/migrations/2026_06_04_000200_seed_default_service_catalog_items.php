<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->rows() as $row) {
            $existing = DB::table('service_catalog_items')
                ->where('normalized_name', $row['normalized_name'])
                ->exists();

            if ($existing) {
                DB::table('service_catalog_items')
                    ->where('normalized_name', $row['normalized_name'])
                    ->update([
                        'name' => $row['name'],
                        'default_price_rupiah' => $row['default_price_rupiah'],
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('service_catalog_items')->insert([
                'id' => (string) Str::uuid(),
                'name' => $row['name'],
                'normalized_name' => $row['normalized_name'],
                'default_price_rupiah' => $row['default_price_rupiah'],
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('service_catalog_items')
            ->whereIn('normalized_name', array_column($this->rows(), 'normalized_name'))
            ->delete();
    }

    /**
     * @return list<array{name:string,normalized_name:string,default_price_rupiah:int}>
     */
    private function rows(): array
    {
        return [
            ['name' => 'Sok Kopling (Besar)', 'normalized_name' => 'sok kopling besar', 'default_price_rupiah' => 120000],
            ['name' => 'Sok Kopling (Kecil)', 'normalized_name' => 'sok kopling kecil', 'default_price_rupiah' => 110000],
            ['name' => 'Setting In (Kecil)', 'normalized_name' => 'setting in kecil', 'default_price_rupiah' => 70000],
            ['name' => 'Setting Ex (Kecil)', 'normalized_name' => 'setting ex kecil', 'default_price_rupiah' => 70000],
            ['name' => 'Setting In (Besar)', 'normalized_name' => 'setting in besar', 'default_price_rupiah' => 85000],
            ['name' => 'Setting Ex (Besar)', 'normalized_name' => 'setting ex besar', 'default_price_rupiah' => 85000],
            ['name' => 'Bosklep In (Kecil)', 'normalized_name' => 'bosklep in kecil', 'default_price_rupiah' => 60000],
            ['name' => 'Bosklep Ex (Kecil)', 'normalized_name' => 'bosklep ex kecil', 'default_price_rupiah' => 60000],
            ['name' => 'Bosklep In (Besar)', 'normalized_name' => 'bosklep in besar', 'default_price_rupiah' => 75000],
            ['name' => 'Bosklep Ex (Besar)', 'normalized_name' => 'bosklep ex besar', 'default_price_rupiah' => 75000],
            ['name' => 'Pasang Stang (Kecil)', 'normalized_name' => 'pasang stang kecil', 'default_price_rupiah' => 50000],
            ['name' => 'Pasang Stang (Besar)', 'normalized_name' => 'pasang stang besar', 'default_price_rupiah' => 60000],
        ];
    }
};
