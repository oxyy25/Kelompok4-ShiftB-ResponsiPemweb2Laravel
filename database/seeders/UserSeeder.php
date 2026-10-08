<?php

namespace Database\Seeders;

use App\Enums\RoleUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@lab.test']);
        $admin->forceFill([
            'name' => 'Admin Lab',
            'role' => RoleUser::Admin,
            'password' => 'password', // di-hash otomatis oleh cast 'hashed'
        ])->save();

        foreach (range(1, 3) as $i) {
            $mhs = User::firstOrNew(['email' => "mahasiswa{$i}@sipinlab.test"]);
            $mhs->forceFill([
                'name' => "Mahasiswa {$i}",
                'nim' => sprintf('H1D023%03d', $i),
                'no_hp' => '0812345678'.$i,
                'role' => RoleUser::Mahasiswa,
                'password' => 'password',
            ])->save();
        }
    }
}
