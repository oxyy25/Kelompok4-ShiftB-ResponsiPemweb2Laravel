<?php

namespace App\Enums;

enum RoleUser: string
{
    case Admin = 'admin';
    case Mahasiswa = 'mahasiswa';

    public function abilities(): array
    {
        return match ($this) {
            self::Mahasiswa => [
                'peminjaman:create',
                'peminjaman:manage-own',
            ],
            self::Admin => [
                'peminjaman:create',
                'peminjaman:manage-own',
                'peminjaman:review',
                'lab:manage',
                'user:manage',
            ],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Mahasiswa => 'Mahasiswa',
        };
    }
}
