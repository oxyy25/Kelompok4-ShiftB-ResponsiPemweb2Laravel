<?php

namespace App\Http\Requests;

use App\Models\Lab;
use Illuminate\Foundation\Http\FormRequest;

class StoreLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lab::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:50', 'unique:labs,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'lokasi' => ['required', 'string', 'max:255'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:1000'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.unique' => 'Kode lab sudah dipakai.',
            'kapasitas.min' => 'Kapasitas minimal 1 orang.',
        ];
    }
}
