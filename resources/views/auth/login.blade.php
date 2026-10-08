@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<main class="container py-5" style="max-width: 440px">
    <p class="kicker mb-1">Akun</p>
    <h1 class="fw-bold mb-1">Masuk ke PinLab</h1>
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
        <button type="submit" class="act btn w-100" id="btnSubmit">Masuk ke akun</button>
    </form>

    <p class="rule pt-3 mt-4 text-secondary small">Operasional 08.00 sampai 17.00 · Durasi maksimal 4 jam · Maksimal 3 pengajuan menunggu</p>

    <p class="text-center mt-3 mb-0 small">
        Belum punya akun? <a class="link-ink" href="{{ route('register') }}">Daftar akun mahasiswa</a>
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

                const target = res.data.user && res.data.user.role === 'admin' ? '/admin' : '/';
                setTimeout(() => window.location.href = target, 600);
            } catch (err) {
                if (err.status === 422) showFieldErrors(form, err.errors);
                showToast(err.message || 'Terjadi kesalahan', 'error');
                setLoading(btn, false);
            }
        });
    });
</script>
@endpush
