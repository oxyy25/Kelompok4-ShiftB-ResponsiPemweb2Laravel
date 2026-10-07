<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lab extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kode', 'nama', 'lokasi', 'kapasitas', 'deskripsi', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function alats(): HasMany
    {
        return $this->hasMany(Alat::class);
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }

    // Scope: lab aktif
    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
