<?php

namespace App\Models;

use App\Enums\StatusPeminjaman;
use App\Enums\TujuanPeminjaman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjamas';

    protected $fillable = [
        'user_id', 'lab_id', 'judul_kegiatan', 'tujuan', 'keterangan',
        'tanggal', 'jam_mulai', 'jam_selesai', 'status',
        'catatan_admin', 'diproses_oleh', 'diproses_pada',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'status' => StatusPeminjaman::class,
        'tujuan' => TujuanPeminjaman::class,
        'diproses_pada' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function alats(): BelongsToMany
    {
        return $this->belongsToMany(Alat::class, 'alat_peminjaman')
            ->withPivot('jumlah');
    }

    public function scopeBentrok(Builder $query, $labId, $tanggal, $jamMulai, $jamSelesai): Builder
    {
        return $query->where('lab_id', $labId)
            ->where('tanggal', $tanggal)
            ->where('status', StatusPeminjaman::Disetujui)
            ->where(function ($q) use ($jamMulai, $jamSelesai) {
                $q->where('jam_mulai', '<', $jamSelesai)
                    ->where('jam_selesai', '>', $jamMulai);
            });
    }
}
