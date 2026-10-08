@extends('layouts.app')
@section('title', 'Daftar lab')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <p class="kicker mb-1">Katalog</p>
    <h1 class="fw-bold mb-3">Daftar lab</h1>

    <form id="filterForm" class="mb-3" aria-label="Pencarian lab">
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label" for="q">Kata kunci</label>
                <input class="form-control" type="search" id="q" name="q" placeholder="Nama, kode, atau lokasi">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="min_kapasitas">Kapasitas minimal</label>
                <input class="form-control" type="number" id="min_kapasitas" name="min_kapasitas" min="1" placeholder="cth 20">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="act btn w-100" id="btnCari">Cari lab</button>
            </div>
        </div>
    </form>

    <div id="labState" class="alert alert-secondary" role="status">Memuat daftar lab.</div>
    <div class="table-responsive">
        <table class="table grid-table d-none" id="labTable">
            <thead>
                <tr><th scope="col">Lab</th><th scope="col">Lokasi</th><th scope="col">Kapasitas</th><th scope="col">Alat</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr>
            </thead>
            <tbody id="labBody"></tbody>
        </table>
    </div>
    <div id="pager" class="mt-3"></div>
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
        const table = document.getElementById('labTable');
        const body = document.getElementById('labBody');
        const state = document.getElementById('labState');
        const pager = document.getElementById('pager');
        const form = document.getElementById('filterForm');
        let params = {};
        let page = 1;

        async function load() {
            state.className = 'alert alert-secondary';
            state.textContent = 'Memuat daftar lab.';
            table.classList.add('d-none');
            body.innerHTML = '';
            const query = new URLSearchParams({ page, ...params }).toString();
            try {
                const res = await Api.get(`/labs?${query}`, { auth: false });
                const items = res.data || [];
                if (!items.length) {
                    state.textContent = 'Tidak ada lab yang cocok dengan pencarian. Ubah kata kunci atau kosongkan saringan.';
                    renderPager(pager, null, () => {});
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
                    const alat = document.createElement('td');
                    alat.textContent = lab.jumlah_alat ?? 0;
                    const aksi = document.createElement('td');
                    const link = document.createElement('a');
                    link.className = 'link-ink';
                    link.href = `/labs/${lab.id}`;
                    link.textContent = 'Detail';
                    aksi.appendChild(link);
                    tr.append(nama, lokasi, kap, alat, aksi);
                    body.appendChild(tr);
                });
                renderPager(pager, res.meta, (p) => { page = p; load(); window.scrollTo(0, 0); });
            } catch (e) {
                failState(state, e.message || 'Daftar lab gagal dimuat.');
            }
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            params = {};
            if (form.q.value.trim()) params.q = form.q.value.trim();
            if (form.min_kapasitas.value) params.min_kapasitas = form.min_kapasitas.value;
            page = 1;
            load();
        });

        load();
    });
</script>
@endpush
