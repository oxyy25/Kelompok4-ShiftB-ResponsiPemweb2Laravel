@extends('layouts.app')
@section('title', 'Detail peminjaman')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <a class="link-ink d-inline-block mb-2" href="/peminjaman">Kembali ke daftar peminjaman</a>
    <div id="pinjamState" class="alert alert-secondary" role="status">Memuat detail pengajuan.</div>

    <article id="pinjamDetail" class="d-none" aria-labelledby="pinjamJudul">
        <p class="kicker mb-1">Rincian pengajuan</p>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <h1 class="fw-bold mb-0" id="pinjamJudul"></h1>
            <span id="pinjamStatus" class="mt-2"></span>
        </div>
        <hr>
        <dl class="spec row">
            <dt class="col-sm-4">Lab</dt>
            <dd class="col-sm-8" id="pinjamLab"></dd>
            <dt class="col-sm-4">Jadwal</dt>
            <dd class="col-sm-8" id="pinjamJadwal"></dd>
            <dt class="col-sm-4">Tujuan</dt>
            <dd class="col-sm-8" id="pinjamTujuan"></dd>
            <dt class="col-sm-4">Keterangan</dt>
            <dd class="col-sm-8" id="pinjamKet"></dd>
            <dt class="col-sm-4">Diajukan oleh</dt>
            <dd class="col-sm-8" id="pinjamUser"></dd>
            <dt class="col-sm-4">Catatan admin</dt>
            <dd class="col-sm-8" id="pinjamCatatan">Belum ada catatan.</dd>
        </dl>

        <h2 class="h6 mt-3">Alat yang diajukan</h2>
        <div class="table-responsive">
            <table class="table grid-table" id="pinjamAlatTable">
                <thead>
                    <tr><th scope="col">Nama</th><th scope="col">Jumlah</th></tr>
                </thead>
                <tbody id="pinjamAlatBody"></tbody>
            </table>
        </div>

        <div id="aksiUser" class="d-flex flex-wrap gap-2 mt-3"></div>

        <div id="panelAdmin" class="time-panel p-3 mt-4 d-none">
            <h2 class="h6">Tindakan admin</h2>
            <div class="mb-2">
                <label class="form-label" for="catatan_admin">Catatan admin</label>
                <textarea class="form-control" id="catatan_admin" rows="2" placeholder="Wajib diisi saat menolak"></textarea>
            </div>
            <div class="d-flex flex-wrap gap-2" id="aksiAdmin"></div>
        </div>
    </article>
</main>
@endsection

@push('scripts')
<script>
    function bootWhenApiReady(fn, attempts = 100) {
        if (window.Api) { fn(); return; }
        if (attempts <= 0) return;
        setTimeout(() => bootWhenApiReady(fn, attempts - 1), 50);
    }

    bootWhenApiReady(async () => {
        if (!requireLogin()) return;
        const id = window.location.pathname.split('/').pop();
        const state = document.getElementById('pinjamState');
        const detail = document.getElementById('pinjamDetail');
        const me = currentUser();
        const admin = isAdmin();

        let p;
        try {
            const res = await Api.get(`/peminjaman/${id}`);
            p = res.data;
        } catch (e) {
            failState(state, e.message || 'Detail pengajuan gagal dimuat.');
            return;
        }
        state.classList.add('d-none');
        detail.classList.remove('d-none');
        document.getElementById('pinjamJudul').textContent = p.judul_kegiatan;
        document.getElementById('pinjamStatus').appendChild(statusBadge(p.status.value));
        document.getElementById('pinjamLab').textContent = p.lab ? `${p.lab.nama} (${p.lab.kode}), ${p.lab.lokasi}` : 'Lab';
        document.getElementById('pinjamJadwal').textContent = `${p.tanggal}, pukul ${p.jam_mulai} sampai ${p.jam_selesai}`;
        document.getElementById('pinjamTujuan').textContent = p.tujuan_label || p.tujuan;
        document.getElementById('pinjamKet').textContent = p.keterangan || 'Tanpa keterangan tambahan.';
        document.getElementById('pinjamUser').textContent = p.user ? `${p.user.name}${p.user.nim ? ` (${p.user.nim})` : ''}` : 'Tidak diketahui';
        if (p.catatan_admin) document.getElementById('pinjamCatatan').textContent = p.catatan_admin;

        const alatBody = document.getElementById('pinjamAlatBody');
        if (!(p.alat || []).length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 2;
            td.className = 'text-secondary';
            td.textContent = 'Tidak ada alat yang diajukan.';
            tr.appendChild(td);
            alatBody.appendChild(tr);
        } else {
            p.alat.forEach(a => {
                const tr = document.createElement('tr');
                const nama = document.createElement('td');
                nama.textContent = a.nama;
                const jml = document.createElement('td');
                jml.textContent = `${a.jumlah_dipinjam} unit`;
                tr.append(nama, jml);
                alatBody.appendChild(tr);
            });
        }

        const aksiUser = document.getElementById('aksiUser');
        const mine = me && p.user && Number(p.user.id) === Number(me.id);
        if (mine && p.status.value === 'diajukan') {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn act-ghost';
            b.textContent = 'Batalkan pengajuan ini';
            b.addEventListener('click', async () => {
                if (!confirm('Batalkan pengajuan ini?')) return;
                try {
                    await Api.patch(`/peminjaman/${id}/batalkan`);
                    showToast('Pengajuan dibatalkan.');
                    setTimeout(() => window.location.reload(), 600);
                } catch (e) { showToast(e.message || 'Gagal membatalkan.', 'error'); }
            });
            aksiUser.appendChild(b);
        }

        if (admin && ['diajukan', 'disetujui'].includes(p.status.value)) {
            document.getElementById('panelAdmin').classList.remove('d-none');
            const box = document.getElementById('aksiAdmin');
            const cat = () => document.getElementById('catatan_admin').value.trim() || null;
            const mk = (label, cls, fn) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = `btn ${cls}`;
                b.textContent = label;
                b.addEventListener('click', fn);
                box.appendChild(b);
            };
            if (p.status.value === 'diajukan') {
                mk('Setujui pengajuan', 'act', async () => {
                    try {
                        await Api.patch(`/peminjaman/${id}/setujui`, { catatan_admin: cat() });
                        showToast('Pengajuan disetujui.');
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) { showToast(e.message || 'Gagal menyetujui.', 'error'); }
                });
                mk('Tolak pengajuan', 'btn-outline-danger', async () => {
                    const c = cat();
                    if (!c) { showToast('Catatan wajib diisi saat menolak.', 'error'); return; }
                    try {
                        await Api.patch(`/peminjaman/${id}/tolak`, { catatan_admin: c });
                        showToast('Pengajuan ditolak.');
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) { showToast(e.message || 'Gagal menolak.', 'error'); }
                });
            }
            if (p.status.value === 'disetujui') {
                mk('Tandai selesai', 'act', async () => {
                    try {
                        await Api.patch(`/peminjaman/${id}/selesai`);
                        showToast('Peminjaman ditandai selesai.');
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) { showToast(e.message || 'Gagal menandai selesai.', 'error'); }
                });
            }
        }
    });
</script>
@endpush
