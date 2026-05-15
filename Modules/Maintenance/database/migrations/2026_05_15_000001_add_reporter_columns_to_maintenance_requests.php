<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            // Hapus FK constraint agar resident_id bisa nullable (FK dihapus di Fase 10)
            $table->dropForeign(['resident_id']);
            $table->unsignedBigInteger('resident_id')->nullable()->change();

            // Kolom baru: simpan data reporter tanpa FK ke modul Resident
            $table->unsignedBigInteger('reporter_user_id')->nullable()->after('resident_id');
            $table->string('reporter_name')->nullable()->after('reporter_user_id');
            $table->string('reporter_phone')->nullable()->after('reporter_name');

            $table->index('reporter_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex(['reporter_user_id']);
            $table->dropColumn(['reporter_user_id', 'reporter_name', 'reporter_phone']);

            $table->unsignedBigInteger('resident_id')->nullable(false)->change();
            $table->foreign('resident_id')->references('id')->on('residents')->cascadeOnDelete();
        });
    }
};
