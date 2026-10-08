@extends('layouts.app')
@section('title', 'Peminjaman saya')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <p class="kicker mb-1">Pengajuan saya</p>
            <h1 class="fw-bold mb-0">Peminjaman saya</h1>
        </div>
        <a class="act btn" href="/peminjaman/buat">Buat pengajuan baru</a>
    </div>

    <form id="filterForm" class="mb-3" aria-label="Saring peminjaman">
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua status</option>
                    <option value="diajukan">Diajukan</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dibatalkan">Dibatalkan</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="act btn" id="btnSaring">Terapkan saringan</button>
            </div>
        </div>
    </form>

    <div id="pinjamState" class="alert alert-secondary" role="status">Memuat daftar peminjaman.</div>
    <div class="table-responsive">
        <table class="table grid-table d-none" id="pinjamTable">
            <thead>
                <tr><th scope="col">Kegiatan</th><th scope="col">Jadwal</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr>
            </thead>
            <tbody id="pinjamBody"></tbody>
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
        const table = document.getElementById('pinjamTable');
        const body = document.getElementById('pinjamBody');
        const state = document.getElementById('pinjamState');
        const pager = document.getElementById('pager');
        const form = document.getElementById('filterForm');
        let params = {};
        let page = 1;

        async function load() {
            state.className = 'alert alert-secondary';
            state.textContent = 'Memuat daftar peminjaman.';
            table.classList.add('d-none');
            body.innerHTML = '';
            const query = new URLSearchParams({ page, ...params }).toString();
            try {
                const res = await Api.get(`/peminjaman?${query}`);
                const items = res.data || [];
                if (!items.length) {
                    state.textContent = 'Belum ada pengajuan pada saringan ini. Buat pengajuan baru untuk mulai meminjam lab.';
                    renderPager(pager, null, () => {});
                    return;
                }
                state.classList.add('d-none');
                table.classList.remove('d-none');
                items.forEach(p => {
                    const tr = document.createElement('tr');
                    const keg = document.createElement('td');
                    keg.textContent = `${p.judul_kegiatan} (${p.lab ? p.lab.nama : 'lab'})`;
                    const jadwal = document.createElement('td');
                    jadwal.textContent = `${p.tanggal}, ${p.jam_mulai} sampai ${p.jam_selesai}`;
                    const st = document.createElement('td');
                    st.appendChild(statusBadge(p.status.value));
                    const aksi = document.createElement('td');
                    const link = document.createElement('a');
                    link.className = 'link-ink';
                    link.href = `/peminjaman/${p.id}`;
                    link.textContent = 'Buka';
                    aksi.appendChild(link);
                    tr.append(keg, jadwal, st, aksi);
                    body.appendChild(tr);
                });
                renderPager(pager, res.meta, (n) => { page = n; load(); window.scrollTo(0, 0); });
            } catch (e) {
                failState(state, e.message || 'Daftar peminjaman gagal dimuat.');
            }
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            params = form.status.value ? { status: form.status.value } : {};
            page = 1;
            load();
        });

        load();
    });
</script>
@endpush
