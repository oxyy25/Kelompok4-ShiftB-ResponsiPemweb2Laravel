<?php

namespace App\Enums;

enum TujuanPeminjaman: string
{
    case ProjectMatkul = 'project_matkul';
    case PraktikumPengganti = 'praktikum_pengganti';
    case Latihan = 'latihan';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::ProjectMatkul => 'Project Matkul',
            self::PraktikumPengganti => 'Praktikum Pengganti',
            self::Latihan => 'Latihan',
            self::Lainnya => 'Lainnya',
        };
    }
}
