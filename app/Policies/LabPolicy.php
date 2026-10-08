<?php

namespace App\Policies;

use App\Models\Lab;
use App\Models\User;

/** Melihat lab bersifat publik; kelola lab khusus admin. */
class LabPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Lab $lab): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Lab $lab): bool
    {
        return $user->isAdmin();
    }
}
