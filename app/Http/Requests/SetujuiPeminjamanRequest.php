<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetujuiPeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
