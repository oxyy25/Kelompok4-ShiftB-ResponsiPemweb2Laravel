<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alat_peminjaman', function (Blueprint $table) {
            $table->foreignId('peminjaman_id')->constrained('peminjamas')->cascadeOnDelete();
            $table->foreignId('alat_id')->constrained('alats')->cascadeOnDelete();
            $table->unsignedInteger('jumlah')->default(1);
            $table->primary(['peminjaman_id', 'alat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alat_peminjaman');
    }
};
