<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjamas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('lab_id')->constrained('labs');
            $table->string('judul_kegiatan');
            $table->enum('tujuan', ['project_matkul', 'praktikum_pengganti', 'latihan', 'lainnya']);
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'dibatalkan', 'selesai'])
                  ->default('diajukan');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();

            $table->index(['lab_id', 'tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjamas');
    }
};
