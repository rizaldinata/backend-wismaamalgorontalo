<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_schedule_updates', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('maintenance_schedule_id')->constrained('maintenance_schedules')->cascadeOnDelete();
            $blueprint->foreignId('user_id')->constrained('users');
            $blueprint->string('status')->nullable(); // New status if updated
            $blueprint->text('notes');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedule_updates');
    }
};
