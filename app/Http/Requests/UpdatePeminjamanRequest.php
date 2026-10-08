<?php

namespace App\Http\Requests;

use App\Enums\TujuanPeminjaman;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga PeminjamanPolicy@update di controller
    }

    public function rules(): array
    {
        return [
            'lab_id' => ['sometimes', 'required', 'integer', Rule::exists('labs', 'id')->whereNull('deleted_at')],
            'judul_kegiatan' => ['sometimes', 'required', 'string', 'max:255'],
            'tujuan' => ['sometimes', 'required', Rule::enum(TujuanPeminjaman::class)],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'tanggal' => ['sometimes', 'required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'jam_mulai' => ['sometimes', 'required', 'date_format:H:i'],
            'jam_selesai' => ['sometimes', 'required', 'date_format:H:i'],
            'alat' => ['sometimes', 'array'],
            'alat.*.alat_id' => ['required', 'integer', 'distinct', 'exists:alats,id'],
            'alat.*.jumlah' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'lab_id.exists' => 'Lab tidak ditemukan.',
            'tujuan.enum' => 'Tujuan harus salah satu dari: project_matkul, praktikum_pengganti, latihan, lainnya.',
            'tanggal.after_or_equal' => 'Tanggal peminjaman tidak boleh di masa lalu.',
            'tanggal.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'jam_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'jam_selesai.date_format' => 'Format jam selesai harus HH:MM.',
        ];
    }
}
