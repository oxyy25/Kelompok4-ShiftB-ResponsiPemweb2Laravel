<?php

namespace Database\Factories;

use App\Models\Lab;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lab_id' => Lab::factory(),
            'nama' => fake()->randomElement(['Mikrokontroler', 'Osiloskop', 'Switch', 'Router', 'Multimeter']),
            'stok' => fake()->numberBetween(3, 20),
            'kondisi' => fake()->randomElement(['baik', 'perlu_perbaikan', 'rusak']),
        ];
    }
}
