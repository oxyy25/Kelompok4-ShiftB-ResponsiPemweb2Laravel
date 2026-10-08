<?php

namespace App\Http\Requests;

use App\Enums\RoleUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'nim' => ['nullable', 'string', 'max:20', Rule::unique('users', 'nim')->ignore($userId)],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)[0-9]{8,15}$/'],
            'role' => ['sometimes', 'required', Rule::enum(RoleUser::class)],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'no_hp.regex' => 'Nomor HP tidak valid (contoh: 081234567890).',
            'role.enum' => 'Role harus admin atau mahasiswa.',
        ];
    }
}
