@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
<main class="container py-5">
    <h1 class="h3 mb-3">Peminjaman laboratorium Teknik Komputer</h1>
    <p class="text-secondary" style="max-width: 60ch">
        Halaman ini masih placeholder. Daftar lab dan jadwal akan ditambahkan oleh modul Lab dan Alat.
    </p>
    <div id="sessionInfo" class="alert alert-secondary d-none"></div>
</main>
@endsection

@push('scripts')
<script>
    // lab.js dimuat sebagai ES module Vite (deferred), jadi tunggu window.Api siap.
    function bootWhenApiReady(fn, attempts = 100) {
        if (window.Api) { fn(); return; }
        if (attempts <= 0) return;
        setTimeout(() => bootWhenApiReady(fn, attempts - 1), 50);
    }

    bootWhenApiReady(() => {
        const info = document.getElementById('sessionInfo');
        if (Api.isLoggedIn()) {
            Api.get('/auth/me').then(r => {
                info.textContent = `Login sebagai ${r.data.email}, role ${r.data.role}`;
                info.classList.remove('d-none');
            }).catch(() => {});
        }
    });
</script>
@endpush