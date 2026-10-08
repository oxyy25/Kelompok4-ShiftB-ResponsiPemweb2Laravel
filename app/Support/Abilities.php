<?php

namespace App\Support;

use App\Models\User;

/**
 * Daftar ability token Sanctum per role.
 *
 * Daftar sebenarnya ada di RoleUser::abilities() (dipakai AuthController saat login via
 * User::tokenAbilities()). Konstanta di sini dipakai di routes/api.php agar tidak ada typo.
 */
class Abilities
{
    public const PEMINJAMAN_CREATE = 'peminjaman:create';

    public const PEMINJAMAN_MANAGE_OWN = 'peminjaman:manage-own';

    public const PEMINJAMAN_REVIEW = 'peminjaman:review';

    public const LAB_MANAGE = 'lab:manage';

    public const USER_MANAGE = 'user:manage';

    /** @return array<int, string> */
    public static function for(User $user): array
    {
        return $user->tokenAbilities();
    }
}
