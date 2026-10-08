<?php

namespace Database\Factories;

use App\Enums\RoleUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'nim' => fake()->unique()->numerify('H1D023###'),
            'no_hp' => fake()->numerify('08##########'),
            'role' => RoleUser::Mahasiswa,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'role' => RoleUser::Admin,
            'nim' => null,
        ]);
    }
}
