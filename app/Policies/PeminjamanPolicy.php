<?php

namespace App\Policies;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use App\Models\User;

class PeminjamanPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    /** Pemilik atau admin boleh melihat. */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin() || (int) $peminjaman->user_id === (int) $user->id;
    }

    /** Hanya pemilik yang boleh mengubah pengajuannya selama masih diajukan. */
    public function update(User $user, Peminjaman $peminjaman): bool
    {
        return (int) $peminjaman->user_id === (int) $user->id
            && $peminjaman->status === StatusPeminjaman::Diajukan;
    }

    /** Hanya pemilik yang boleh membatalkan selama masih diajukan. */
    public function cancel(User $user, Peminjaman $peminjaman): bool
    {
        return (int) $peminjaman->user_id === (int) $user->id
            && $peminjaman->status === StatusPeminjaman::Diajukan;
    }

    /** Setujui / tolak / selesai: khusus admin. */
    public function review(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin();
    }
}
