<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'lokasi' => $this->lokasi,
            'kapasitas' => $this->kapasitas,
            'deskripsi' => $this->deskripsi,
            'is_active' => (bool) $this->is_active,
            'jumlah_alat' => $this->whenCounted('alats'),
            'alat' => $this->whenLoaded('alats', fn () => AlatResource::collection($this->alats)->resolve()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
