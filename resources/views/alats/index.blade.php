@extends('layouts.app')
@section('title', 'Daftar alat')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <p class="kicker mb-1">Inventaris</p>
    <h1 class="fw-bold mb-3">Daftar alat</h1>

    <form id="filterForm" class="mb-3" aria-label="Pencarian alat">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label" for="lab_id">Lab</label>
                <select class="form-select" id="lab_id" name="lab_id">
                    <option value="">Semua lab</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="kondisi">Kondisi</label>
                <select class="form-select" id="kondisi" name="kondisi">
                    <option value="">Semua kondisi</option>
                    <option value="baik">Baik</option>
                    <option value="perlu_perbaikan">Perlu perbaikan</option>
                    <option value="rusak">Rusak</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="q">Nama alat</label>
                <input class="form-control" type="search" id="q" name="q" placeholder="cth proyektor">
            </div>
            <div class="col-12">
                <button type="submit" class="act btn" id="btnCari">Cari alat</button>
            </div>
        </div>
    </form>

    <div id="alatState" class="alert alert-secondary" role="status">Memuat daftar alat.</div>
    <div class="table-responsive">
        <table class="table grid-table d-none" id="alatTable">
            <thead>
                <tr><th scope="col">Nama</th><th scope="col">Lab</th><th scope="col">Stok</th><th scope="col">Kondisi</th></tr>
            </thead>
            <tbody id="alatBody"></tbody>
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
        if (!requireLogin()) return;
        const state = document.getElementById('alatState');
        const table = document.getElementById('alatTable');
        const body = document.getElementById('alatBody');
        const pager = document.getElementById('pager');
        const form = document.getElementById('filterForm');
        let params = {};
        let page = 1;

        Api.get('/labs?per_page=50', { auth: false }).then(res => {
            const sel = document.getElementById('lab_id');
            (res.data || []).forEach(lab => {
                const opt = document.createElement('option');
                opt.value = lab.id;
                opt.textContent = `${lab.kode} (${lab.nama})`;
                sel.appendChild(opt);
            });
        }).catch(() => {});

        async function load() {
            state.className = 'alert alert-secondary';
            state.textContent = 'Memuat daftar alat.';
            table.classList.add('d-none');
            body.innerHTML = '';
            const query = new URLSearchParams({ page, ...params }).toString();
            try {
                const res = await Api.get(`/alats?${query}`);
                const items = res.data || [];
                if (!items.length) {
                    state.textContent = 'Tidak ada alat yang cocok dengan pencarian. Ubah saringan atau kosongkan.';
                    renderPager(pager, null, () => {});
                    return;
                }
                state.classList.add('d-none');
                table.classList.remove('d-none');
                items.forEach(a => {
                    const tr = document.createElement('tr');
                    const nama = document.createElement('td');
                    nama.textContent = a.nama;
                    const lab = document.createElement('td');
                    lab.textContent = a.lab ? a.lab.kode : 'Tanpa lab';
                    const stok = document.createElement('td');
                    stok.textContent = a.stok;
                    const kondisi = document.createElement('td');
                    kondisi.textContent = a.kondisi;
                    tr.append(nama, lab, stok, kondisi);
                    body.appendChild(tr);
                });
                renderPager(pager, res.meta, (p) => { page = p; load(); window.scrollTo(0, 0); });
            } catch (e) {
                failState(state, e.message || 'Daftar alat gagal dimuat.');
            }
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            params = {};
            if (form.lab_id.value) params.lab_id = form.lab_id.value;
            if (form.kondisi.value) params.kondisi = form.kondisi.value;
            if (form.q.value.trim()) params.q = form.q.value.trim();
            page = 1;
            load();
        });

        load();
    });
</script>
@endpush
