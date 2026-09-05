<?php

declare(strict_types=1);

namespace Database\Seeders\DefaultSeed\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DefaultSeedActor
{
    public static function adminId(): string
    {
        $id = DB::table('users')->where('email', 'admin@gmail.com')->value('id');

        if ($id === null) {
            throw new RuntimeException('Default admin must exist before dependent seeders run.');
        }

        return (string) $id;
    }
}
