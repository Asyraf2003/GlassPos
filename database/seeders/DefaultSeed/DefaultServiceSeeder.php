<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\ServiceCatalog\UseCases\CreateServiceCatalogItemHandler;
use Database\Seeders\DefaultSeed\Support\DefaultSeedActor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DefaultServiceSeeder extends Seeder
{
    private const SERVICES = [
        'Ganti Oli Mesin', 'Tune Up', 'Servis Rem Depan', 'Servis Rem Belakang', 'Ganti Kampas Rem',
        'Ganti Rantai Gear', 'Servis CVT', 'Setel Klep', 'Ganti Busi', 'Servis Shock',
    ];

    private const SEGMENTS = ['Matic', 'Bebek', 'Sport 150cc', 'Sport 250cc', 'Harian'];

    public function run(CreateServiceCatalogItemHandler $services): void
    {
        $activeCount = DB::table('service_catalog_items')->where('is_active', true)->count();

        if ($activeCount > 50) {
            throw new RuntimeException('Fresh default seed expects at most 50 active services before filling.');
        }

        $remaining = 50 - $activeCount;
        $actorId = DefaultSeedActor::adminId();
        $candidate = 0;

        foreach (self::SERVICES as $serviceIndex => $service) {
            foreach (self::SEGMENTS as $segmentIndex => $segment) {
                if ($remaining <= 0) {
                    return;
                }

                $candidate++;
                $services->handle(
                    sprintf('Default Service %02d - %s - %s', $candidate, $service, $segment),
                    35000 + ($serviceIndex * 12000) + ($segmentIndex * 5000),
                    $actorId,
                    'seed_default',
                );
                $remaining--;
            }
        }
    }
}
