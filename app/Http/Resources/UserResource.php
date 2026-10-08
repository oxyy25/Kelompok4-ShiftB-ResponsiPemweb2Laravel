<?php

namespace App\Http\Resources;

use App\Enums\RoleUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->role ?? RoleUser::Mahasiswa;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'nim' => $this->nim,
            'no_hp' => $this->no_hp,
            'role' => $role->value,
            'role_label' => $role->label(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
