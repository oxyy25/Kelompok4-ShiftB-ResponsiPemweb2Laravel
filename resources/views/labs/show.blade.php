@extends('layouts.app')
@section('title', 'Detail lab')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <a class="link-ink d-inline-block mb-2" href="/labs">Kembali ke daftar lab</a>
    <div id="labState" class="alert alert-secondary" role="status">Memuat detail lab.</div>

    <article id="labDetail" class="d-none" aria-labelledby="labNama">
        <p class="kicker mb-1">Katalog</p>
        <h1 class="fw-bold mb-1" id="labNama"></h1>
        <p class="text-secondary mb-3" id="labMeta"></p>
        <p id="labDeskripsi" style="max-width: 62ch"></p>

        <h2 class="h6 mt-4">Alat di lab ini</h2>
        <div id="alatState" class="alert alert-secondary" role="status">Memuat daftar alat.</div>
        <div class="table-responsive">
            <table class="table grid-table d-none" id="alatTable">
                <thead>
                    <tr><th scope="col">Nama</th><th scope="col">Stok</th><th scope="col">Kondisi</th></tr>
                </thead>
                <tbody id="alatBody"></tbody>
            </table>
        </div>

        <h2 class="h6 mt-4">Slot yang sudah terpakai</h2>
        <div class="time-panel p-3 mb-3">
            <form id="jadwalForm" class="row g-2 align-items-end" aria-label="Pilih tanggal jadwal">
                <div class="col-sm-6 col-md-4">
                    <label class="form-label" for="tanggal">Tanggal</label>
                    <input class="form-control" type="date" id="tanggal" name="tanggal" required>
                </div>
                <div class="col-sm-6 col-md-4">
                    <button type="submit" class="act btn" id="btnJadwal">Tampilkan slot</button>
                </div>
            </form>
        </div>
        <div id="jadwalState" class="alert alert-secondary" role="status">Pilih tanggal untuk melihat slot yang sudah terpakai.</div>
        <ul id="slotList" class="list-unstyled"></ul>

        <a class="act btn mt-2" id="btnAjukan" href="/peminjaman/buat">Ajukan peminjaman lab ini</a>
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
        const id = window.location.pathname.split('/').pop();
        const state = document.getElementById('labState');
        const detail = document.getElementById('labDetail');

        try {
            const res = await Api.get(`/labs/${id}`, { auth: false });
            const lab = res.data;
            state.classList.add('d-none');
            detail.classList.remove('d-none');
            document.title = `${lab.nama} · PinLab`;
            document.getElementById('labNama').textContent = lab.nama;
            document.getElementById('labMeta').textContent = `${lab.kode} di ${lab.lokasi}, kapasitas ${lab.kapasitas} orang.`;
            document.getElementById('labDeskripsi').textContent = lab.deskripsi || 'Keterangan lab belum diisi.';
            document.getElementById('btnAjukan').href = `/peminjaman/buat?lab_id=${lab.id}`;

            const alatTable = document.getElementById('alatTable');
            const alatBody = document.getElementById('alatBody');
            const alatState = document.getElementById('alatState');
            const alats = lab.alat || [];
            if (!alats.length) {
                alatState.textContent = 'Belum ada alat yang terdaftar di lab ini.';
            } else {
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
                    tr.append(nama, stok, kondisi);
                    alatBody.appendChild(tr);
                });
            }

            const today = new Date().toISOString().slice(0, 10);
            document.getElementById('tanggal').value = today;
            loadJadwal(id, today);
        } catch (e) {
            failState(state, e.message || 'Detail lab gagal dimuat.');
            return;
        }

        document.getElementById('jadwalForm').addEventListener('submit', (e) => {
            e.preventDefault();
            loadJadwal(id, document.getElementById('tanggal').value);
        });

        async function loadJadwal(labId, tanggal) {
            const box = document.getElementById('jadwalState');
            const list = document.getElementById('slotList');
            list.innerHTML = '';
            box.className = 'alert alert-secondary';
            box.textContent = 'Memuat slot.';
            try {
                const res = await Api.get(`/labs/${labId}/jadwal?tanggal=${tanggal}`, { auth: false });
                const slots = res.data.slot_terpakai || [];
                if (!slots.length) {
                    box.className = 'alert alert-primary';
                    box.textContent = `Tanggal ${tanggal} belum ada slot yang terpakai. Seluruh jam operasional masih lowong.`;
                    return;
                }
                box.classList.add('d-none');
                slots.forEach(s => {
                    const li = document.createElement('li');
                    li.className = 'rule pt-2 pb-2';
                    li.textContent = `${s.jam_mulai} sampai ${s.jam_selesai}: terpakai`;
                    list.appendChild(li);
                });
            } catch (err) {
                failState(box, err.message || 'Jadwal gagal dimuat.');
            }
        }
    });
</script>
@endpush
