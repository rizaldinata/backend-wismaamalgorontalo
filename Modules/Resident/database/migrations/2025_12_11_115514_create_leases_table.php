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
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->onDelete('cascade');

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('price_per_month', 12, 2);
            $table->enum('status', ['active', 'finished', 'cancelled', 'pending', 'rejected']);

            $table->timestamps();

            $table->index('user_id', 'status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
