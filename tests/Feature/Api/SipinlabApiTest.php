<?php

namespace Tests\Feature\Api;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Alat;
use App\Models\Lab;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji API SiPinLab: hak akses, validasi, aturan bisnis, format response.
 * Jalankan: php artisan test --filter=SipinlabApiTest
 */
class SipinlabApiTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- helper

    private function admin(string $email = 'admin@test.id'): User
    {
        return $this->buatUser('Admin', $email, RoleUser::Admin);
    }

    private function mhs(string $email = 'mhs@test.id'): User
    {
        return $this->buatUser('Mahasiswa '.$email, $email, RoleUser::Mahasiswa);
    }

    /** 'role' tidak fillable (anti mass assignment), jadi diset lewat forceFill. */
    private function buatUser(string $nama, string $email, RoleUser $role): User
    {
        $user = new User(['name' => $nama, 'email' => $email, 'password' => 'password']);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function lab(array $o = []): Lab
    {
        return Lab::create(array_merge([
            'kode' => 'LAB-'.Str::upper(Str::random(5)),
            'nama' => 'Lab Embedded',
            'lokasi' => 'Gedung A',
            'kapasitas' => 20,
            'is_active' => true,
        ], $o));
    }

    private function alat(Lab $lab, array $o = []): Alat
    {
        return Alat::create(array_merge(['lab_id' => $lab->id, 'nama' => 'Osiloskop', 'stok' => 2, 'kondisi' => 'baik'], $o));
    }

    private function tanggal(): string
    {
        return now()->addDays(3)->toDateString();
    }

    private function payload(Lab $lab, array $o = []): array
    {
        return array_merge([
            'lab_id' => $lab->id,
            'judul_kegiatan' => 'Project mikrokontroler',
            'tujuan' => 'project_matkul',
            'tanggal' => $this->tanggal(),
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:00',
        ], $o);
    }

    private function buatPeminjaman(User $user, Lab $lab, string $status = 'diajukan', string $mulai = '09:00:00', string $selesai = '11:00:00'): Peminjaman
    {
        return Peminjaman::create([
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'judul_kegiatan' => 'Kegiatan',
            'tujuan' => 'latihan',
            'tanggal' => $this->tanggal(),
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'status' => $status,
        ]);
    }

    // ----------------------------------------------------------- autentikasi

    public function test_endpoint_terproteksi_mengembalikan_401_format_seragam(): void
    {
        $this->getJson('/api/peminjaman')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_endpoint_tidak_ada_mengembalikan_404_format_seragam(): void
    {
        $this->getJson('/api/tidak-ada')->assertStatus(404)->assertJsonPath('success', false);
    }

    // ------------------------------------------------------------------- lab

    public function test_daftar_lab_publik_menyembunyikan_lab_nonaktif_dan_admin_bisa_melihat_semua(): void
    {
        $this->lab(['nama' => 'Lab Aktif']);
        $this->lab(['nama' => 'Lab Mati', 'is_active' => false]);

        $this->getJson('/api/labs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page'], 'links']);

        Sanctum::actingAs($this->admin(), ['*']);
        $this->getJson('/api/labs')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_pencarian_lab_dengan_q(): void
    {
        $this->lab(['nama' => 'Lab Jaringan', 'kode' => 'LAB-NET']);
        $this->lab(['nama' => 'Lab Embedded', 'kode' => 'LAB-EMB']);

        $this->getJson('/api/labs?q=jaringan')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.kode', 'LAB-NET');
    }

    public function test_mahasiswa_tidak_boleh_membuat_lab(): void
    {
        Sanctum::actingAs($this->mhs(), ['*']);

        $this->postJson('/api/labs', ['kode' => 'X', 'nama' => 'X', 'lokasi' => 'X', 'kapasitas' => 5])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_membuat_lab_dan_validasi_gagal_422(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $this->postJson('/api/labs', ['kode' => 'LAB-NEW', 'nama' => 'Lab Baru', 'lokasi' => 'Gedung B', 'kapasitas' => 15])
            ->assertStatus(201)
            ->assertJsonPath('data.kode', 'LAB-NEW');

        $this->postJson('/api/labs', ['kode' => 'LAB-NEW'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['nama', 'lokasi', 'kapasitas'], 'errors');
    }

    public function test_jadwal_lab_menampilkan_slot_disetujui_saja(): void
    {
        $lab = $this->lab();
        $u = $this->mhs();
        $this->buatPeminjaman($u, $lab, 'disetujui', '09:00:00', '11:00:00');
        $this->buatPeminjaman($u, $lab, 'diajukan', '13:00:00', '14:00:00');

        $this->getJson("/api/labs/{$lab->id}/jadwal?tanggal=".$this->tanggal())
            ->assertOk()
            ->assertJsonCount(1, 'data.slot_terpakai')
            ->assertJsonPath('data.slot_terpakai.0.jam_mulai', '09:00');

        $this->getJson("/api/labs/{$lab->id}/jadwal")->assertStatus(422);
    }

    // ------------------------------------------------------------ pengajuan

    public function test_mahasiswa_mengajukan_peminjaman_dengan_alat(): void
    {
        $lab = $this->lab();
        $alat = $this->alat($lab);
        Sanctum::actingAs($this->mhs(), ['*']);

        $this->postJson('/api/peminjaman', $this->payload($lab, ['alat' => [['alat_id' => $alat->id, 'jumlah' => 1]]]))
            ->assertStatus(201)
            ->assertJsonPath('data.status.value', 'diajukan')
            ->assertJsonPath('data.alat.0.jumlah_dipinjam', 1)
            ->assertJsonPath('data.jam_mulai', '09:00');
    }

    public function test_jam_di_luar_operasional_ditolak_422(): void
    {
        $lab = $this->lab();
        Sanctum::actingAs($this->mhs(), ['*']);

        $this->postJson('/api/peminjaman', $this->payload($lab, ['jam_mulai' => '06:00', 'jam_selesai' => '08:00']))
            ->assertStatus(422)->assertJsonValidationErrors(['jam_mulai'], 'errors');

        $this->postJson('/api/peminjaman', $this->payload($lab, ['jam_mulai' => '09:00', 'jam_selesai' => '15:00']))
            ->assertStatus(422)->assertJsonValidationErrors(['jam_selesai'], 'errors');
    }

    public function test_tanggal_masa_lalu_dan_jam_terbalik_ditolak(): void
    {
        $lab = $this->lab();
        Sanctum::actingAs($this->mhs(), ['*']);

        $this->postJson('/api/peminjaman', $this->payload($lab, ['tanggal' => now()->subDay()->toDateString()]))
            ->assertStatus(422)->assertJsonValidationErrors(['tanggal'], 'errors');

        $this->postJson('/api/peminjaman', $this->payload($lab, ['jam_mulai' => '11:00', 'jam_selesai' => '09:00']))
            ->assertStatus(422)->assertJsonValidationErrors(['jam_selesai'], 'errors');
    }

    public function test_lab_nonaktif_tidak_bisa_dipinjam(): void
    {
        $lab = $this->lab(['is_active' => false]);
        Sanctum::actingAs($this->mhs(), ['*']);

        $this->postJson('/api/peminjaman', $this->payload($lab))->assertStatus(422)->assertJsonValidationErrors(['lab_id'], 'errors');
    }

    public function test_stok_alat_tidak_mencukupi_ditolak_422(): void
    {
        $lab = $this->lab();
        $alat = $this->alat($lab, ['stok' => 2]);
        Sanctum::actingAs($this->mhs(), ['*']);

        $this->postJson('/api/peminjaman', $this->payload($lab, ['alat' => [['alat_id' => $alat->id, 'jumlah' => 3]]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['alat.0.jumlah'], 'errors');
    }

    public function test_alat_dari_lab_lain_ditolak(): void
    {
        $lab = $this->lab();
        $labLain = $this->lab();
        $alat = $this->alat($labLain);
        Sanctum::actingAs($this->mhs(), ['*']);

        $this->postJson('/api/peminjaman', $this->payload($lab, ['alat' => [['alat_id' => $alat->id, 'jumlah' => 1]]]))
            ->assertStatus(422)->assertJsonValidationErrors(['alat.0.alat_id'], 'errors');
    }

    public function test_maksimal_tiga_pengajuan_menunggu(): void
    {
        $lab = $this->lab();
        $u = $this->mhs();
        $this->buatPeminjaman($u, $lab, 'diajukan', '08:00:00', '09:00:00');
        $this->buatPeminjaman($u, $lab, 'diajukan', '09:00:00', '10:00:00');
        $this->buatPeminjaman($u, $lab, 'diajukan', '10:00:00', '11:00:00');
        Sanctum::actingAs($u, ['*']);

        $this->postJson('/api/peminjaman', $this->payload($lab, ['jam_mulai' => '13:00', 'jam_selesai' => '14:00']))
            ->assertStatus(422)->assertJsonPath('success', false);
    }

    // --------------------------------------------------------------- bentrok

    public function test_pengajuan_bentrok_dengan_jadwal_disetujui_ditolak_409_dan_jam_bersambung_diizinkan(): void
    {
        $lab = $this->lab();
        $pemilik = $this->mhs('a@test.id');
        $this->buatPeminjaman($pemilik, $lab, 'disetujui', '09:00:00', '11:00:00');

        Sanctum::actingAs($this->mhs('b@test.id'), ['*']);

        $this->postJson('/api/peminjaman', $this->payload($lab, ['jam_mulai' => '10:00', 'jam_selesai' => '12:00']))
            ->assertStatus(409)->assertJsonPath('success', false);

        // selesai 11:00 == mulai 11:00 -> tidak bentrok
        $this->postJson('/api/peminjaman', $this->payload($lab, ['jam_mulai' => '11:00', 'jam_selesai' => '12:00']))
            ->assertStatus(201);
    }

    public function test_admin_tidak_bisa_menyetujui_dua_pengajuan_bentrok(): void
    {
        $lab = $this->lab();
        $p1 = $this->buatPeminjaman($this->mhs('a@test.id'), $lab, 'diajukan', '09:00:00', '11:00:00');
        $p2 = $this->buatPeminjaman($this->mhs('b@test.id'), $lab, 'diajukan', '10:00:00', '12:00:00');

        Sanctum::actingAs($this->admin(), ['*']);

        $this->patchJson("/api/peminjaman/{$p1->id}/setujui")
            ->assertOk()->assertJsonPath('data.status.value', 'disetujui');

        $this->patchJson("/api/peminjaman/{$p2->id}/setujui")
            ->assertStatus(409);

        $this->assertSame(StatusPeminjaman::Diajukan, $p2->fresh()->status);
    }

    public function test_stok_alat_dicek_ulang_saat_persetujuan(): void
    {
        $lab = $this->lab();
        $alat = $this->alat($lab, ['stok' => 2]);

        $p = $this->buatPeminjaman($this->mhs(), $lab, 'diajukan', '09:00:00', '10:00:00');
        $p->alats()->attach($alat->id, ['jumlah' => 2]);

        // stok dikurangi admin setelah pengajuan dibuat
        $alat->update(['stok' => 1]);

        Sanctum::actingAs($this->admin(), ['*']);

        $this->patchJson("/api/peminjaman/{$p->id}/setujui")->assertStatus(409);
        $this->assertSame(StatusPeminjaman::Diajukan, $p->fresh()->status);
    }

    // ------------------------------------------------------------ hak akses

    public function test_mahasiswa_hanya_melihat_peminjaman_miliknya(): void
    {
        $lab = $this->lab();
        $a = $this->mhs('a@test.id');
        $b = $this->mhs('b@test.id');
        $milikA = $this->buatPeminjaman($a, $lab);
        $this->buatPeminjaman($b, $lab, 'diajukan', '13:00:00', '14:00:00');

        Sanctum::actingAs($a, ['*']);

        $this->getJson('/api/peminjaman')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/peminjaman/{$milikA->id}")->assertOk();

        Sanctum::actingAs($b, ['*']);
        $this->getJson("/api/peminjaman/{$milikA->id}")->assertStatus(403);
    }

    public function test_admin_melihat_semua_peminjaman_dan_bisa_filter_status(): void
    {
        $lab = $this->lab();
        $this->buatPeminjaman($this->mhs('a@test.id'), $lab, 'diajukan', '08:00:00', '09:00:00');
        $this->buatPeminjaman($this->mhs('b@test.id'), $lab, 'ditolak', '13:00:00', '14:00:00');

        Sanctum::actingAs($this->admin(), ['*']);

        $this->getJson('/api/peminjaman')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/peminjaman?status=ditolak')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status.value', 'ditolak');
    }

    public function test_mahasiswa_tidak_bisa_menyetujui_menolak_atau_membuka_endpoint_admin(): void
    {
        $lab = $this->lab();
        $u = $this->mhs();
        $p = $this->buatPeminjaman($u, $lab);
        Sanctum::actingAs($u, ['*']);

        $this->patchJson("/api/peminjaman/{$p->id}/setujui")->assertStatus(403);
        $this->patchJson("/api/peminjaman/{$p->id}/tolak", ['catatan_admin' => 'x'])->assertStatus(403);
        $this->getJson('/api/users')->assertStatus(403);
        $this->getJson('/api/statistik')->assertStatus(403);
    }

    // ------------------------------------------------------- alur status

    public function test_tolak_wajib_catatan_dan_status_berubah(): void
    {
        $lab = $this->lab();
        $p = $this->buatPeminjaman($this->mhs(), $lab);
        Sanctum::actingAs($this->admin(), ['*']);

        $this->patchJson("/api/peminjaman/{$p->id}/tolak")->assertStatus(422)->assertJsonValidationErrors(['catatan_admin'], 'errors');

        $this->patchJson("/api/peminjaman/{$p->id}/tolak", ['catatan_admin' => 'Lab dipakai praktikum'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'ditolak')
            ->assertJsonPath('data.catatan_admin', 'Lab dipakai praktikum');
    }

    public function test_transisi_status_tidak_valid_409(): void
    {
        $lab = $this->lab();
        $p = $this->buatPeminjaman($this->mhs(), $lab, 'ditolak');
        Sanctum::actingAs($this->admin(), ['*']);

        $this->patchJson("/api/peminjaman/{$p->id}/setujui")->assertStatus(409);
        $this->patchJson("/api/peminjaman/{$p->id}/selesai")->assertStatus(409);
    }

    public function test_alur_lengkap_ajukan_setujui_selesai(): void
    {
        config(['sipinlab.selesai_setelah_mulai' => false]);
        $lab = $this->lab();
        $u = $this->mhs();
        $admin = $this->admin();

        Sanctum::actingAs($u, ['*']);
        $id = $this->postJson('/api/peminjaman', $this->payload($lab))->assertStatus(201)->json('data.id');

        Sanctum::actingAs($admin, ['*']);
        $this->patchJson("/api/peminjaman/{$id}/setujui", ['catatan_admin' => 'Silakan'])->assertOk();
        $this->patchJson("/api/peminjaman/{$id}/selesai")->assertOk()->assertJsonPath('data.status.value', 'selesai');
        $this->deleteJson("/api/peminjaman/{$id}")->assertOk();
        $this->getJson("/api/peminjaman/{$id}")->assertStatus(404);
    }

    public function test_pemilik_bisa_mengubah_dan_membatalkan_saat_diajukan(): void
    {
        $lab = $this->lab();
        $u = $this->mhs();
        $p = $this->buatPeminjaman($u, $lab);
        Sanctum::actingAs($u, ['*']);

        $this->putJson("/api/peminjaman/{$p->id}", ['judul_kegiatan' => 'Judul baru'])
            ->assertOk()->assertJsonPath('data.judul_kegiatan', 'Judul baru');

        $this->patchJson("/api/peminjaman/{$p->id}/batalkan")
            ->assertOk()->assertJsonPath('data.status.value', 'dibatalkan');

        $this->putJson("/api/peminjaman/{$p->id}", ['judul_kegiatan' => 'Lagi'])->assertStatus(409);
        $this->patchJson("/api/peminjaman/{$p->id}/batalkan")->assertStatus(409);
    }

    public function test_orang_lain_tidak_bisa_mengubah_atau_membatalkan(): void
    {
        $lab = $this->lab();
        $p = $this->buatPeminjaman($this->mhs('a@test.id'), $lab);
        Sanctum::actingAs($this->mhs('b@test.id'), ['*']);

        $this->putJson("/api/peminjaman/{$p->id}", ['judul_kegiatan' => 'Curang'])->assertStatus(403);
        $this->patchJson("/api/peminjaman/{$p->id}/batalkan")->assertStatus(403);
    }

    // ------------------------------------------------------- admin lain

    public function test_lab_dengan_peminjaman_disetujui_mendatang_tidak_bisa_dihapus(): void
    {
        $lab = $this->lab();
        $this->buatPeminjaman($this->mhs(), $lab, 'disetujui');
        Sanctum::actingAs($this->admin(), ['*']);

        $this->deleteJson("/api/labs/{$lab->id}")->assertStatus(409);

        $kosong = $this->lab();
        $this->deleteJson("/api/labs/{$kosong->id}")->assertOk();
        $this->assertSoftDeleted('labs', ['id' => $kosong->id]);
    }

    public function test_statistik_untuk_admin(): void
    {
        $lab = $this->lab();
        $this->buatPeminjaman($this->mhs(), $lab, 'diajukan');
        Sanctum::actingAs($this->admin(), ['*']);

        $this->getJson('/api/statistik')
            ->assertOk()
            ->assertJsonPath('data.per_status.diajukan', 1)
            ->assertJsonPath('data.per_status.disetujui', 0)
            ->assertJsonPath('data.menunggu_persetujuan', 1);
    }

    public function test_admin_tidak_bisa_menghapus_diri_sendiri_dan_user_berisi_riwayat(): void
    {
        $admin = $this->admin();
        $lab = $this->lab();
        $u = $this->mhs();
        $this->buatPeminjaman($u, $lab);
        Sanctum::actingAs($admin, ['*']);

        $this->deleteJson("/api/users/{$admin->id}")->assertStatus(409);
        $this->deleteJson("/api/users/{$u->id}")->assertStatus(409);
        $this->getJson('/api/users?role=mahasiswa')->assertOk()->assertJsonCount(1, 'data');
    }

    // ------------------------------------------------------- ability token

    public function test_ability_token_membatasi_akses_walau_role_admin(): void
    {
        $admin = $this->admin();
        $lab = $this->lab();

        // token admin yang hanya boleh meninjau pengajuan
        Sanctum::actingAs($admin, ['peminjaman:review']);

        $this->postJson('/api/labs', ['kode' => 'LAB-X', 'nama' => 'X', 'lokasi' => 'X', 'kapasitas' => 5])
            ->assertStatus(403);
        $this->getJson('/api/users')->assertStatus(403);
        $this->getJson('/api/statistik')->assertOk();
    }

    public function test_token_mahasiswa_tanpa_ability_tidak_bisa_mengajukan_atau_mengubah(): void
    {
        $lab = $this->lab();
        $u = $this->mhs();
        $p = $this->buatPeminjaman($u, $lab);

        Sanctum::actingAs($u, ['peminjaman:create']);

        $this->postJson('/api/peminjaman', $this->payload($lab, ['jam_mulai' => '13:00', 'jam_selesai' => '14:00']))
            ->assertStatus(201);
        $this->putJson("/api/peminjaman/{$p->id}", ['judul_kegiatan' => 'Ubah'])->assertStatus(403);
        $this->patchJson("/api/peminjaman/{$p->id}/batalkan")->assertStatus(403);
    }

    public function test_abilities_helper_per_role(): void
    {
        $this->assertNotContains('lab:manage', \App\Support\Abilities::for($this->mhs()));
        $this->assertContains('lab:manage', \App\Support\Abilities::for($this->admin()));
    }
}
