<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // FK ke room_schedules (jalur baru); lease_id dipertahankan selama masa transisi
            $table->unsignedBigInteger('schedule_id')->nullable()->after('lease_id');
            // Buat lease_id nullable agar bisa diisi hanya schedule_id untuk sewa baru
            $table->unsignedBigInteger('lease_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('schedule_id');
            $table->unsignedBigInteger('lease_id')->nullable(false)->change();
        });
    }
};
