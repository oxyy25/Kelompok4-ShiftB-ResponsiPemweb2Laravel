@extends('layouts.app')
@section('title', 'Daftar')

@section('content')
<main class="container py-5" style="max-width: 520px">
    <p class="kicker mb-1">Akun</p>
    <h1 class="fw-bold mb-1">Buat akun mahasiswa</h1>
    <p class="text-secondary mb-4">Akun dipakai untuk melihat jadwal dan mengajukan peminjaman lab. Akun baru otomatis berperan mahasiswa.</p>

    <form id="registerForm" novalidate>
        <div class="mb-3">
            <label for="name" class="form-label">Nama lengkap</label>
            <input type="text" class="form-control" id="name" name="name" autocomplete="name" required>
            <div class="invalid-feedback" data-error-for="name"></div>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
            <div class="invalid-feedback" data-error-for="email"></div>
        </div>
        <div class="row">
            <div class="col-sm-6 mb-3">
                <label for="nim" class="form-label">NIM <span class="text-secondary small">(opsional)</span></label>
                <input type="text" class="form-control" id="nim" name="nim" maxlength="20">
                <div class="invalid-feedback" data-error-for="nim"></div>
            </div>
            <div class="col-sm-6 mb-3">
                <label for="no_hp" class="form-label">No. HP <span class="text-secondary small">(opsional)</span></label>
                <input type="tel" class="form-control" id="no_hp" name="no_hp" placeholder="081234567890" maxlength="20">
                <div class="invalid-feedback" data-error-for="no_hp"></div>
            </div>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
            <div class="form-text">Minimal 8 karakter.</div>
            <div class="invalid-feedback" data-error-for="password"></div>
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Ulangi password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            <div class="invalid-feedback" data-error-for="password_confirmation"></div>
        </div>
        <button type="submit" class="act btn w-100" id="btnSubmit">Buat akun mahasiswa</button>
    </form>

    <p class="text-center mt-4 mb-0 small">
        Sudah punya akun? <a class="link-ink" href="{{ route('login') }}">Masuk ke akun</a>
    </p>
</main>
@endsection

@push('scripts')
<script>
    function bootWhenApiReady(fn, attempts = 100) {
        if (window.Api) { fn(); return; }
        if (attempts <= 0) return;
        setTimeout(() => bootWhenApiReady(fn, attempts - 1), 50);
    }

    bootWhenApiReady(() => {
        if (Api.isLoggedIn()) window.location.href = '/';

        const form = document.getElementById('registerForm');
        const btn = document.getElementById('btnSubmit');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearFieldErrors(form);
            setLoading(btn, true, 'Membuat akun...');

            const body = {
                name: form.name.value.trim(),
                email: form.email.value.trim(),
                nim: form.nim.value.trim() || null,
                no_hp: form.no_hp.value.trim() || null,
                password: form.password.value,
                password_confirmation: form.password_confirmation.value,
            };

            try {
                const res = await Api.post('/auth/register', body, { auth: false });
                showToast(res.message);
                setTimeout(() => window.location.href = '/login', 900);
            } catch (err) {
                if (err.status === 422) showFieldErrors(form, err.errors);
                showToast(err.message || 'Terjadi kesalahan', 'error');
                setLoading(btn, false);
            }
        });
    });
</script>
@endpush
