<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_expense_entries', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 10);      // listrik | air | wifi
            $table->tinyInteger('bulan')->unsigned(); // 1–12
            $table->smallInteger('tahun')->unsigned();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable(); // soft ref ke users.id

            $table->timestamps();

            $table->unique(['jenis', 'bulan', 'tahun'], 'unique_jenis_periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_expense_entries');
    }
};
