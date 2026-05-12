<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->string('rental_type')->default('monthly')->comment('bulanan atau harian');
            $table->string('status')->default('pending')->comment('pending, active, expired, cancelled');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
