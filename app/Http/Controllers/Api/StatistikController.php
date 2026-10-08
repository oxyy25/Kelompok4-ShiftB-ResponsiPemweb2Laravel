<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Alat;
use App\Models\Lab;
use App\Models\Peminjaman;
use App\Models\User;
use App\Models\User as UserModel;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StatistikController extends ApiController
{
    /** GET /api/statistik (admin) */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', UserModel::class);

        $perStatus = Peminjaman::query()->toBase()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusLengkap = [];
        foreach (StatusPeminjaman::cases() as $status) {
            $statusLengkap[$status->value] = (int) ($perStatus[$status->value] ?? 0);
        }

        $labTerpopuler = DB::table('peminjamas as p')
            ->join('labs as l', 'l.id', '=', 'p.lab_id')
            ->whereIn('p.status', [StatusPeminjaman::Disetujui->value, StatusPeminjaman::Selesai->value])
            ->whereNull('l.deleted_at')
            ->select('l.id', 'l.kode', 'l.nama')
            ->selectRaw('count(*) as total_peminjaman')
            ->groupBy('l.id', 'l.kode', 'l.nama')
            ->orderByDesc('total_peminjaman')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'kode' => $r->kode,
                'nama' => $r->nama,
                'total_peminjaman' => (int) $r->total_peminjaman,
            ]);

        return ApiResponse::success([
            'total_peminjaman' => array_sum($statusLengkap),
            'per_status' => $statusLengkap,
            'menunggu_persetujuan' => $statusLengkap[StatusPeminjaman::Diajukan->value],
            'lab_terpopuler' => $labTerpopuler,
            'total_lab_aktif' => Lab::aktif()->count(),
            'total_alat' => Alat::count(),
            'total_mahasiswa' => User::where('role', RoleUser::Mahasiswa->value)->count(),
        ], 'Statistik berhasil diambil');
    }
}
