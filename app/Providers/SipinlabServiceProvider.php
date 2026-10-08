<?php

namespace App\Providers;

use App\Models\Alat;
use App\Models\Lab;
use App\Models\Peminjaman;
use App\Models\User;
use App\Policies\AlatPolicy;
use App\Policies\LabPolicy;
use App\Policies\PeminjamanPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Mendaftarkan policy otorisasi SiPinLab.
 *
 * Route API dimuat sekali via bootstrap/app.php (withRouting api: routes/api.php).
 * Exception JSON terpusat ditangani di bootstrap/app.php.
 */
class SipinlabServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Peminjaman::class, PeminjamanPolicy::class);
        Gate::policy(Lab::class, LabPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Alat::class, AlatPolicy::class);
    }
}
