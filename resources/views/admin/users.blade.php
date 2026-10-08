@extends('layouts.app')
@section('title', 'Kelola pengguna')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <p class="kicker mb-1">Ruang admin</p>
    <h1 class="fw-bold mb-3">Kelola pengguna</h1>

    <form id="filterForm" class="mb-3" aria-label="Pencarian pengguna">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label" for="role">Peran</label>
                <select class="form-select" id="role" name="role">
                    <option value="">Semua peran</option>
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="q">Nama, email, atau NIM</label>
                <input class="form-control" type="search" id="q" name="q">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="act btn w-100" id="btnCari">Cari pengguna</button>
            </div>
        </div>
    </form>

    <div id="userState" class="alert alert-secondary" role="status">Memuat daftar pengguna.</div>
    <div class="table-responsive">
        <table class="table grid-table d-none" id="userTable">
            <thead>
                <tr><th scope="col">Nama</th><th scope="col">Kontak</th><th scope="col">Peran</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr>
            </thead>
            <tbody id="userBody"></tbody>
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
        const table = document.getElementById('userTable');
        const body = document.getElementById('userBody');
        const state = document.getElementById('userState');
        const pager = document.getElementById('pager');
        const form = document.getElementById('filterForm');
        let params = {};
        let page = 1;

        async function load() {
            state.className = 'alert alert-secondary';
            state.textContent = 'Memuat daftar pengguna.';
            table.classList.add('d-none');
            body.innerHTML = '';
            const query = new URLSearchParams({ page, ...params }).toString();
            try {
                const res = await Api.get(`/users?${query}`);
                const items = res.data || [];
                if (!items.length) {
                    state.textContent = 'Tidak ada pengguna yang cocok. Ubah saringan atau kosongkan.';
                    renderPager(pager, null, () => {});
                    return;
                }
                state.classList.add('d-none');
                table.classList.remove('d-none');
                items.forEach(u => {
                    const tr = document.createElement('tr');
                    const nama = document.createElement('td');
                    nama.textContent = `${u.name}${u.nim ? ` (${u.nim})` : ''}`;
                    const kontak = document.createElement('td');
                    kontak.textContent = u.email;
                    const peran = document.createElement('td');
                    const badge = document.createElement('span');
                    badge.className = `badge badge-status ${u.role === 'admin' ? 'tag-on' : 'tag-off'}`;
                    badge.textContent = u.role_label;
                    peran.appendChild(badge);
                    const aksi = document.createElement('td');
                    aksi.className = 'text-nowrap';
                    const sel = document.createElement('select');
                    sel.className = 'form-select form-select-sm d-inline-block me-1';
                    sel.style.maxWidth = '130px';
                    sel.setAttribute('aria-label', `Peran untuk ${u.name}`);
                    ['mahasiswa', 'admin'].forEach(r => {
                        const o = document.createElement('option');
                        o.value = r;
                        o.textContent = r;
                        if (u.role === r) o.selected = true;
                        sel.appendChild(o);
                    });
                    const save = document.createElement('button');
                    save.type = 'button';
                    save.className = 'btn btn-sm act act-sm me-1';
                    save.textContent = 'Simpan';
                    save.addEventListener('click', async () => {
                        try {
                            await Api.put(`/users/${u.id}`, { role: sel.value });
                            showToast('Peran tersimpan.');
                            load();
                        } catch (e) { showToast(e.message || 'Gagal menyimpan.', 'error'); }
                    });
                    const del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'btn btn-sm btn-outline-danger';
                    del.textContent = 'Hapus';
                    del.addEventListener('click', async () => {
                        if (!confirm(`Hapus akun ${u.name}? Akun dengan riwayat tidak bisa dihapus.`)) return;
                        try {
                            await Api.delete(`/users/${u.id}`);
                            showToast('Akun dihapus.');
                            load();
                        } catch (e) { showToast(e.message || 'Gagal menghapus.', 'error'); }
                    });
                    aksi.append(sel, save, del);
                    tr.append(nama, kontak, peran, aksi);
                    body.appendChild(tr);
                });
                renderPager(pager, res.meta, (n) => { page = n; load(); window.scrollTo(0, 0); });
            } catch (e) {
                failState(state, e.message || 'Daftar pengguna gagal dimuat.');
            }
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            params = {};
            if (form.role.value) params.role = form.role.value;
            if (form.q.value.trim()) params.q = form.q.value.trim();
            page = 1;
            load();
        });

        load();
    });
</script>
@endpush
