<?php

namespace App\Http\Requests;

use App\Enums\TujuanPeminjaman;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga PeminjamanPolicy@create di controller
    }

    public function rules(): array
    {
        return [
            'lab_id' => ['required', 'integer', Rule::exists('labs', 'id')->whereNull('deleted_at')],
            'judul_kegiatan' => ['required', 'string', 'max:255'],
            'tujuan' => ['required', Rule::enum(TujuanPeminjaman::class)],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'alat' => ['sometimes', 'array', 'max:50'],
            'alat.*.alat_id' => ['required', 'integer', 'distinct', 'exists:alats,id'],
            'alat.*.jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
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
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'alat.*.alat_id.exists' => 'Alat tidak ditemukan.',
            'alat.*.alat_id.distinct' => 'Alat yang sama tidak boleh dipilih dua kali.',
            'alat.*.jumlah.min' => 'Jumlah alat minimal 1.',
            'alat.max' => 'Maksimal 50 jenis alat dalam satu pengajuan.',
            'alat.*.jumlah.max' => 'Jumlah alat terlalu besar.',
        ];
    }
}
