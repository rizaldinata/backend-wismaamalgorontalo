<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->string('bill_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('unpaid');
            $table->string('payment_proof_path')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('snap_token')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_bills');
    }
};
