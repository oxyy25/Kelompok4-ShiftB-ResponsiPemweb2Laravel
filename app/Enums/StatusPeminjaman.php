<?php

namespace App\Enums;

enum StatusPeminjaman: string
{
    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match($this) {
            self::Diajukan => 'Diajukan',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
            self::Selesai => 'Selesai',
        };
    }
}
