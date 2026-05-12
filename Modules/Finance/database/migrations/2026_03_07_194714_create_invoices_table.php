<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lease_id')->constrained('leases')->cascadeOnDelete();

            $table->string('invoice_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('unpaid')->comment('unpaid, paid, cancelled');
            $table->date('due_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
