<?php

return [
    // Jam operasional lab (BR-02)
    'jam_buka' => env('SIPINLAB_JAM_BUKA', '08:00'),
    'jam_tutup' => env('SIPINLAB_JAM_TUTUP', '17:00'),

    // Durasi maksimal satu peminjaman, dalam jam (BR-02)
    'durasi_maks_jam' => (int) env('SIPINLAB_DURASI_MAKS', 4),

    // Maksimal pengajuan berstatus "diajukan" per mahasiswa (BR-09)
    'maks_diajukan' => (int) env('SIPINLAB_MAKS_DIAJUKAN', 3),

    // Peminjaman baru boleh ditandai selesai setelah jam mulai lewat.
    // Set false di .env untuk demo: SIPINLAB_SELESAI_SETELAH_MULAI=false
    'selesai_setelah_mulai' => filter_var(env('SIPINLAB_SELESAI_SETELAH_MULAI', true), FILTER_VALIDATE_BOOL),

    'per_page_default' => 10,
    'per_page_maks' => 50,
];
