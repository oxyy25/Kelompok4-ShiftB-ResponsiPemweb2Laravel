<?php

namespace Database\Seeders;

use App\Enums\RoleUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Akun admin utama untuk login ke web dan API:
     * email admin@lab.test, password "password".
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lab.test'],
            [
                'name' => 'Admin Lab',
                'role' => RoleUser::Admin,
                'password' => Hash::make('password'),
            ]
        );
    }
}
