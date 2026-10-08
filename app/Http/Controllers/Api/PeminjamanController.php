<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusPeminjaman;
use App\Http\Requests\SetujuiPeminjamanRequest;
use App\Http\Requests\StorePeminjamanRequest;
use App\Http\Requests\TolakPeminjamanRequest;
use App\Http\Requests\UpdatePeminjamanRequest;
use App\Http\Resources\PeminjamanCollection;
use App\Http\Resources\PeminjamanResource;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PeminjamanController extends ApiController
{
    public function __construct(private readonly PeminjamanService $service)
    {
    }

    /**
     * GET /api/peminjaman
     * Mahasiswa: hanya miliknya. Admin: semua.
     * Filter: status, lab_id, tanggal, q. Urut: tanggal, created_at, status (awalan - = menurun).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Peminjaman::query()
            ->with(['user', 'lab', 'alats'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when(
                $request->filled('status') && StatusPeminjaman::tryFrom((string) $request->query('status')),
                fn ($q) => $q->where('status', (string) $request->query('status'))
            )
            ->when($request->filled('lab_id'), fn ($q) => $q->where('lab_id', (int) $request->query('lab_id')))
            ->when($request->filled('tanggal'), fn ($q) => $q->where('tanggal', (string) $request->query('tanggal')))
            ->when($request->filled('q'), function ($q) use ($request, $user) {
                $kata = '%'.trim((string) $request->query('q')).'%';

                $q->where(function ($w) use ($kata, $user) {
                    $w->where('judul_kegiatan', 'like', $kata);

                    if ($user->isAdmin()) {
                        $w->orWhereHas('user', fn ($u) => $u->where('name', 'like', $kata)->orWhere('nim', 'like', $kata));
                    }
                });
            });

        $this->urutkan($query, $request, ['tanggal', 'created_at', 'status'], '-created_at');

        return ApiResponse::paginated(
            $query->paginate($this->perPage($request))->withQueryString(),
            PeminjamanCollection::class,
            'Daftar peminjaman berhasil diambil'
        );
    }

    /** POST /api/peminjaman */
    public function store(StorePeminjamanRequest $request): JsonResponse
    {
        Gate::authorize('create', Peminjaman::class);

        $peminjaman = $this->service->ajukan($request->user(), $request->validated());

        return ApiResponse::created(new PeminjamanResource($peminjaman), 'Pengajuan peminjaman berhasil dibuat');
    }

    /** GET /api/peminjaman/{peminjaman}: pemilik atau admin */
    public function show(Peminjaman $peminjaman): JsonResponse
    {
        Gate::authorize('view', $peminjaman);

        $peminjaman->load(['user', 'lab', 'alats', 'pemroses']);

        return ApiResponse::success(new PeminjamanResource($peminjaman), 'Detail peminjaman berhasil diambil');
    }

    /** PUT /api/peminjaman/{peminjaman}: pemilik, saat status diajukan */
    public function update(UpdatePeminjamanRequest $request, Peminjaman $peminjaman): JsonResponse
    {
        Gate::authorize('update', $peminjaman);

        $peminjaman = $this->service->ubah($peminjaman, $request->validated());

        return ApiResponse::success(new PeminjamanResource($peminjaman), 'Pengajuan berhasil diperbarui');
    }

    /** PATCH /api/peminjaman/{peminjaman}/batalkan: pemilik */
    public function batalkan(Peminjaman $peminjaman): JsonResponse
    {
        Gate::authorize('cancel', $peminjaman);

        return ApiResponse::success(
            new PeminjamanResource($this->service->batalkan($peminjaman)),
            'Pengajuan berhasil dibatalkan'
        );
    }

    /** PATCH /api/peminjaman/{peminjaman}/setujui: admin */
    public function setujui(SetujuiPeminjamanRequest $request, Peminjaman $peminjaman): JsonResponse
    {
        Gate::authorize('review', $peminjaman);

        $hasil = $this->service->setujui($peminjaman, $request->user(), $request->validated('catatan_admin'));

        return ApiResponse::success(new PeminjamanResource($hasil), 'Pengajuan berhasil disetujui');
    }

    /** PATCH /api/peminjaman/{peminjaman}/tolak: admin */
    public function tolak(TolakPeminjamanRequest $request, Peminjaman $peminjaman): JsonResponse
    {
        Gate::authorize('review', $peminjaman);

        $hasil = $this->service->tolak($peminjaman, $request->user(), $request->validated('catatan_admin'));

        return ApiResponse::success(new PeminjamanResource($hasil), 'Pengajuan berhasil ditolak');
    }

    /** PATCH /api/peminjaman/{peminjaman}/selesai: admin */
    public function selesai(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        Gate::authorize('review', $peminjaman);

        $hasil = $this->service->selesai($peminjaman, $request->user());

        return ApiResponse::success(new PeminjamanResource($hasil), 'Peminjaman ditandai selesai');
    }

    /** DELETE /api/peminjaman/{peminjaman}: admin, hanya data final */
    public function destroy(Peminjaman $peminjaman): JsonResponse
    {
        Gate::authorize('delete', $peminjaman);

        $this->service->hapus($peminjaman);

        return ApiResponse::success(null, 'Data peminjaman berhasil dihapus');
    }
}
