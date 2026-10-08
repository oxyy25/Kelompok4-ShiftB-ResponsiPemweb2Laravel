<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusPeminjaman;
use App\Exceptions\BusinessRuleException;
use App\Http\Requests\StoreLabRequest;
use App\Http\Requests\UpdateLabRequest;
use App\Http\Resources\LabResource;
use App\Models\Lab;
use App\Models\Peminjaman;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LabController extends ApiController
{
    /**
     * GET /api/labs (publik)
     * Filter: q, min_kapasitas, is_active (khusus admin). Urut: nama, kode, kapasitas, created_at.
     */
    public function index(Request $request): JsonResponse
    {
        $admin = $this->adalahAdmin($request);

        $query = Lab::query()
            ->withCount('alats')
            ->when(! $admin, fn ($q) => $q->aktif())
            ->when($admin && $request->filled('is_active'), fn ($q) => $q->where(
                'is_active',
                filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)
            ))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = '%'.trim((string) $request->query('q')).'%';
                $q->where(fn ($w) => $w->where('nama', 'like', $kata)
                    ->orWhere('kode', 'like', $kata)
                    ->orWhere('lokasi', 'like', $kata));
            })
            ->when($request->filled('min_kapasitas'), fn ($q) => $q->where('kapasitas', '>=', (int) $request->query('min_kapasitas')));

        $this->urutkan($query, $request, ['nama', 'kode', 'kapasitas', 'created_at'], 'nama');

        return ApiResponse::paginated($query->paginate($this->perPage($request))->withQueryString(), LabResource::class, 'Daftar lab berhasil diambil');
    }

    /** GET /api/labs/{lab} (publik) */
    public function show(Request $request, Lab $lab): JsonResponse
    {
        $this->pastikanTerlihat($request, $lab);

        $lab->load('alats')->loadCount('alats');

        return ApiResponse::success(new LabResource($lab), 'Detail lab berhasil diambil');
    }

    /** GET /api/labs/{lab}/jadwal?tanggal=YYYY-MM-DD (publik): slot yang sudah terpakai */
    public function jadwal(Request $request, Lab $lab): JsonResponse
    {
        $this->pastikanTerlihat($request, $lab);

        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d'],
        ], [
            'tanggal.required' => 'Parameter tanggal wajib diisi (format YYYY-MM-DD).',
            'tanggal.date_format' => 'Format tanggal harus YYYY-MM-DD.',
        ]);

        $slot = Peminjaman::query()
            ->where('lab_id', $lab->id)
            ->where('tanggal', $data['tanggal'])
            ->where('status', StatusPeminjaman::Disetujui->value)
            ->orderBy('jam_mulai')
            ->get(['jam_mulai', 'jam_selesai'])
            ->map(fn ($p) => [
                'jam_mulai' => substr((string) $p->jam_mulai, 0, 5),
                'jam_selesai' => substr((string) $p->jam_selesai, 0, 5),
            ])
            ->values();

        return ApiResponse::success([
            'lab' => ['id' => $lab->id, 'kode' => $lab->kode, 'nama' => $lab->nama],
            'tanggal' => $data['tanggal'],
            'jam_operasional' => [
                'buka' => config('sipinlab.jam_buka'),
                'tutup' => config('sipinlab.jam_tutup'),
            ],
            'slot_terpakai' => $slot,
        ], 'Jadwal lab berhasil diambil');
    }

    /** POST /api/labs (admin) */
    public function store(StoreLabRequest $request): JsonResponse
    {
        $lab = Lab::create($request->validated());

        return ApiResponse::created(new LabResource($lab), 'Lab berhasil ditambahkan');
    }

    /** PUT /api/labs/{lab} (admin) */
    public function update(UpdateLabRequest $request, Lab $lab): JsonResponse
    {
        $lab->update($request->validated());

        return ApiResponse::success(new LabResource($lab->fresh()), 'Lab berhasil diperbarui');
    }

    /** DELETE /api/labs/{lab} (admin): soft delete */
    public function destroy(Lab $lab): JsonResponse
    {
        Gate::authorize('delete', $lab);

        $masihDipakai = Peminjaman::where('lab_id', $lab->id)
            ->where('status', StatusPeminjaman::Disetujui->value)
            ->where('tanggal', '>=', now()->toDateString())
            ->exists();

        if ($masihDipakai) {
            throw new BusinessRuleException('Lab masih memiliki peminjaman disetujui yang akan datang, tidak dapat dihapus.', 409);
        }

        $lab->delete();

        return ApiResponse::success(null, 'Lab berhasil dihapus');
    }

    private function adalahAdmin(Request $request): bool
    {
        // Route ini publik; token (jika ada) tetap dibaca untuk mengenali admin.
        $user = $request->user('sanctum');

        return $user !== null && $user->isAdmin();
    }

    private function pastikanTerlihat(Request $request, Lab $lab): void
    {
        if (! $lab->is_active && ! $this->adalahAdmin($request)) {
            throw new BusinessRuleException('Lab tidak ditemukan.', 404);
        }
    }
}
