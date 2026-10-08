@extends('layouts.app')
@section('title', 'Dasbor admin')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <p class="kicker mb-1">Ruang admin</p>
    <h1 class="fw-bold mb-1">Dasbor admin</h1>
    <p class="text-secondary mb-4">Keputusan utama di halaman ini: pengajuan mana yang perlu diputus hari ini.</p>

    <section aria-labelledby="antrianTitle" class="mb-4">
        <div class="d-flex justify-content-between align-items-baseline mb-2">
            <h2 class="h6 mb-0" id="antrianTitle">Antrian persetujuan</h2>
            <a class="link-ink small" href="/admin/peminjaman">Buka semua tinjauan</a>
        </div>
        <div id="antrianState" class="alert alert-secondary" role="status">Memuat antrian pengajuan.</div>
        <div class="table-responsive">
            <table class="table grid-table d-none" id="antrianTable">
                <thead>
                    <tr><th scope="col">Kegiatan</th><th scope="col">Pengaju</th><th scope="col">Jadwal</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr>
                </thead>
                <tbody id="antrianBody"></tbody>
            </table>
        </div>
    </section>

    <section aria-labelledby="ringkasanTitle" class="mb-4">
        <h2 class="h6" id="ringkasanTitle">Ringkasan angka</h2>
        <div id="statState" class="alert alert-secondary" role="status">Memuat statistik.</div>
        <p id="statLine" class="fs-5 d-none"></p>
        <div class="row g-4">
            <div class="col-md-6">
                <h3 class="h6 text-secondary">Jumlah per status</h3>
                <div class="table-responsive">
                    <table class="table grid-table">
                        <thead>
                            <tr><th scope="col">Status</th><th scope="col">Jumlah</th></tr>
                        </thead>
                        <tbody id="statStatus"></tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h3 class="h6 text-secondary">Lab terpopuler</h3>
                <div class="table-responsive">
                    <table class="table grid-table">
                        <thead>
                            <tr><th scope="col">Lab</th><th scope="col">Peminjaman</th></tr>
                        </thead>
                        <tbody id="statPopuler"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <h2 class="h6">Kelola data</h2>
    <div class="d-flex flex-wrap gap-2">
        <a class="act btn" href="/admin/peminjaman">Tinjau pengajuan</a>
        <a class="act-ghost btn" href="/admin/labs">Kelola lab dan alat</a>
        <a class="act-ghost btn" href="/admin/pengguna">Kelola pengguna</a>
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

    bootWhenApiReady(async () => {
        if (!requireAdmin()) return;

        const antrianState = document.getElementById('antrianState');
        const antrianTable = document.getElementById('antrianTable');
        const antrianBody = document.getElementById('antrianBody');
        try {
            const res = await Api.get('/peminjaman?status=diajukan&per_page=5');
            const items = res.data || [];
            if (!items.length) {
                antrianState.className = 'alert alert-primary';
                antrianState.textContent = 'Antrian kosong. Semua pengajuan sudah diputus.';
            } else {
                antrianState.classList.add('d-none');
                antrianTable.classList.remove('d-none');
                items.forEach(p => {
                    const tr = document.createElement('tr');
                    const keg = document.createElement('td');
                    keg.textContent = p.judul_kegiatan;
                    const pemohon = document.createElement('td');
                    pemohon.textContent = p.user ? p.user.name : 'Pengaju';
                    const jadwal = document.createElement('td');
                    jadwal.textContent = `${p.tanggal}, ${p.jam_mulai} sampai ${p.jam_selesai}`;
                    const aksi = document.createElement('td');
                    const detail = document.createElement('a');
                    detail.className = 'link-ink me-2';
                    detail.href = `/peminjaman/${p.id}`;
                    detail.textContent = 'Periksa';
                    const ok = document.createElement('button');
                    ok.type = 'button';
                    ok.className = 'btn btn-sm act act-sm';
                    ok.textContent = 'Setujui';
                    ok.addEventListener('click', async () => {
                        try {
                            await Api.patch(`/peminjaman/${p.id}/setujui`, {});
                            showToast('Pengajuan disetujui.');
                            setTimeout(() => window.location.reload(), 600);
                        } catch (e) { showToast(e.message || 'Gagal menyetujui.', 'error'); }
                    });
                    aksi.append(detail, ok);
                    tr.append(keg, pemohon, jadwal, aksi);
                    antrianBody.appendChild(tr);
                });
            }
        } catch (e) {
            failState(antrianState, e.message || 'Antrian gagal dimuat.');
        }

        try {
            const res = await Api.get('/statistik');
            const d = res.data;
            document.getElementById('statState').classList.add('d-none');
            const line = document.getElementById('statLine');
            line.classList.remove('d-none');
            line.textContent = `${d.menunggu_persetujuan} menunggu · ${d.total_peminjaman} total · ${d.total_lab_aktif} lab aktif · ${d.total_mahasiswa} mahasiswa.`;
            const ul = document.getElementById('statStatus');
            Object.entries(d.per_status || {}).forEach(([k, v]) => {
                const tr = document.createElement('tr');
                const nama = document.createElement('td');
                nama.textContent = k;
                const n = document.createElement('td');
                n.textContent = v;
                tr.append(nama, n);
                ul.appendChild(tr);
            });
            const pop = document.getElementById('statPopuler');
            if (!(d.lab_terpopuler || []).length) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 2;
                td.className = 'text-secondary';
                td.textContent = 'Belum ada peminjaman yang disetujui.';
                tr.appendChild(td);
                pop.appendChild(tr);
            } else {
                d.lab_terpopuler.forEach(l => {
                    const tr = document.createElement('tr');
                    const nama = document.createElement('td');
                    nama.textContent = `${l.nama} (${l.kode})`;
                    const n = document.createElement('td');
                    n.textContent = l.total_peminjaman;
                    tr.append(nama, n);
                    pop.appendChild(tr);
                });
            }
        } catch (e) {
            failState(document.getElementById('statState'), e.message || 'Statistik gagal dimuat.');
        }
    });
</script>
@endpush
