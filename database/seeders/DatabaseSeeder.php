<?php

namespace Database\Seeders;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Alat;
use App\Models\Lab;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin Lab',
            'email' => 'admin@sinpinlab.test',
            'password' => Hash::make('password'),
            'nim' => '00000001',
            'no_hp' => '081234567890',
            'role' => RoleUser::Admin,
        ]);

        // 3 Mahasiswa
        foreach (range(1, 3) as $i) {
            User::create([
                'name' => "Mahasiswa $i",
                'email' => "mahasiswa$i@sinpinlab.test",
                'password' => Hash::make('password'),
                'nim' => "22" . str_pad($i, 6, '0', STR_PAD_LEFT),
                'no_hp' => "08123456789$i",
                'role' => RoleUser::Mahasiswa,
            ]);
        }

        // 4 Lab
        $labs = [
            ['kode' => 'LAB-EMB', 'nama' => 'Lab Sistem Embedded', 'lokasi' => 'Gedung A Ruang 101', 'kapasitas' => 30],
            ['kode' => 'LAB-JAR', 'nama' => 'Lab Jaringan Komputer', 'lokasi' => 'Gedung A Ruang 102', 'kapasitas' => 25],
            ['kode' => 'LAB-PRO', 'nama' => 'Lab Pemrograman', 'lokasi' => 'Gedung B Ruang 201', 'kapasitas' => 40],
            ['kode' => 'LAB-KOM', 'nama' => 'Lab Komputasi', 'lokasi' => 'Gedung B Ruang 202', 'kapasitas' => 35],
        ];

        foreach ($labs as $lab) {
            Lab::create($lab + ['deskripsi' => 'Laboratorium ' . $lab['nama'], 'is_active' => true]);
        }

        // Alat (10-15 tersebar di lab)
        $alatList = [
            ['lab_id' => 1, 'nama' => 'Mikrokontroler Arduino', 'stok' => 10, 'kondisi' => 'baik'],
            ['lab_id' => 1, 'nama' => 'Osiloskop', 'stok' => 5, 'kondisi' => 'baik'],
            ['lab_id' => 1, 'nama' => 'Multimeter', 'stok' => 8, 'kondisi' => 'baik'],
            ['lab_id' => 2, 'nama' => 'Switch Cisco', 'stok' => 6, 'kondisi' => 'baik'],
            ['lab_id' => 2, 'nama' => 'Router Mikrotik', 'stok' => 6, 'kondisi' => 'baik'],
            ['lab_id' => 2, 'nama' => 'Kabel UTP', 'stok' => 50, 'kondisi' => 'baik'],
            ['lab_id' => 3, 'nama' => 'Laptop Lenovo', 'stok' => 20, 'kondisi' => 'baik'],
            ['lab_id' => 3, 'nama' => 'Monitor LG', 'stok' => 20, 'kondisi' => 'baik'],
            ['lab_id' => 3, 'nama' => 'Keyboard Mechanical', 'stok' => 15, 'kondisi' => 'perlu_perbaikan'],
            ['lab_id' => 4, 'nama' => 'PC Workstation', 'stok' => 12, 'kondisi' => 'baik'],
            ['lab_id' => 4, 'nama' => 'Headset VR', 'stok' => 3, 'kondisi' => 'baik'],
            ['lab_id' => 4, 'nama' => 'Proyektor', 'stok' => 2, 'kondisi' => 'perlu_perbaikan'],
        ];

        foreach ($alatList as $alat) {
            Alat::create($alat);
        }

        // 8-12 peminjaman
        $mahasiswaIds = User::where('role', RoleUser::Mahasiswa)->pluck('id');
        $statuses = [
            StatusPeminjaman::Diajukan,
            StatusPeminjaman::Disetujui,
            StatusPeminjaman::Ditolak,
            StatusPeminjaman::Selesai,
            StatusPeminjaman::Dibatalkan,
        ];

        foreach (range(1, 10) as $i) {
            $mulai = rand(8, 13);
            Peminjaman::create([
                'user_id' => $mahasiswaIds->random(),
                'lab_id' => rand(1, 4),
                'judul_kegiatan' => "Kegiatan peminjaman ke-$i",
                'tujuan' => fake()->randomElement(['project_matkul', 'praktikum_pengganti', 'latihan', 'lainnya']),
                'keterangan' => 'Butuh alat untuk keperluan praktikum',
                'tanggal' => now()->addDays(rand(1, 30))->format('Y-m-d'),
                'jam_mulai' => sprintf('%02d:00', $mulai),
                'jam_selesai' => sprintf('%02d:00', $mulai + rand(1, 3)),
                'status' => fake()->randomElement($statuses)->value,
            ]);
        }
    }
}
