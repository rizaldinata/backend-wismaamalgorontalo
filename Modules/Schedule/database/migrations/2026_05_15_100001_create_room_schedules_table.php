<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->string('type'); // sewa | maintenance | kebersihan | blokir
            $table->string('status')->default('pending'); // pending | active | finished | cancelled
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('created_by')->nullable(); // user_id pembuat jadwal

            // Data penghuni (hanya diisi untuk type = sewa)
            $table->string('tenant_name')->nullable();
            $table->string('tenant_id_number')->nullable();
            $table->string('tenant_phone')->nullable();
            $table->string('tenant_id_photo')->nullable();
            $table->unsignedBigInteger('tenant_user_id')->nullable(); // FK ke users (opsional)
            $table->decimal('agreed_price', 15, 2)->nullable();

            // Metadata
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_schedules');
    }
};
