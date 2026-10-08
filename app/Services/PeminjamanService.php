<?php

namespace App\Services;

use App\Enums\StatusPeminjaman;
use App\Exceptions\BusinessRuleException;
use App\Models\Alat;
use App\Models\Lab;
use App\Models\Peminjaman;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Seluruh logika bisnis peminjaman lab (BR-01 sampai BR-10 di PRD).
 *
 * Controller hanya menerima request lalu memanggil service ini.
 */
class PeminjamanService
{
    private const RELASI = ['user', 'lab', 'alats', 'pemroses'];

    // ------------------------------------------------------------------
    // Aksi mahasiswa
    // ------------------------------------------------------------------

    public function ajukan(User $user, array $data): Peminjaman
    {
        return DB::transaction(function () use ($user, $data) {
            $this->batasiPengajuanAktif($user);

            $lab = $this->ambilLabAktif((int) $data['lab_id']);
            [$tanggal, $mulai, $selesai] = $this->normalisasiWaktu(
                $data['tanggal'], $data['jam_mulai'], $data['jam_selesai']
            );

            $this->validasiJamOperasional($mulai, $selesai);
            $this->pastikanTidakBentrok($lab->id, $tanggal, $mulai, $selesai);

            $alat = $this->kumpulkanAlat($data['alat'] ?? []);
            $this->validasiAlat($lab, $alat, $tanggal, $mulai, $selesai);

            $peminjaman = Peminjaman::create([
                'user_id' => $user->id,
                'lab_id' => $lab->id,
                'judul_kegiatan' => $data['judul_kegiatan'],
                'tujuan' => $data['tujuan'],
                'keterangan' => $data['keterangan'] ?? null,
                'tanggal' => $tanggal,
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
                'status' => StatusPeminjaman::Diajukan,
            ]);

            $peminjaman->alats()->sync($this->payloadPivot($alat));

            return $peminjaman->load(self::RELASI);
        });
    }

    public function ubah(Peminjaman $peminjaman, array $data): Peminjaman
    {
        return DB::transaction(function () use ($peminjaman, $data) {
            $p = Peminjaman::with('alats')->whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

            $this->pastikanStatus($p, StatusPeminjaman::Diajukan, 'Pengajuan hanya dapat diubah selama berstatus diajukan.');

            $lab = $this->ambilLabAktif((int) ($data['lab_id'] ?? $p->lab_id));
            [$tanggal, $mulai, $selesai] = $this->normalisasiWaktu(
                $data['tanggal'] ?? $p->tanggal->toDateString(),
                $data['jam_mulai'] ?? $p->jam_mulai,
                $data['jam_selesai'] ?? $p->jam_selesai,
            );

            if ($mulai >= $selesai) {
                throw ValidationException::withMessages(['jam_selesai' => ['Jam selesai harus setelah jam mulai.']]);
            }

            $this->validasiJamOperasional($mulai, $selesai);
            $this->pastikanTidakBentrok($lab->id, $tanggal, $mulai, $selesai, $p->id);

            $itemAlat = array_key_exists('alat', $data)
                ? $data['alat']
                : $p->alats->map(fn (Alat $a) => ['alat_id' => $a->id, 'jumlah' => $a->pivot->jumlah])->all();

            $alat = $this->kumpulkanAlat($itemAlat);
            $this->validasiAlat($lab, $alat, $tanggal, $mulai, $selesai, $p->id);

            $atribut = collect($data)->only(['judul_kegiatan', 'tujuan', 'keterangan'])->all();
            $p->update($atribut + [
                'lab_id' => $lab->id,
                'tanggal' => $tanggal,
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
            ]);

            $p->alats()->sync($this->payloadPivot($alat));

            return $p->load(self::RELASI);
        });
    }

    public function batalkan(Peminjaman $peminjaman): Peminjaman
    {
        return DB::transaction(function () use ($peminjaman) {
            $p = Peminjaman::whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

            $this->pastikanStatus($p, StatusPeminjaman::Diajukan, 'Hanya pengajuan berstatus diajukan yang dapat dibatalkan.');

            $p->update(['status' => StatusPeminjaman::Dibatalkan]);

            return $p->load(self::RELASI);
        });
    }

    // ------------------------------------------------------------------
    // Aksi admin
    // ------------------------------------------------------------------

