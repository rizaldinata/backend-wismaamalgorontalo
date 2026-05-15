<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_active_contexts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lease_id')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->unsignedBigInteger('room_id');
            $table->decimal('room_price', 12, 2)->default(0);
            $table->string('tenant_name');
            $table->string('tenant_email')->nullable();
            $table->string('tenant_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('lease_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_active_contexts');
    }
};
