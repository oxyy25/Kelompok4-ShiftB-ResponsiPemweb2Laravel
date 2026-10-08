<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lab')) ?? false;
    }

    public function rules(): array
    {
        $labId = $this->route('lab')?->id;

        return [
            'kode' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('labs', 'kode')->ignore($labId)],
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'lokasi' => ['sometimes', 'required', 'string', 'max:255'],
            'kapasitas' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['kode.unique' => 'Kode lab sudah dipakai.'];
    }
}
