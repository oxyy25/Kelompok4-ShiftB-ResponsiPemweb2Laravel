@extends('layouts.app')
@section('title', 'Kelola lab dan alat')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <p class="kicker mb-1">Ruang admin</p>
            <h1 class="fw-bold mb-0">Kelola lab dan alat</h1>
        </div>
        <button type="button" class="act btn" data-bs-toggle="modal" data-bs-target="#labModal" id="btnTambahLab">Tambah lab baru</button>
    </div>

    <div id="labState" class="alert alert-secondary" role="status">Memuat daftar lab.</div>
    <div class="table-responsive">
        <table class="table grid-table d-none" id="labTable">
            <thead>
                <tr><th scope="col">Lab</th><th scope="col">Lokasi</th><th scope="col">Kapasitas</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr>
            </thead>
            <tbody id="labBody"></tbody>
        </table>
    </div>

    <section aria-labelledby="alatTitle" class="mt-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-0" id="alatTitle">Alat per lab</h2>
                <p class="text-secondary small mb-0">Pilih lab untuk melihat, menambah, mengubah, atau menghapus alatnya.</p>
            </div>
            <button type="button" class="act btn" data-bs-toggle="modal" data-bs-target="#alatModal" id="btnTambahAlat">Tambah alat baru</button>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="alatLabId">Lab</label>
                <select class="form-select" id="alatLabId" name="alatLabId"></select>
            </div>
        </div>
        <div id="alatState" class="alert alert-secondary" role="status">Pilih lab untuk memuat alat.</div>
        <div class="table-responsive">
            <table class="table grid-table d-none" id="alatTable">
                <thead>
                    <tr><th scope="col">Nama</th><th scope="col">Stok</th><th scope="col">Kondisi</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr>
                </thead>
                <tbody id="alatBody"></tbody>
            </table>
        </div>
    </section>

    <div class="modal fade" id="labModal" tabindex="-1" aria-labelledby="labModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="labForm" novalidate>
                <div class="modal-header">
                    <h2 class="h6 mb-0" id="labModalTitle">Tambah lab</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="labId">
                    <div class="mb-2">
                        <label class="form-label" for="kode">Kode</label>
                        <input class="form-control" id="kode" required maxlength="50">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="nama">Nama</label>
                        <input class="form-control" id="nama" required maxlength="255">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="lokasi">Lokasi</label>
                        <input class="form-control" id="lokasi" required maxlength="255">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="form-label" for="kapasitas">Kapasitas</label>
                            <input class="form-control" type="number" id="kapasitas" min="1" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label" for="is_active">Status</label>
                            <select class="form-select" id="is_active">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="deskripsi">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" rows="2"></textarea>
                    </div>
                    <div class="alert alert-danger d-none" id="labError" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn act-ghost" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn act" id="btnSimpanLab">Simpan lab</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="alatModal" tabindex="-1" aria-labelledby="alatModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="alatForm" novalidate>
                <div class="modal-header">
                    <h2 class="h6 mb-0" id="alatModalTitle">Tambah alat</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="alatId">
                    <div class="mb-2">
                        <label class="form-label" for="alat_lab_id">Lab</label>
                        <select class="form-select" id="alat_lab_id" required></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="alat_nama">Nama alat</label>
                        <input class="form-control" id="alat_nama" required maxlength="255">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="form-label" for="alat_stok">Stok</label>
                            <input class="form-control" type="number" id="alat_stok" min="0" max="100000" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label" for="alat_kondisi">Kondisi</label>
                            <select class="form-select" id="alat_kondisi">
                                <option value="baik">Baik</option>
                                <option value="perlu_perbaikan">Perlu perbaikan</option>
                                <option value="rusak">Rusak</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-danger d-none" id="alatError" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn act-ghost" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn act" id="btnSimpanAlat">Simpan alat</button>
                </div>
            </form>
        </div>
    </div>
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
        const table = document.getElementById('labTable');
        const body = document.getElementById('labBody');
        const state = document.getElementById('labState');
        const form = document.getElementById('labForm');
        const err = document.getElementById('labError');
        const modal = new bootstrap.Modal(document.getElementById('labModal'));

        async function load() {
            state.className = 'alert alert-secondary';
            state.textContent = 'Memuat daftar lab.';
            table.classList.add('d-none');
            body.innerHTML = '';
            try {
                const res = await Api.get('/labs?per_page=50');
                const items = res.data || [];
                if (!items.length) {
                    state.textContent = 'Belum ada lab. Tambah lab pertama lewat tombol di atas.';
                    return;
                }
                state.classList.add('d-none');
                table.classList.remove('d-none');
                isiPilihanLab(items);
                items.forEach(lab => {
                    const tr = document.createElement('tr');
                    const nama = document.createElement('td');
                    nama.textContent = `${lab.nama} (${lab.kode})`;
                    const lokasi = document.createElement('td');
                    lokasi.textContent = lab.lokasi;
                    const kap = document.createElement('td');
                    kap.textContent = `${lab.kapasitas} orang`;
                    const st = document.createElement('td');
                    const badge = document.createElement('span');
                    badge.className = `badge badge-status ${lab.is_active ? 'tag-on' : 'tag-off'}`;
                    badge.textContent = lab.is_active ? 'Aktif' : 'Nonaktif';
                    st.appendChild(badge);
                    const aksi = document.createElement('td');
                    const edit = document.createElement('button');
                    edit.type = 'button';
                    edit.className = 'btn btn-sm act-ghost act-sm me-1';
                    edit.textContent = 'Ubah';
                    edit.addEventListener('click', () => {
                        document.getElementById('labModalTitle').textContent = 'Ubah lab';
                        document.getElementById('labId').value = lab.id;
                        document.getElementById('kode').value = lab.kode;
                        document.getElementById('nama').value = lab.nama;
                        document.getElementById('lokasi').value = lab.lokasi;
                        document.getElementById('kapasitas').value = lab.kapasitas;
                        document.getElementById('is_active').value = lab.is_active ? '1' : '0';
                        document.getElementById('deskripsi').value = lab.deskripsi || '';
                        modal.show();
                    });
                    const del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'btn btn-sm btn-outline-danger';
                    del.textContent = 'Hapus';
                    del.addEventListener('click', async () => {
                        if (!confirm(`Hapus lab ${lab.nama}? Lab dengan peminjaman aktif tidak bisa dihapus.`)) return;
                        try {
                            await Api.delete(`/labs/${lab.id}`);
                            showToast('Lab dihapus.');
                            load();
                        } catch (e) { showToast(e.message || 'Gagal menghapus.', 'error'); }
                    });
                    aksi.append(edit, del);
                    tr.append(nama, lokasi, kap, st, aksi);
                    body.appendChild(tr);
                });
            } catch (e) {
                failState(state, e.message || 'Daftar lab gagal dimuat.');
            }
        }

        document.getElementById('btnTambahLab').addEventListener('click', () => {
            document.getElementById('labModalTitle').textContent = 'Tambah lab';
            form.reset();
            document.getElementById('labId').value = '';
            err.classList.add('d-none');
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            err.classList.add('d-none');
            const body = {
                kode: document.getElementById('kode').value.trim(),
                nama: document.getElementById('nama').value.trim(),
                lokasi: document.getElementById('lokasi').value.trim(),
                kapasitas: Number(document.getElementById('kapasitas').value),
                is_active: document.getElementById('is_active').value === '1',
                deskripsi: document.getElementById('deskripsi').value.trim() || null,
            };
            const id = document.getElementById('labId').value;
            try {
                if (id) await Api.put(`/labs/${id}`, body);
                else await Api.post('/labs', body);
                modal.hide();
                showToast('Lab tersimpan.');
                load();
            } catch (e2) {
                err.classList.remove('d-none');
                err.textContent = e2.message || 'Lab gagal disimpan.';
            }
        });

        const alatLabSel = document.getElementById('alatLabId');
        const alatFormLabSel = document.getElementById('alat_lab_id');
        const alatTable = document.getElementById('alatTable');
        const alatBody = document.getElementById('alatBody');
        const alatState = document.getElementById('alatState');
        const alatForm = document.getElementById('alatForm');
        const alatErr = document.getElementById('alatError');
        const alatModal = new bootstrap.Modal(document.getElementById('alatModal'));

        function isiPilihanLab(items) {
            [alatLabSel, alatFormLabSel].forEach(sel => {
                sel.innerHTML = '';
                const kosong = document.createElement('option');
                kosong.value = '';
                kosong.textContent = 'Pilih lab';
                sel.appendChild(kosong);
                items.forEach(lab => {
                    const opt = document.createElement('option');
                    opt.value = lab.id;
                    opt.textContent = `${lab.kode} (${lab.nama})`;
                    sel.appendChild(opt);
                });
            });
            if (items.length && !alatLabSel.value) {
                alatLabSel.value = items[0].id;
                loadAlat(items[0].id);
            }
        }

        async function loadAlat(labId) {
            alatBody.innerHTML = '';
            if (!labId) {
                alatState.className = 'alert alert-secondary';
                alatState.textContent = 'Pilih lab untuk memuat alat.';
                alatTable.classList.add('d-none');
                return;
            }
            alatState.className = 'alert alert-secondary';
            alatState.textContent = 'Memuat alat lab.';
            alatTable.classList.add('d-none');
            try {
                const res = await Api.get(`/labs/${labId}`, { auth: false });
                const alats = res.data.alat || [];
                if (!alats.length) {
                    alatState.textContent = 'Lab ini belum memiliki alat. Tambah alat pertama lewat tombol di atas.';
                    return;
                }
                alatState.classList.add('d-none');
                alatTable.classList.remove('d-none');
                alats.forEach(a => {
                    const tr = document.createElement('tr');
                    const nama = document.createElement('td');
                    nama.textContent = a.nama;
                    const stok = document.createElement('td');
                    stok.textContent = a.stok;
                    const kondisi = document.createElement('td');
                    kondisi.textContent = a.kondisi;
                    const aksi = document.createElement('td');
                    const edit = document.createElement('button');
                    edit.type = 'button';
                    edit.className = 'btn btn-sm act-ghost act-sm me-1';
                    edit.textContent = 'Ubah';
                    edit.addEventListener('click', () => {
                        document.getElementById('alatModalTitle').textContent = 'Ubah alat';
                        document.getElementById('alatId').value = a.id;
                        document.getElementById('alat_lab_id').value = labId;
                        document.getElementById('alat_nama').value = a.nama;
                        document.getElementById('alat_stok').value = a.stok;
                        document.getElementById('alat_kondisi').value = a.kondisi;
                        alatErr.classList.add('d-none');
                        alatModal.show();
                    });
                    const del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'btn btn-sm btn-outline-danger';
                    del.textContent = 'Hapus';
                    del.addEventListener('click', async () => {
                        if (!confirm(`Hapus alat ${a.nama}? Alat yang dipakai peminjaman aktif tidak bisa dihapus.`)) return;
                        try {
                            await Api.delete(`/alats/${a.id}`);
                            showToast('Alat dihapus.');
                            loadAlat(labId);
                        } catch (e) { showToast(e.message || 'Gagal menghapus alat.', 'error'); }
                    });
                    aksi.append(edit, del);
                    tr.append(nama, stok, kondisi, aksi);
                    alatBody.appendChild(tr);
                });
            } catch (e) {
                failState(alatState, e.message || 'Alat lab gagal dimuat.');
            }
        }

        alatLabSel.addEventListener('change', () => loadAlat(alatLabSel.value));

        document.getElementById('btnTambahAlat').addEventListener('click', () => {
            document.getElementById('alatModalTitle').textContent = 'Tambah alat';
            alatForm.reset();
            document.getElementById('alatId').value = '';
            if (alatLabSel.value) document.getElementById('alat_lab_id').value = alatLabSel.value;
            alatErr.classList.add('d-none');
        });

        alatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            alatErr.classList.add('d-none');
            const body = {
                lab_id: Number(document.getElementById('alat_lab_id').value),
                nama: document.getElementById('alat_nama').value.trim(),
                stok: Number(document.getElementById('alat_stok').value),
                kondisi: document.getElementById('alat_kondisi').value,
            };
            const id = document.getElementById('alatId').value;
            try {
                if (id) await Api.put(`/alats/${id}`, body);
                else await Api.post('/alats', body);
                alatModal.hide();
                showToast('Alat tersimpan.');
                loadAlat(alatLabSel.value || body.lab_id);
            } catch (e2) {
                alatErr.classList.remove('d-none');
                alatErr.textContent = e2.message || 'Alat gagal disimpan.';
            }
        });

        load();
    });
</script>
@endpush
