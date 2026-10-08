<?php

namespace App\Policies;

use App\Models\Alat;
use App\Models\User;

/** Kelola alat khusus admin, konsisten dengan LabPolicy. */
class AlatPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Alat $alat): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Alat $alat): bool
    {
        return $user->isAdmin();
    }
}