    public function setujui(Peminjaman $peminjaman, User $admin, ?string $catatan = null): Peminjaman
    {
        return DB::transaction(function () use ($peminjaman, $admin, $catatan) {
            // BR-07: kunci baris lab agar dua admin tidak menyetujui jadwal yang sama bersamaan.
            Lab::withTrashed()->whereKey($peminjaman->lab_id)->lockForUpdate()->first();

            $p = Peminjaman::with('alats')->whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

            $this->pastikanStatus($p, StatusPeminjaman::Diajukan, 'Hanya pengajuan berstatus diajukan yang dapat disetujui.');

            $lab = Lab::find($p->lab_id);
            if (! $lab || ! $lab->is_active) {
                throw new BusinessRuleException('Lab tidak aktif atau sudah dihapus.', 409);
            }

            $tanggal = $p->tanggal->toDateString();
            [, $mulai, $selesai] = $this->normalisasiWaktu($tanggal, $p->jam_mulai, $p->jam_selesai);

            $this->pastikanTidakBentrok($p->lab_id, $tanggal, $mulai, $selesai, $p->id);

            foreach ($p->alats as $alat) {
                $sisa = $this->sisaStok($alat, $tanggal, $mulai, $selesai, $p->id);
                if ($alat->pivot->jumlah > $sisa) {
                    throw new BusinessRuleException(
                        "Stok alat {$alat->nama} tidak mencukupi pada jam tersebut (tersisa {$sisa}).",
                        409
                    );
                }
            }

            $p->update([
                'status' => StatusPeminjaman::Disetujui,
                'catatan_admin' => $catatan,
                'diproses_oleh' => $admin->id,
                'diproses_pada' => now(),
            ]);

            return $p->load(self::RELASI);
        });
    }

    public function tolak(Peminjaman $peminjaman, User $admin, string $catatan): Peminjaman
    {
        return DB::transaction(function () use ($peminjaman, $admin, $catatan) {
            $p = Peminjaman::whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

            $this->pastikanStatus($p, StatusPeminjaman::Diajukan, 'Hanya pengajuan berstatus diajukan yang dapat ditolak.');

            $p->update([
                'status' => StatusPeminjaman::Ditolak,
                'catatan_admin' => $catatan,
                'diproses_oleh' => $admin->id,
                'diproses_pada' => now(),
            ]);

            return $p->load(self::RELASI);
        });
    }

    public function selesai(Peminjaman $peminjaman, User $admin): Peminjaman
    {
        return DB::transaction(function () use ($peminjaman, $admin) {
            $p = Peminjaman::whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

            $this->pastikanStatus($p, StatusPeminjaman::Disetujui, 'Hanya peminjaman berstatus disetujui yang dapat diselesaikan.');

            if (config('sipinlab.selesai_setelah_mulai')) {
                $mulai = Carbon::parse($p->tanggal->toDateString().' '.$p->jam_mulai);
                if (now()->lt($mulai)) {
                    throw new BusinessRuleException('Peminjaman belum dimulai, belum dapat ditandai selesai.', 409);
                }
            }

            $p->update([
                'status' => StatusPeminjaman::Selesai,
                'diproses_oleh' => $p->diproses_oleh ?? $admin->id,
                'diproses_pada' => $p->diproses_pada ?? now(),
            ]);

            return $p->load(self::RELASI);
        });
    }

    public function hapus(Peminjaman $peminjaman): void
    {
        $final = [StatusPeminjaman::Ditolak, StatusPeminjaman::Dibatalkan, StatusPeminjaman::Selesai];

        if (! in_array($peminjaman->status, $final, true)) {
            throw new BusinessRuleException(
                'Hanya data berstatus ditolak, dibatalkan, atau selesai yang dapat dihapus.',
                409
            );
        }

        $peminjaman->delete(); // baris pivot ikut terhapus (cascadeOnDelete)
    }

    // ------------------------------------------------------------------
    // Aturan bisnis
    // ------------------------------------------------------------------

    /** BR-09 */
    private function batasiPengajuanAktif(User $user): void
    {
        $maks = (int) config('sipinlab.maks_diajukan');

        $jumlah = Peminjaman::where('user_id', $user->id)
            ->where('status', StatusPeminjaman::Diajukan->value)
            ->count();

        if ($jumlah >= $maks) {
            throw new BusinessRuleException(
                "Anda masih memiliki {$jumlah} pengajuan yang menunggu. Maksimal {$maks} pengajuan bersamaan.",
                422
            );
        }
    }

    /** BR-04 */
    private function ambilLabAktif(int $labId): Lab
    {
        $lab = Lab::find($labId);

        if (! $lab) {
            throw ValidationException::withMessages(['lab_id' => ['Lab tidak ditemukan.']]);
        }

        if (! $lab->is_active) {
            throw ValidationException::withMessages(['lab_id' => ['Lab sedang tidak aktif dan tidak dapat dipinjam.']]);
        }

        return $lab;
    }

