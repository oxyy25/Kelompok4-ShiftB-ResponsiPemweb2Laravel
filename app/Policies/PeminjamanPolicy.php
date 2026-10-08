<?php

namespace App\Policies;

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

    /** Hanya pemilik yang boleh mengubah pengajuannya. */
    public function update(User $user, Peminjaman $peminjaman): bool
    {
        return (int) $peminjaman->user_id === (int) $user->id;
    }

    /** Hanya pemilik yang boleh membatalkan. */
    public function cancel(User $user, Peminjaman $peminjaman): bool
    {
        return (int) $peminjaman->user_id === (int) $user->id;
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
