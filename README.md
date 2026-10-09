# PinLab
> Portal peminjaman laboratorium — pinjam lab dan alat sesuai jadwal.

---

## 📌 Informasi Kelompok
- **Nomor Kelompok:**  Kelompok 04
- **Shift Praktikum:**  Shift B

---

## 👥 Anggota Kelompok

| No | Nama Lengkap | NIM | Shift Awal | Shift Akhir | Jobdesk / Kontribusi | Link Video Penjelasan |
|---|---|---|---|---|---|---|
| 1 | M.Fawaz Akbar | H1H024046 | Shift B | Shift B | CRUD Login & Register, Frontend Web | [YouTube](https://youtu.be/m-PmxZZwNr4) |
| 2 | Finda Wulan Febrianti | H1H024055 | Shift C | Shift B| Database: Migration, Model, Seeder | YouTube (https://youtu.be/5_gY-6p-EGI) |
| 3 | Fathah Ikhwansyah | H1H024063 | Shift A | Shift B | Backend REST API: CRUD Lab, Alat dan Pengguna | [Drive](https://drive.google.com/drive/folders/1AbDn8OxgOS8-CSV73Sr_OsxsoOKGkZoo?usp=sharing) |

---

## 📖 Deskripsi Aplikasi
PinLab adalah portal peminjaman Ruangan laboratorium. Mahasiswa dapat melihat lab yang terbuka dan slot jadwal yang sudah terpakai, lalu mengajukan peminjaman lab beserta alatnya (judul kegiatan, tujuan, tanggal, jam mulai–selesai). Setiap pengajuan diputus oleh admin (disetujui / ditolak / selesai), dengan aturan operasional pukul 08.00–17.00, durasi maksimal 4 jam, dan maksimal 3 pengajuan menunggu. Admin mengelola data lab, alat, persetujuan peminjaman, statistik, dan pengguna.

---

## ⚙️ Penjelasan Teknis

### 1. Teknologi (Tech Stack)
- **Backend:** Laravel 13.35.0 (PHP 8.5.10, syarat composer `^8.3`)
- **Frontend:** Blade + Bootstrap 5.3.3 (CDN) + Vite + JavaScript vanilla (`resources/js/lab.js`, fetch ke REST API)
- **Database:** MySQL 
- **Library / Package:** Laravel Sanctum 4 (autentikasi token + abilities), Tailwind CSS 4 (`@tailwindcss/vite`), `laravel-vite-plugin`, Pest 5, Pint, Pail

### 2. Fitur Utama & Modul
- **Autentikasi & Otorisasi:** Register, login, logout, dan profil (`POST /api/auth/*`, throttle 5/menit); role `admin` / `mahasiswa` dengan middleware `EnsureAdmin` + Sanctum abilities (`peminjaman:create`, `peminjaman:manage-own`, `peminjaman:review`, `lab:manage`, `user:manage`)
- **Lab:** Mahasiswa melihat daftar, detail, dan jadwal pemakaian lab. Admin CRUD lab + aktivasi/nonaktif (soft delete)
- **Alat:** Mahasiswa login melihat daftar dan detail alat per lab (stok, kondisi). Admin CRUD alat
- **Peminjaman:** Mahasiswa buat, lihat, ubah, dan batalkan pengajuan miliknya dengan validasi bentrok jadwal, admin setujui / tolak / selesaikan / hapus pengajuan, lihat statistik, dan kelola pengguna

### 3. Skema Data Singkat
- `users` (1 : N) `peminjamas` (sebagai peminjam via `user_id`, dan sebagai pemroses via `diproses_oleh`)
- `labs` (1 : N) `alats`
- `labs` (1 : N) `peminjamas`
- `peminjamas` (M : N) `alats` via `alat_peminjaman` (pivot `jumlah`)

### 4. Dokumentasi API
Base URL: `http://localhost:8000/api`. Semua respons JSON dengan format `{ success, message, data }` (list tambah `meta` + `links` untuk paginasi). Endpoint yang butuh login memakai header `Authorization: Bearer <token>` (Sanctum). `POST /api/auth/*` dibatasi throttle 5 request/menit.

**Auth**

| Method | Endpoint | Akses | Keterangan |
|---|---|---|---|
| POST | `/api/auth/register` | Publik | Body: `name`, `email`, `nim?`, `no_hp?`, `password`, `password_confirmation` (min. 8) → `201` |
| POST | `/api/auth/login` | Publik | Body: `email`, `password` → `200` + `{ user, token, token_type, abilities }` |
| POST | `/api/auth/logout` | Login | Hapus token aktif → `200` |
| GET | `/api/auth/me` | Login | Profil user login → `200` |

**Lab**

| Method | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/api/labs` | Publik | Query: `q`, `min_kapasitas`, `is_active` (admin saja), `sort`, `per_page` |
| GET | `/api/labs/{id}` | Publik | Detail lab + daftar alat |
| GET | `/api/labs/{id}/jadwal?tanggal=YYYY-MM-DD` | Publik | Slot terpakai (status `disetujui`) + jam operasional 08.00–17.00 |
| POST | `/api/labs` | Admin (`lab:manage`) | Body: `kode` (unik), `nama`, `lokasi`, `kapasitas` (1–1000), `deskripsi?`, `is_active?` → `201` |
| PUT | `/api/labs/{id}` | Admin (`lab:manage`) | Body sama seperti POST (partial) |
| DELETE | `/api/labs/{id}` | Admin (`lab:manage`) | Soft delete; `409` jika masih ada pengajuan aktif mendatang |

**Alat**

| Method | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/api/alats` | Login | Query: `lab_id`, `kondisi` (`baik`/`perlu_perbaikan`/`rusak`), `q`, `sort`, `per_page` |
| GET | `/api/alats/{id}` | Login | Detail alat + lab |
| POST | `/api/alats` | Admin (`lab:manage`) | Body: `lab_id` (aktif), `nama`, `stok` (≥ 0), `kondisi?` → `201` |
| PUT | `/api/alats/{id}` | Admin (`lab:manage`) | Body sama seperti POST (partial) |
| DELETE | `/api/alats/{id}` | Admin (`lab:manage`) | `409` jika alat masih dipakai pengajuan aktif |

**Peminjaman**

| Method | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/api/peminjaman` | Login | Mahasiswa: miliknya; admin: semua. Query: `status`, `lab_id`, `tanggal`, `q` |
| GET | `/api/peminjaman/{id}` | Pemilik / Admin | Detail + user, lab, alat, pemroses |
| POST | `/api/peminjaman` | Mahasiswa (`peminjaman:create`) | Body: `lab_id`, `judul_kegiatan`, `tujuan` (`project_matkul`/`praktikum_pengganti`/`latihan`/`lainnya`), `keterangan?`, `tanggal` (≥ hari ini), `jam_mulai`/`jam_selesai` (`HH:MM`, 08.00–17.00, maks 4 jam), `alat?[]` (`alat_id`, `jumlah`) → `201`. Aturan: tidak bentrok jadwal, maks 3 pengajuan `diajukan` |
| PUT | `/api/peminjaman/{id}` | Pemilik (`peminjaman:manage-own`) | Ubah pengajuan status `diajukan` |
| PATCH | `/api/peminjaman/{id}/batalkan` | Pemilik (`peminjaman:manage-own`) | Batalkan milik sendiri → status `dibatalkan` |
| PATCH | `/api/peminjaman/{id}/setujui` | Admin (`peminjaman:review`) | Body: `catatan_admin?` → status `disetujui` |
| PATCH | `/api/peminjaman/{id}/tolak` | Admin (`peminjaman:review`) | Body: `catatan_admin` (wajib) → status `ditolak` |
| PATCH | `/api/peminjaman/{id}/selesai` | Admin (`peminjaman:review`) | Tandai `selesai` (setelah jam mulai) |
| DELETE | `/api/peminjaman/{id}` | Admin (`peminjaman:review`) | Hanya data final (`ditolak`/`dibatalkan`/`selesai`) |

**Statistik & Pengguna (admin)**

| Method | Endpoint | Akses | Keterangan |
|---|---|---|---|
| GET | `/api/statistik` | Admin (`peminjaman:review`) | `total_peminjaman`, `per_status`, `menunggu_persetujuan`, `lab_terpopuler` (top 5), `total_lab_aktif`, `total_alat`, `total_mahasiswa` |
| GET | `/api/users` | Admin (`user:manage`) | Query: `role` (`admin`/`mahasiswa`), `q`, `sort`, `per_page` |
| GET | `/api/users/{id}` | Admin (`user:manage`) | Detail pengguna |
| PUT | `/api/users/{id}` | Admin (`user:manage`) | Body: `name?`, `email?`, `nim?`, `no_hp?`, `role?`, `password?` (tidak bisa ubah role sendiri) |
| DELETE | `/api/users/{id}` | Admin (`user:manage`) | `403` hapus diri sendiri; `409` jika punya riwayat peminjaman |

Contoh login:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"mahasiswa@mail.com","password":"password123"}'

# pakai token
curl http://localhost:8000/api/peminjaman \
  -H "Authorization: Bearer <token>"
```

Koleksi Postman: `postman/PinLab.postman_collection.json` (+ environment `PinLab-Local.postman_environment.json`).

---

## 🚀 Panduan Instalasi Lokal

```bash
# Clone repository
git clone https://github.com/oxyy25/Kelompok4-ShiftB-ResponsiPemweb2Laravel.git
cd Kelompok4-ShiftB-ResponsiPemweb2Laravel

# Install dependensi PHP & Node
composer install
npm install

# Konfigurasi Environment
cp .env.example .env
php artisan key:generate

# Konfigurasi database di file .env, lalu migrasi & seed
php artisan migrate --seed

# Jalankan development server
php artisan serve
npm run dev
```
