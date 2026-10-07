<?php

namespace App\Enums;

enum TujuanPeminjaman: string
{
    case ProjectMatkul = 'project_matkul';
    case PraktikumPengganti = 'praktikum_pengganti';
    case Latihan = 'latihan';
    case Lainnya = 'lainnya';
}
