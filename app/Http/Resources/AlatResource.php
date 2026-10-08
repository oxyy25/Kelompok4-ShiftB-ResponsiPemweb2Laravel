<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lab_id' => $this->lab_id,
            'nama' => $this->nama,
            'stok' => $this->stok,
            'kondisi' => $this->kondisi,
            'lab' => $this->whenLoaded('lab', fn () => $this->lab ? [
                'id' => $this->lab->id,
                'kode' => $this->lab->kode,
                'nama' => $this->lab->nama,
            ] : null),
            // hanya muncul saat alat dimuat lewat relasi peminjaman
            'jumlah_dipinjam' => $this->whenPivotLoaded('alat_peminjaman', fn () => $this->pivot->jumlah),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
