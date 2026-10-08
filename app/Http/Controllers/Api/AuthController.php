<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        // only() + $fillable memastikan field 'role' dari client diabaikan.
        // Kolom 'role' memakai default database: mahasiswa.
        try {
            $user = User::create($request->safe()->only(['name', 'email', 'nim', 'no_hp', 'password']));
        } catch (QueryException $e) {
            // Race antara validasi unique dan insert (dua request bersamaan).
            throw ValidationException::withMessages([
                'email' => ['Email sudah terdaftar.'],
            ]);
        }

        return ApiResponse::created(
            new UserResource($user->refresh()),
            'Registrasi berhasil, silakan login'
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return ApiResponse::error('Email atau password salah', 401);
        }

        $abilities = $user->tokenAbilities();
        $token = $user->createToken('sipinlab-token', $abilities)->plainTextToken;

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'abilities' => $abilities,
        ], 'Login berhasil');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logout berhasil');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Belum login atau token tidak valid.', 401);
        }

        return ApiResponse::success(
            new UserResource($user),
            'Profil pengguna'
        );
    }
}
