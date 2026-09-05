<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed;

use App\Application\ServiceCatalog\UseCases\CreateServiceCatalogItemHandler;
use Database\Seeders\DefaultSeed\Support\DefaultSeedActor;
use Illuminate\Database\Seeder;

final class DefaultServiceSeeder extends Seeder
{
    private const SERVICES = [
        'Ganti Oli Mesin', 'Tune Up', 'Servis Rem Depan', 'Servis Rem Belakang', 'Ganti Kampas Rem',
        'Ganti Rantai Gear', 'Servis CVT', 'Setel Klep', 'Ganti Busi', 'Servis Shock',
    ];

    private const SEGMENTS = ['Matic', 'Bebek', 'Sport 150cc', 'Sport 250cc', 'Harian'];

    public function run(CreateServiceCatalogItemHandler $services): void
    {
        $actorId = DefaultSeedActor::adminId();
        $index = 0;

        foreach (self::SERVICES as $serviceIndex => $service) {
            foreach (self::SEGMENTS as $segmentIndex => $segment) {
                $index++;
                $services->handle(
                    sprintf('Default Service %02d - %s - %s', $index, $service, $segment),
                    35000 + ($serviceIndex * 12000) + ($segmentIndex * 5000),
                    $actorId,
                    'seed_default',
                );
            }
        }
    }
}
