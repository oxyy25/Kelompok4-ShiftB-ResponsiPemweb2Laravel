@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<main class="auth-wrap d-flex align-items-center justify-content-center p-3">
    <div class="auth-card p-4 p-md-5">
        <h1 class="h4 mb-1">Masuk ke SiPinLab</h1>
        <p class="text-secondary mb-4">Gunakan akun untuk mengajukan peminjaman lab.</p>

        <form id="loginForm" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
                <div class="invalid-feedback" data-error-for="email"></div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                <div class="invalid-feedback" data-error-for="password"></div>
            </div>
            <button type="submit" class="btn btn-teal w-100" id="btnSubmit">Masuk</button>
        </form>

        <p class="text-center mt-4 mb-0 small">
            Belum punya akun? <a class="link-teal" href="{{ route('register') }}">Daftar sebagai mahasiswa</a>
        </p>
    </div>
</main>
@endsection

@push('scripts')
<script>
    // lab.js dimuat sebagai ES module Vite (deferred), jadi tunggu window.Api siap
    // sebelum memasang listener. Tanpa ini, akses Api langsung melempar ReferenceError
    // dan form kembali ke submit native (halaman hanya reload).
    function bootWhenApiReady(fn, attempts = 100) {
        if (window.Api) { fn(); return; }
        if (attempts <= 0) return;
        setTimeout(() => bootWhenApiReady(fn, attempts - 1), 50);
    }

    bootWhenApiReady(() => {
        if (Api.isLoggedIn()) window.location.href = '/';

        const form = document.getElementById('loginForm');
        const btn = document.getElementById('btnSubmit');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearFieldErrors(form);
            setLoading(btn, true, 'Masuk...');

            try {
                const res = await Api.post('/auth/login', {
                    email: form.email.value.trim(),
                    password: form.password.value,
                }, { auth: false });

                Api.save(res.data.token, res.data.user);
                showToast(res.message);

                // TODO: arahkan admin ke dashboard admin saat halamannya sudah ada
                setTimeout(() => window.location.href = '/', 600);
            } catch (err) {
                if (err.status === 422) showFieldErrors(form, err.errors);
                showToast(err.message || 'Terjadi kesalahan', 'error');
                setLoading(btn, false);
            }
        });
    });
</script>
@endpush