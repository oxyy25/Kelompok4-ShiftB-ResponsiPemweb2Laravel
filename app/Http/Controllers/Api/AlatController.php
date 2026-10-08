<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusPeminjaman;
use App\Exceptions\BusinessRuleException;
use App\Http\Requests\StoreAlatRequest;
use App\Http\Requests\UpdateAlatRequest;
use App\Http\Resources\AlatResource;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AlatController extends ApiController
{
    /** GET /api/alats  Filter: lab_id, kondisi, q. Urut: nama, stok, created_at. */
    public function index(Request $request): JsonResponse
    {
        $query = Alat::query()
            ->with('lab')
            ->when($request->filled('lab_id'), fn ($q) => $q->where('lab_id', (int) $request->query('lab_id')))
            ->when($request->filled('kondisi'), fn ($q) => $q->where('kondisi', (string) $request->query('kondisi')))
            ->when($request->filled('q'), fn ($q) => $q->where('nama', 'like', '%'.trim((string) $request->query('q')).'%'));

        $this->urutkan($query, $request, ['nama', 'stok', 'created_at'], 'nama');

        return ApiResponse::paginated($query->paginate($this->perPage($request))->withQueryString(), AlatResource::class, 'Daftar alat berhasil diambil');
    }

    /** GET /api/alats/{alat} */
    public function show(Alat $alat): JsonResponse
    {
        return ApiResponse::success(new AlatResource($alat->load('lab')), 'Detail alat berhasil diambil');
    }

    /** POST /api/alats (admin) */
    public function store(StoreAlatRequest $request): JsonResponse
    {
        Gate::authorize('create', Alat::class);

        $alat = Alat::create($request->validated());

        return ApiResponse::created(new AlatResource($alat->load('lab')), 'Alat berhasil ditambahkan');
    }

    /** PUT /api/alats/{alat} (admin) */
    public function update(UpdateAlatRequest $request, Alat $alat): JsonResponse
    {
        Gate::authorize('update', $alat);

        $alat->update($request->validated());

        return ApiResponse::success(new AlatResource($alat->fresh('lab')), 'Alat berhasil diperbarui');
    }

    /** DELETE /api/alats/{alat} (admin) */
    public function destroy(Alat $alat): JsonResponse
    {
        Gate::authorize('delete', $alat);

        $dipakai = $alat->peminjaman()
            ->whereIn((new Peminjaman)->getTable().'.status', [StatusPeminjaman::Diajukan->value, StatusPeminjaman::Disetujui->value])
            ->exists();

        if ($dipakai) {
            throw new BusinessRuleException('Alat masih dipakai pada pengajuan/peminjaman aktif, tidak dapat dihapus.', 409);
        }

        $alat->delete();

        return ApiResponse::success(null, 'Alat berhasil dihapus');
    }
}
