<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\Lab;
use Illuminate\Database\Seeder;

class AlatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alats = [
            'RPL' => [
                ['nama' => 'Proyektor', 'stok' => 2, 'kondisi' => 'baik'],
                ['nama' => 'Kabel HDMI', 'stok' => 10, 'kondisi' => 'baik'],
                ['nama' => 'Stopkontak Kabel', 'stok' => 8, 'kondisi' => 'baik'],
            ],
            'JAR' => [
                ['nama' => 'Switch 24 Port', 'stok' => 4, 'kondisi' => 'baik'],
                ['nama' => 'Router', 'stok' => 4, 'kondisi' => 'baik'],
                ['nama' => 'Kabel LAN 10m', 'stok' => 15, 'kondisi' => 'baik'],
                ['nama' => 'Tang Crimping', 'stok' => 6, 'kondisi' => 'perlu_perbaikan'],
            ],
            'MIK' => [
                ['nama' => 'Mikrokontroler Arduino', 'stok' => 20, 'kondisi' => 'baik'],
                ['nama' => 'Sensor Kit', 'stok' => 12, 'kondisi' => 'baik'],
                ['nama' => 'Breadboard', 'stok' => 20, 'kondisi' => 'baik'],
            ],
            'MUL' => [
                ['nama' => 'Kamera DSLR', 'stok' => 3, 'kondisi' => 'baik'],
                ['nama' => 'Tripod', 'stok' => 5, 'kondisi' => 'baik'],
                ['nama' => 'Mikrofon Clip-on', 'stok' => 6, 'kondisi' => 'baik'],
            ],
            'HAD' => [
                ['nama' => 'Multimeter', 'stok' => 10, 'kondisi' => 'baik'],
                ['nama' => 'Osiloskop', 'stok' => 3, 'kondisi' => 'baik'],
                ['nama' => 'Obeng Set', 'stok' => 8, 'kondisi' => 'baik'],
            ],
        ];

        foreach ($alats as $kode => $daftar) {
            $lab = Lab::where('kode', $kode)->first();

            if (! $lab) {
                continue;
            }

            foreach ($daftar as $alat) {
                Alat::firstOrCreate(
                    ['lab_id' => $lab->id, 'nama' => $alat['nama']],
                    $alat + ['lab_id' => $lab->id]
                );
            }
        }
    }
}
