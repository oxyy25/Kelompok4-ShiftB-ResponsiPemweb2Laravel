<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleUser;
use App\Exceptions\BusinessRuleException;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends ApiController
{
    /** GET /api/users (admin)  Filter: role, q. Urut: name, email, created_at. */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query()
            ->when(
                $request->filled('role') && RoleUser::tryFrom((string) $request->query('role')),
                fn ($q) => $q->where('role', (string) $request->query('role'))
            )
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = '%'.trim((string) $request->query('q')).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $kata)
                    ->orWhere('email', 'like', $kata)
                    ->orWhere('nim', 'like', $kata));
            });

        $this->urutkan($query, $request, ['name', 'email', 'created_at'], 'name');

        return ApiResponse::paginated($query->paginate($this->perPage($request))->withQueryString(), UserResource::class, 'Daftar pengguna berhasil diambil');
    }

    /** GET /api/users/{user} (admin) */
    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return ApiResponse::success(new UserResource($user), 'Detail pengguna berhasil diambil');
    }

    /** PUT /api/users/{user} (admin) */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['role']) && (int) $user->id === (int) $request->user()->id && $data['role'] !== $user->role->value) {
            throw new BusinessRuleException('Anda tidak dapat mengubah role akun Anda sendiri.', 409);
        }

        // 'role' tidak ada di $fillable (mencegah mass assignment), jadi diset eksplisit.
        $role = $data['role'] ?? null;
        unset($data['role']);

        $user->fill($data); // password otomatis di-hash oleh cast 'hashed' pada model User

        if ($role !== null) {
            $user->role = RoleUser::from($role);
        }

        $user->save();

        return ApiResponse::success(new UserResource($user->fresh()), 'Pengguna berhasil diperbarui');
    }

    /** DELETE /api/users/{user} (admin) */
    public function destroy(Request $request, User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        if ((int) $user->id === (int) $request->user()->id) {
            throw new BusinessRuleException('Anda tidak dapat menghapus akun Anda sendiri.', 409);
        }

        if ($user->peminjaman()->exists()) {
            throw new BusinessRuleException('Pengguna memiliki riwayat peminjaman, tidak dapat dihapus.', 409);
        }

        $user->tokens()->delete();
        $user->delete();

        return ApiResponse::success(null, 'Pengguna berhasil dihapus');
    }
}
