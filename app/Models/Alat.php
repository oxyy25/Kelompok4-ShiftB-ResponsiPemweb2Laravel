<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alat extends Model
{
    use HasFactory;

    protected $fillable = ['lab_id', 'nama', 'stok', 'kondisi'];

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function peminjaman(): BelongsToMany
    {
        return $this->belongsToMany(Peminjaman::class, 'alat_peminjaman')
                    ->withPivot('jumlah');
    }
}
