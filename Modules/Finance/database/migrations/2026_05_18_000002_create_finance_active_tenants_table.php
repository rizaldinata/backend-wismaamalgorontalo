<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_active_tenants', function (Blueprint $table) {
            $table->id();
            // Referensi ke Core — disimpan sebagai plain ID, tanpa FK constraint
            $table->unsignedBigInteger('schedule_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            // Snapshot data agar Finance tidak perlu query tabel Core
            $table->string('room_number')->nullable();
            $table->string('tenant_name')->nullable();
            $table->string('tenant_phone')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_active_tenants');
    }
};
