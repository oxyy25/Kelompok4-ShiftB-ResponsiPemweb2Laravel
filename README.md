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
| 1 | M.Fawaz Akbar | H1H024046 | Shift B | Shift B | CRUD Login & Register, Frontend Web | [YouTube/Drive](https://...) |
| 2 | Finda Wulan Febrianti | H1H024055 | Shift C | Shift B| Database: Migration, Model, Seeder | YouTube (https://youtu.be/5_gY-6p-EGI) |
| 3 | Fathah Ikhwansyah | H1H024063 | Shift A | Shift B | Backend REST API: CRUD Lab, Alat dan Pengguna | [YouTube/Drive](https://...) |

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
