@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <p class="kicker mb-1">Portal peminjaman</p>
    <h1 class="fw-bold mb-2">Pinjam lab dan alat sesuai jadwal.</h1>
    <p class="text-secondary mb-4" style="max-width: 62ch">Lihat lab yang terbuka dan slot yang sudah terpakai, lalu kirim pengajuan. Setiap pengajuan diputus admin.</p>

    <p class="rule pt-3 text-secondary">Operasional 08.00 sampai 17.00 · Durasi maksimal 4 jam · Maksimal 3 pengajuan menunggu</p>

    <div id="sessionInfo" class="alert alert-primary d-none" role="status"></div>

    <section aria-labelledby="daftarLabTitle" class="mt-4">
        <div class="d-flex justify-content-between align-items-baseline mb-2">
            <h2 class="h6 mb-0" id="daftarLabTitle">Lab yang terbuka</h2>
            <a class="link-ink small" href="/labs">Pencarian lengkap</a>
        </div>
        <div id="labState" class="alert alert-secondary" role="status">Memuat daftar lab.</div>
        <div class="table-responsive">
            <table class="table grid-table d-none" id="labTable">
                <thead>
                    <tr><th scope="col">Lab</th><th scope="col">Lokasi</th><th scope="col">Kapasitas</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr>
                </thead>
                <tbody id="labBody"></tbody>
            </table>
        </div>
        <div class="mt-3 d-flex flex-wrap gap-2">
            <a class="act btn" href="/labs">Telusuri semua lab</a>
            <a class="act-ghost btn" href="/peminjaman/buat">Ajukan peminjaman</a>
        </div>
    </section>
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
        const info = document.getElementById('sessionInfo');
        if (Api.isLoggedIn()) {
            Api.get('/auth/me').then(r => {
                info.textContent = `Login sebagai ${r.data.name}, peran ${r.data.role_label}.`;
                info.classList.remove('d-none');
            }).catch(() => {});
        }

        const table = document.getElementById('labTable');
        const body = document.getElementById('labBody');
        const state = document.getElementById('labState');

        Api.get('/labs?per_page=6', { auth: false })
            .then(res => {
                const items = res.data || [];
                if (!items.length) {
                    state.textContent = 'Belum ada lab yang terbuka saat ini.';
                    return;
                }
                state.classList.add('d-none');
                table.classList.remove('d-none');
                items.forEach(lab => {
                    const tr = document.createElement('tr');
                    const nama = document.createElement('td');
                    nama.textContent = `${lab.nama} (${lab.kode})`;
                    const lokasi = document.createElement('td');
                    lokasi.textContent = lab.lokasi;
                    const kap = document.createElement('td');
                    kap.textContent = `${lab.kapasitas} orang`;
                    const aksi = document.createElement('td');
                    const link = document.createElement('a');
                    link.className = 'link-ink';
                    link.href = `/labs/${lab.id}`;
                    link.textContent = 'Detail';
                    aksi.appendChild(link);
                    tr.append(nama, lokasi, kap, aksi);
                    body.appendChild(tr);
                });
            })
            .catch((e) => {
                failState(state, e.message || 'Daftar lab gagal dimuat.');
            });
    });
</script>
@endpush
