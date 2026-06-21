<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fine_invoice', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fine_id');
            $table->unsignedBigInteger('invoice_id');
            $table->timestamps();

            $table->foreign('fine_id')->references('id')->on('fines')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fine_invoice');
    }
};
