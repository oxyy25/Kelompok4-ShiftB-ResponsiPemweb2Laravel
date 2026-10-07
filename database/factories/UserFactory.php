<?php

namespace Database\Factories;

use App\Enums\RoleUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'nim' => fake()->unique()->numerify('22######'),
            'no_hp' => fake()->numerify('08##########'),
            'role' => RoleUser::Mahasiswa,
            'remember_token' => Str::random(10),
        ];
    }
}
