<?php

namespace Database\Factories;

use App\Enums\StatusPeminjaman;
use App\Enums\TujuanPeminjaman;
use App\Models\Lab;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeminjamanFactory extends Factory
{
    public function definition(): array
    {
        $mulai = fake()->numberBetween(8, 13);
        $selesai = $mulai + fake()->numberBetween(1, 3);

        return [
            'user_id' => User::factory()->state(['role' => 'mahasiswa']),
            'lab_id' => Lab::factory(),
            'judul_kegiatan' => fake()->sentence(4),
            'tujuan' => fake()->randomElement(TujuanPeminjaman::cases())->value,
            'keterangan' => fake()->sentence(),
            'tanggal' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'jam_mulai' => sprintf('%02d:00', $mulai),
            'jam_selesai' => sprintf('%02d:00', $selesai),
            'status' => fake()->randomElement(StatusPeminjaman::cases())->value,
        ];
    }
}
