<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('technician_name');
            $table->string('location');
            $table->enum('type', ['pembersihan', 'perawatan']);
            $table->enum('subtype', ['rutin', 'deep_cleaning', 'darurat', 'perbaikan', 'maintenance']);
            $table->enum('status', ['in_progress', 'done', 'cancelled'])->default('in_progress');
            $table->string('notes')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
