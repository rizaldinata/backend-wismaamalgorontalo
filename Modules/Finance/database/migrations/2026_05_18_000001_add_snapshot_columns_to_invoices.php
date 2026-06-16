<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Snapshot data dari event JadwalDibuat — Finance tidak perlu query Schedule lagi
            $table->unsignedBigInteger('tenant_user_id')->nullable()->after('schedule_id');
            $table->string('tenant_name')->nullable()->after('tenant_user_id');
            $table->string('tenant_phone')->nullable()->after('tenant_name');
            $table->string('room_number')->nullable()->after('tenant_phone');
            $table->date('period_start')->nullable()->after('room_number');
            $table->date('period_end')->nullable()->after('period_start');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tenant_user_id', 'tenant_name', 'tenant_phone', 'room_number', 'period_start', 'period_end']);
        });
    }
};
