<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('payment_method')->default('manual');
            $table->string('payment_proof_path')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable()->comment('Alasan jika pembayaran ditolak');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
