<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_id' => ['sometimes', 'required', 'integer', Rule::exists('labs', 'id')->whereNull('deleted_at')],
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'stok' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000'],
            'kondisi' => ['sometimes', Rule::in(['baik', 'perlu_perbaikan', 'rusak'])],
        ];
    }

    public function messages(): array
    {
        return [
            'lab_id.exists' => 'Lab tidak ditemukan.',
            'kondisi.in' => 'Kondisi harus salah satu dari: baik, perlu_perbaikan, rusak.',
        ];
    }
}
