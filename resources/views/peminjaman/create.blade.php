@extends('layouts.app')
@section('title', 'Buat pengajuan')

@section('content')
<main class="container py-4" style="max-width: 880px">
    <a class="link-ink d-inline-block mb-2" href="/peminjaman">Kembali ke daftar peminjaman</a>
    <p class="kicker mb-1">Pengajuan baru</p>
    <h1 class="fw-bold mb-1">Buat pengajuan peminjaman</h1>
    <p class="text-secondary mb-4">Maksimal 3 pengajuan menunggu dalam satu waktu. Jam operasional 08.00 sampai 17.00, durasi maksimal 4 jam.</p>

    <form id="pinjamForm" novalidate>
        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6">Kegiatan</h2>
                <div class="mb-3">
                    <label class="form-label" for="lab_id">Lab</label>
                    <select class="form-select" id="lab_id" name="lab_id" required>
                        <option value="">Pilih lab</option>
                    </select>
                    <div class="invalid-feedback" data-error-for="lab_id"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="judul_kegiatan">Judul kegiatan</label>
                    <input class="form-control" type="text" id="judul_kegiatan" name="judul_kegiatan" maxlength="255" required>
                    <div class="invalid-feedback" data-error-for="judul_kegiatan"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="tujuan">Tujuan</label>
                    <select class="form-select" id="tujuan" name="tujuan" required>
                        <option value="">Pilih tujuan</option>
                        <option value="project_matkul">Project mata kuliah</option>
                        <option value="praktikum_pengganti">Praktikum pengganti</option>
                        <option value="latihan">Latihan</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                    <div class="invalid-feedback" data-error-for="tujuan"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="keterangan">Keterangan <span class="text-secondary small">(opsional)</span></label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3" maxlength="2000"></textarea>
                    <div class="invalid-feedback" data-error-for="keterangan"></div>
                </div>
            </div>
            <div class="col-md-6">
                <h2 class="h6">Jadwal</h2>
                <div class="mb-3">
                    <label class="form-label" for="tanggal">Tanggal</label>
                    <input class="form-control" type="date" id="tanggal" name="tanggal" required>
                    <div class="invalid-feedback" data-error-for="tanggal"></div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label" for="jam_mulai">Jam mulai</label>
                        <input class="form-control" type="time" id="jam_mulai" name="jam_mulai" required>
                        <div class="invalid-feedback" data-error-for="jam_mulai"></div>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="jam_selesai">Jam selesai</label>
                        <input class="form-control" type="time" id="jam_selesai" name="jam_selesai" required>
                        <div class="invalid-feedback" data-error-for="jam_selesai"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <h2 class="h6">Alat yang dibutuhkan <span class="text-secondary small">(opsional)</span></h2>
            <div id="alatState" class="alert alert-secondary small" role="status">Pilih lab untuk melihat alat yang tersedia.</div>
            <div id="alatList" class="d-grid gap-2"></div>
        </div>
        <button type="submit" class="act btn w-100 mt-3" id="btnSubmit">Kirim pengajuan</button>
    </form>
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
        const form = document.getElementById('pinjamForm');
        const btn = document.getElementById('btnSubmit');
        const labSel = document.getElementById('lab_id');
        const alatList = document.getElementById('alatList');
        const alatState = document.getElementById('alatState');
        const presetLab = new URLSearchParams(window.location.search).get('lab_id');

        Api.get('/labs?per_page=50', { auth: false }).then(res => {
            (res.data || []).forEach(lab => {
                const opt = document.createElement('option');
                opt.value = lab.id;
                opt.textContent = `${lab.kode} (${lab.nama})`;
                labSel.appendChild(opt);
            });
            if (presetLab) {
                labSel.value = presetLab;
                loadAlat(presetLab);
            }
        }).catch(() => {});

        labSel.addEventListener('change', () => loadAlat(labSel.value));

        async function loadAlat(labId) {
            alatList.innerHTML = '';
            if (!labId) {
                alatState.className = 'alert alert-secondary small';
                alatState.textContent = 'Pilih lab untuk melihat alat yang tersedia.';
                return;
            }
            alatState.textContent = 'Memuat alat lab.';
            try {
                const res = await Api.get(`/labs/${labId}`, { auth: false });
                const alats = res.data.alat || [];
                if (!alats.length) {
                    alatState.textContent = 'Lab ini belum memiliki alat yang terdaftar.';
                    return;
                }
                alatState.classList.add('d-none');
                alats.forEach(a => {
                    const row = document.createElement('div');
                    row.className = 'border rounded bg-white p-2 w-100';
                    const top = document.createElement('div');
                    top.className = 'form-check';
                    const check = document.createElement('input');
                    check.type = 'checkbox';
                    check.className = 'form-check-input';
                    check.id = `alat-${a.id}`;
                    check.value = a.id;
                    const label = document.createElement('label');
                    label.className = 'form-check-label fw-medium';
                    label.htmlFor = `alat-${a.id}`;
                    label.textContent = a.nama;
                    const sub = document.createElement('div');
                    sub.className = 'text-secondary small ms-4';
                    sub.textContent = `Stok ${a.stok}, kondisi ${a.kondisi}`;
                    top.append(check, label);
                    const bottom = document.createElement('div');
                    bottom.className = 'd-flex align-items-center gap-2 mt-2 ms-4';
                    const qtyLabel = document.createElement('label');
                    qtyLabel.className = 'form-label small text-secondary mb-0';
                    qtyLabel.htmlFor = `jumlah-${a.id}`;
                    qtyLabel.textContent = 'Jumlah:';
                    const qty = document.createElement('input');
                    qty.type = 'number';
                    qty.id = `jumlah-${a.id}`;
                    qty.className = 'form-control';
                    qty.style.width = '84px';
                    qty.style.flexShrink = '0';
                    qty.min = '1';
                    qty.max = String(a.stok);
                    qty.value = '1';
                    qty.disabled = true;
                    bottom.append(qtyLabel, qty);
                    check.addEventListener('change', () => {
                        qty.disabled = !check.checked;
                        row.style.opacity = check.checked ? '1' : '.65';
                    });
                    row.style.opacity = '.65';
                    row.append(top, sub, bottom);
                    row.dataset.alatId = a.id;
                    alatList.appendChild(row);
                });
            } catch (e) {
                failState(alatState, e.message || 'Alat lab gagal dimuat.');
                alatState.classList.add('small');
            }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearFieldErrors(form);
            setLoading(btn, true, 'Mengirim...');
            const alat = [...alatList.querySelectorAll('[data-alat-id]')]
                .filter(row => row.querySelector('input[type=checkbox]').checked)
                .map(row => ({
                    alat_id: Number(row.dataset.alatId),
                    jumlah: Number(row.querySelector('input[type=number]').value) || 1,
                }));
            const body = {
                lab_id: Number(form.lab_id.value),
                judul_kegiatan: form.judul_kegiatan.value.trim(),
                tujuan: form.tujuan.value,
                tanggal: form.tanggal.value,
                jam_mulai: form.jam_mulai.value,
                jam_selesai: form.jam_selesai.value,
                keterangan: form.keterangan.value.trim() || null,
                ...(alat.length ? { alat } : {}),
            };
            try {
                const res = await Api.post('/peminjaman', body);
                showToast(res.message);
                setTimeout(() => window.location.href = `/peminjaman/${res.data.id}`, 600);
            } catch (err) {
                if (err.status === 422) showFieldErrors(form, err.errors);
                showToast(err.message || 'Pengajuan gagal dikirim', 'error');
                setLoading(btn, false);
            }
        });
    });
</script>
@endpush
