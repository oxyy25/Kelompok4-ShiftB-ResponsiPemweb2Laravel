<?php

use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\StatistikController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureAdmin;
use App\Support\Abilities;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

$ability = fn (string $nama) => CheckAbilities::class.':'.$nama;

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Public Routes (No Authentication)
Route::get('labs', [LabController::class, 'index']);
Route::get('labs/{lab}', [LabController::class, 'show']);
Route::get('labs/{lab}/jadwal', [LabController::class, 'jadwal']);

// Authenticated User Routes
Route::middleware('auth:sanctum')->group(function () use ($ability) {
    // View All and Individual Resources
    Route::get('alats', [AlatController::class, 'index']);
    Route::get('alats/{alat}', [AlatController::class, 'show']);

    Route::get('peminjaman', [PeminjamanController::class, 'index']);
    Route::get('peminjaman/{peminjaman}', [PeminjamanController::class, 'show']);

    // User Actions (PEMINJAMAN_CREATE, PEMINJAMAN_MANAGE_OWN)
    Route::post('peminjaman', [PeminjamanController::class, 'store'])
        ->middleware($ability(Abilities::PEMINJAMAN_CREATE));

    Route::put('peminjaman/{peminjaman}', [PeminjamanController::class, 'update'])
        ->middleware($ability(Abilities::PEMINJAMAN_MANAGE_OWN));

    Route::patch('peminjaman/{peminjaman}/batalkan', [PeminjamanController::class, 'batalkan'])
        ->middleware($ability(Abilities::PEMINJAMAN_MANAGE_OWN));
});

// Admin-Only Routes
Route::middleware('auth:sanctum')->group(function () use ($ability) {
    Route::middleware(EnsureAdmin::class)->group(function () use ($ability) {
        // Manage Labs (LAB_MANAGE)
        Route::middleware($ability(Abilities::LAB_MANAGE))->group(function () {
            Route::post('labs', [LabController::class, 'store']);
            Route::put('labs/{lab}', [LabController::class, 'update']);
            Route::delete('labs/{lab}', [LabController::class, 'destroy']);

            Route::post('alats', [AlatController::class, 'store']);
            Route::put('alats/{alat}', [AlatController::class, 'update']);
            Route::delete('alats/{alat}', [AlatController::class, 'destroy']);
        });

        // Manage Peminjaman Approvals and Statistics (PEMINJAMAN_REVIEW)
        Route::middleware($ability(Abilities::PEMINJAMAN_REVIEW))->group(function () {
            Route::patch('peminjaman/{peminjaman}/setujui', [PeminjamanController::class, 'setujui']);
            Route::patch('peminjaman/{peminjaman}/tolak', [PeminjamanController::class, 'tolak']);
            Route::patch('peminjaman/{peminjaman}/selesai', [PeminjamanController::class, 'selesai']);
            Route::delete('peminjaman/{peminjaman}', [PeminjamanController::class, 'destroy']);

            Route::get('statistik', [StatistikController::class, 'index']);
        });

        // Manage Users (USER_MANAGE)
        Route::middleware($ability(Abilities::USER_MANAGE))->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::get('users/{user}', [UserController::class, 'show']);
            Route::put('users/{user}', [UserController::class, 'update']);
            Route::delete('users/{user}', [UserController::class, 'destroy']);
        });
    });
});
