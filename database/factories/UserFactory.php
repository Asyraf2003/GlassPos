<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentUser>
 */
final class UserFactory extends Factory
{
    protected $model = EloquentUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): self
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null,
        ]);
    }
}
