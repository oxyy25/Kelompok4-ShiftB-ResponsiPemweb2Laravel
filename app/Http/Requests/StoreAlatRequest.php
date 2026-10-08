<?php

namespace App\Http\Requests;

use App\Models\Alat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Alat::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'lab_id' => ['required', 'integer', Rule::exists('labs', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'nama' => ['required', 'string', 'max:255'],
            'stok' => ['required', 'integer', 'min:0', 'max:100000'],
            'kondisi' => ['sometimes', 'string', Rule::in(['baik', 'perlu_perbaikan', 'rusak'])],
        ];
    }

    public function messages(): array
    {
        return [
            'lab_id.exists' => 'Lab tidak ditemukan atau sedang tidak aktif.',
            'kondisi.in' => 'Kondisi harus salah satu dari: baik, perlu_perbaikan, rusak.',
        ];
    }
}
