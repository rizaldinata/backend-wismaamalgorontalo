<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_user_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('tenant_name');
            $table->string('tenant_phone')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->string('status')->default('unpaid')->comment('unpaid, paid, waived, cancelled');
            $table->text('waive_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
