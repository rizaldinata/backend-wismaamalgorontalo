<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->string('number')->unique();
            $table->string('type');
            $table->string('price', 12, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');


            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
