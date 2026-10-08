<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TolakPeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan_admin' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return ['catatan_admin.required' => 'Alasan penolakan wajib diisi.'];
    }
}
