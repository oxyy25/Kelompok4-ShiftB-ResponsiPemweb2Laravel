<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul_kegiatan' => $this->judul_kegiatan,
            'tujuan' => $this->tujuan?->value,
            'keterangan' => $this->keterangan,
            'tanggal' => $this->tanggal?->toDateString(),
            'jam_mulai' => substr((string) $this->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $this->jam_selesai, 0, 5),
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'catatan_admin' => $this->catatan_admin,
            'diproses_pada' => $this->diproses_pada?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'nim' => $this->user->nim,
            ] : null),
            'lab' => $this->whenLoaded('lab', fn () => $this->lab ? [
                'id' => $this->lab->id,
                'kode' => $this->lab->kode,
                'nama' => $this->lab->nama,
                'lokasi' => $this->lab->lokasi,
            ] : null),
            'pemroses' => $this->whenLoaded('pemroses', fn () => $this->pemroses ? [
                'id' => $this->pemroses->id,
                'name' => $this->pemroses->name,
            ] : null),
            'alat' => $this->whenLoaded('alats', fn () => AlatResource::collection($this->alats)->resolve()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
