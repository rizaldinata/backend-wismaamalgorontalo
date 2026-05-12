<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Kunci pengaturan, misal: featur_midtrans');
            $table->string('value')->nullable()->comment('Nilai pengaturan, misal: true atau false');
            $table->string('description')->nullable()->comment('Penjelasan singkat tentang kegunaan pengaturan');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
