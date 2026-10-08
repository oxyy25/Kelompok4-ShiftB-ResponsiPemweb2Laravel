<?php

namespace Database\Seeders;

use App\Models\Lab;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labs = [
            [
                'kode' => 'RPL',
                'nama' => 'Lab Rekayasa Perangkat Lunak',
                'lokasi' => 'Gedung A Ruang 201',
                'kapasitas' => 40,
                'deskripsi' => 'Lab pemrograman untuk praktikum dan project mata kuliah.',
                'is_active' => true,
            ],
            [
                'kode' => 'JAR',
                'nama' => 'Lab Jaringan Komputer',
                'lokasi' => 'Gedung A Ruang 202',
                'kapasitas' => 30,
                'deskripsi' => 'Lab jaringan dengan perangkat switch, router, dan kabel.',
                'is_active' => true,
            ],
            [
                'kode' => 'MIK',
                'nama' => 'Lab Mikrokomputer',
                'lokasi' => 'Gedung B Ruang 101',
                'kapasitas' => 24,
                'deskripsi' => 'Lab mikrokontroler dan sistem embedded.',
                'is_active' => true,
            ],
            [
                'kode' => 'MUL',
                'nama' => 'Lab Multimedia',
                'lokasi' => 'Gedung B Ruang 102',
                'kapasitas' => 30,
                'deskripsi' => 'Lab desain grafis, video, dan pengolahan multimedia.',
                'is_active' => true,
            ],
            [
                'kode' => 'HAD',
                'nama' => 'Lab Hardware',
                'lokasi' => 'Gedung B Ruang 103',
                'kapasitas' => 20,
                'deskripsi' => 'Lab perakitan dan pengukuran perangkat keras komputer.',
                'is_active' => true,
            ],
        ];

        foreach ($labs as $lab) {
            Lab::firstOrCreate(['kode' => $lab['kode']], $lab);
        }
    }
}
