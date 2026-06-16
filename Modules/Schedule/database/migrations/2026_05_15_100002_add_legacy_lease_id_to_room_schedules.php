<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_schedules', function (Blueprint $table) {
            // Menyimpan lease.id asal untuk keperluan tracking dan rollback
            $table->unsignedBigInteger('legacy_lease_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('room_schedules', function (Blueprint $table) {
            $table->dropColumn('legacy_lease_id');
        });
    }
};