    /** BR-02 */
    private function validasiJamOperasional(string $mulai, string $selesai): void
    {
        $buka = Carbon::parse(config('sipinlab.jam_buka'))->format('H:i:s');
        $tutup = Carbon::parse(config('sipinlab.jam_tutup'))->format('H:i:s');

        if ($mulai < $buka || $mulai >= $tutup) {
            throw ValidationException::withMessages([
                'jam_mulai' => ['Jam mulai harus dalam jam operasional ('.substr($buka, 0, 5).' - '.substr($tutup, 0, 5).').'],
            ]);
        }

        if ($selesai > $tutup) {
            throw ValidationException::withMessages([
                'jam_selesai' => ['Jam selesai tidak boleh melewati jam tutup ('.substr($tutup, 0, 5).').'],
            ]);
        }

        $durasiMenit = Carbon::parse($mulai)->diffInMinutes(Carbon::parse($selesai));
        $maksJam = (int) config('sipinlab.durasi_maks_jam');

        if ($durasiMenit > $maksJam * 60) {
            throw ValidationException::withMessages([
                'jam_selesai' => ["Durasi peminjaman maksimal {$maksJam} jam."],
            ]);
        }
    }

    /** BR-01: hanya peminjaman berstatus disetujui yang memblokir jadwal. */
    private function pastikanTidakBentrok(int $labId, string $tanggal, string $mulai, string $selesai, ?int $abaikanId = null): void
    {
        $bentrok = Peminjaman::bentrok($labId, $tanggal, $mulai, $selesai)
            ->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))
            ->exists();

        if ($bentrok) {
            throw new BusinessRuleException(
                'Jadwal bentrok dengan peminjaman lain yang sudah disetujui pada lab dan jam tersebut.',
                409
            );
        }
    }

    /** BR-05 */
    private function validasiAlat(Lab $lab, array $alat, string $tanggal, string $mulai, string $selesai, ?int $abaikanId = null): void
    {
        foreach ($alat as $row) {
            /** @var Alat $a */
            $a = $row['alat'];
            $kunci = "alat.{$row['index']}.alat_id";

            if ((int) $a->lab_id !== (int) $lab->id) {
                throw ValidationException::withMessages([$kunci => ["Alat {$a->nama} bukan milik lab yang dipilih."]]);
            }

            if ($a->kondisi === 'rusak') {
                throw ValidationException::withMessages([$kunci => ["Alat {$a->nama} dalam kondisi rusak."]]);
            }

            $sisa = $this->sisaStok($a, $tanggal, $mulai, $selesai, $abaikanId);

            if ($row['jumlah'] > $sisa) {
                throw ValidationException::withMessages([
                    "alat.{$row['index']}.jumlah" => ["Stok alat {$a->nama} tidak mencukupi pada jam tersebut (tersisa {$sisa})."],
                ]);
            }
        }
    }

    /** Stok alat dikurangi alat yang sudah dipakai peminjaman disetujui pada jam beririsan. */
    private function sisaStok(Alat $alat, string $tanggal, string $mulai, string $selesai, ?int $abaikanId = null): int
    {
        $terpakai = DB::table('alat_peminjaman as ap')
            ->join('peminjamas as p', 'p.id', '=', 'ap.peminjaman_id')
            ->where('ap.alat_id', $alat->id)
            ->where('p.status', StatusPeminjaman::Disetujui->value)
            ->where('p.tanggal', $tanggal)
            ->where('p.jam_mulai', '<', $selesai)
            ->where('p.jam_selesai', '>', $mulai)
            ->when($abaikanId, fn ($q) => $q->where('p.id', '!=', $abaikanId))
            ->sum('ap.jumlah');

        return max(0, (int) $alat->stok - (int) $terpakai);
    }

    // ------------------------------------------------------------------
    // Util
    // ------------------------------------------------------------------

    private function pastikanStatus(Peminjaman $p, StatusPeminjaman $diharapkan, string $pesan): void
    {
        if ($p->status !== $diharapkan) {
            throw new BusinessRuleException($pesan, 409);
        }
    }

    /** @return array{0: string, 1: string, 2: string} tanggal Y-m-d, jam H:i:s */
    private function normalisasiWaktu(mixed $tanggal, mixed $mulai, mixed $selesai): array
    {
        return [
            Carbon::parse($tanggal)->toDateString(),
            Carbon::parse($mulai)->format('H:i:s'),
            Carbon::parse($selesai)->format('H:i:s'),
        ];
    }

    /** @return array<int, array{index: int, alat: Alat, jumlah: int}> */
    private function kumpulkanAlat(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $model = Alat::whereIn('id', collect($items)->pluck('alat_id'))->get()->keyBy('id');
        $hasil = [];

        foreach (array_values($items) as $i => $item) {
            $alat = $model->get((int) $item['alat_id']);

            if (! $alat) {
                throw ValidationException::withMessages(["alat.{$i}.alat_id" => ['Alat tidak ditemukan.']]);
            }

            $hasil[] = ['index' => $i, 'alat' => $alat, 'jumlah' => (int) $item['jumlah']];
        }

        return $hasil;
    }

    /** @return array<int, array{jumlah: int}> */
    private function payloadPivot(array $alat): array
    {
        $payload = [];

        foreach ($alat as $row) {
            $payload[$row['alat']->id] = ['jumlah' => $row['jumlah']];
        }

        return $payload;
    }
}
