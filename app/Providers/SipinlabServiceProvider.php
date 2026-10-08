<?php

namespace App\Providers;

use App\Exceptions\BusinessRuleException;
use App\Models\Lab;
use App\Models\Peminjaman;
use App\Models\User;
use App\Policies\LabPolicy;
use App\Policies\PeminjamanPolicy;
use App\Policies\UserPolicy;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Mendaftarkan route API SiPinLab, policy, dan penanganan error terpusat.
 *
 * Sengaja dibuat sebagai provider terpisah (bukan mengubah bootstrap/app.php)
 * agar tidak bentrok dengan perubahan anggota lain di Git.
 */
class SipinlabServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Peminjaman::class, PeminjamanPolicy::class);
        Gate::policy(Lab::class, LabPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/sipinlab.php'));

        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'renderable')) {
            $handler->renderable(fn (Throwable $e, Request $request) => $this->renderApi($e, $request));
        }
    }

    private function renderApi(Throwable $e, Request $request): ?JsonResponse
    {
        if (! ($request->is('api/*') || $request->expectsJson())) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return ApiResponse::error('Data yang dikirim tidak valid', 422, $e->errors());
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error('Belum login atau token tidak valid', 401);
        }

        if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
            return ApiResponse::error('Anda tidak memiliki hak akses untuk aksi ini', 403);
        }

        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::error('Data tidak ditemukan', 404);
        }

        if ($e instanceof NotFoundHttpException) {
            $pesan = $e->getPrevious() instanceof ModelNotFoundException
                ? 'Data tidak ditemukan'
                : 'Endpoint tidak ditemukan';

            return ApiResponse::error($pesan, 404);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error('Method HTTP tidak diizinkan untuk endpoint ini', 405);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return ApiResponse::error('Terlalu banyak permintaan, coba lagi nanti', 429);
        }

        if ($e instanceof BusinessRuleException) {
            return ApiResponse::error($e->getMessage(), $e->status(), $e->errors());
        }

        if ($e instanceof HttpExceptionInterface) {
            return ApiResponse::error($e->getMessage() ?: 'Permintaan tidak dapat diproses', $e->getStatusCode());
        }

        $pesan = config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server';

        return ApiResponse::error($pesan, 500);
    }
}
