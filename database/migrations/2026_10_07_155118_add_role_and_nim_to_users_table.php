<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nim', 20)->nullable()->unique()->after('email');
            $table->string('no_hp', 20)->nullable()->after('nim');
            $table->enum('role', ['admin', 'mahasiswa'])->default('mahasiswa')->after('no_hp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nim', 'no_hp', 'role']);
        });
    }
};
