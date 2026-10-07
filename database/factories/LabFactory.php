<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LabFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => 'LAB-' . strtoupper(fake()->unique()->lexify('???')),
            'nama' => 'Lab ' . fake()->words(2, true),
            'lokasi' => 'Gedung ' . fake()->randomLetter() . ' Ruang ' . fake()->numberBetween(101, 405),
            'kapasitas' => fake()->numberBetween(20, 50),
            'deskripsi' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
