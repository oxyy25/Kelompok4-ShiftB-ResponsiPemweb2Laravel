@extends('layouts.app')
@section('title', 'Tinjau pengajuan')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <p class="kicker mb-1">Ruang admin</p>
    <h1 class="fw-bold mb-3">Tinjau pengajuan</h1>

    <form id="filterForm" class="mb-3" aria-label="Saring pengajuan">
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

    <div id="pinjamState" class="alert alert-secondary" role="status">Memuat daftar pengajuan.</div>
    <div class="table-responsive">
        <table class="table grid-table d-none" id="pinjamTable">
            <thead>
                <tr><th scope="col">Kegiatan</th><th scope="col">Pengaju</th><th scope="col">Jadwal</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr>
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
        if (!requireAdmin()) return;
        const table = document.getElementById('pinjamTable');
        const body = document.getElementById('pinjamBody');
        const state = document.getElementById('pinjamState');
        const pager = document.getElementById('pager');
        const form = document.getElementById('filterForm');
        let params = { status: 'diajukan' };
        let page = 1;
        form.status.value = 'diajukan';

        async function load() {
            state.className = 'alert alert-secondary';
            state.textContent = 'Memuat daftar pengajuan.';
            table.classList.add('d-none');
            body.innerHTML = '';
            const query = new URLSearchParams({ page, ...params }).toString();
            try {
                const res = await Api.get(`/peminjaman?${query}`);
                const items = res.data || [];
                if (!items.length) {
                    state.textContent = 'Tidak ada pengajuan pada saringan ini.';
                    renderPager(pager, null, () => {});
                    return;
                }
                state.classList.add('d-none');
                table.classList.remove('d-none');
                items.forEach(p => {
                    const tr = document.createElement('tr');
                    const keg = document.createElement('td');
                    keg.textContent = `${p.judul_kegiatan} (${p.lab ? p.lab.nama : 'lab'})`;
                    const pemohon = document.createElement('td');
                    pemohon.textContent = p.user ? p.user.name : 'Pengaju';
                    const jadwal = document.createElement('td');
                    jadwal.textContent = `${p.tanggal}, ${p.jam_mulai} sampai ${p.jam_selesai}`;
                    const st = document.createElement('td');
                    st.appendChild(statusBadge(p.status.value));
                    const aksi = document.createElement('td');
                    aksi.className = 'text-nowrap';
                    const detail = document.createElement('a');
                    detail.className = 'link-ink me-2';
                    detail.href = `/peminjaman/${p.id}`;
                    detail.textContent = 'Periksa';
                    aksi.appendChild(detail);
                    if (p.status.value === 'diajukan') {
                        const ok = document.createElement('button');
                        ok.type = 'button';
                        ok.className = 'btn btn-sm act act-sm me-1';
                        ok.textContent = 'Setujui';
                        ok.addEventListener('click', async () => {
                            try {
                                await Api.patch(`/peminjaman/${p.id}/setujui`, {});
                                showToast('Pengajuan disetujui.');
                                load();
                            } catch (e) { showToast(e.message || 'Gagal menyetujui.', 'error'); }
                        });
                        const no = document.createElement('button');
                        no.type = 'button';
                        no.className = 'btn btn-sm btn-outline-danger';
                        no.textContent = 'Tolak';
                        no.addEventListener('click', async () => {
                            const c = prompt('Tulis catatan penolakan:');
                            if (!c) { showToast('Catatan wajib diisi saat menolak.', 'error'); return; }
                            try {
                                await Api.patch(`/peminjaman/${p.id}/tolak`, { catatan_admin: c });
                                showToast('Pengajuan ditolak.');
                                load();
                            } catch (e) { showToast(e.message || 'Gagal menolak.', 'error'); }
                        });
                        aksi.append(ok, no);
                    }
                    if (p.status.value === 'disetujui') {
                        const done = document.createElement('button');
                        done.type = 'button';
                        done.className = 'btn btn-sm act act-sm';
                        done.textContent = 'Selesai';
                        done.addEventListener('click', async () => {
                            try {
                                await Api.patch(`/peminjaman/${p.id}/selesai`, {});
                                showToast('Peminjaman ditandai selesai.');
                                load();
                            } catch (e) { showToast(e.message || 'Gagal menandai selesai.', 'error'); }
                        });
                        aksi.appendChild(done);
                    }
                    if (['ditolak', 'dibatalkan', 'selesai'].includes(p.status.value)) {
                        const del = document.createElement('button');
                        del.type = 'button';
                        del.className = 'btn btn-sm btn-outline-danger';
                        del.textContent = 'Hapus';
                        del.addEventListener('click', async () => {
                            if (!confirm('Hapus data final ini?')) return;
                            try {
                                await Api.delete(`/peminjaman/${p.id}`);
                                showToast('Data dihapus.');
                                load();
                            } catch (e) { showToast(e.message || 'Gagal menghapus.', 'error'); }
                        });
                        aksi.appendChild(del);
                    }
                    tr.append(keg, pemohon, jadwal, st, aksi);
                    body.appendChild(tr);
                });
                renderPager(pager, res.meta, (n) => { page = n; load(); window.scrollTo(0, 0); });
            } catch (e) {
                failState(state, e.message || 'Daftar pengajuan gagal dimuat.');
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
