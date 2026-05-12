<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('system_alert')->comment('payment_receipt, payment_reminder, manual_broadcast');
            $table->string('target_phone', 20);
            $table->text('message_body');
            $table->string('status')->default('failed')->comment('sent, failed');
            $table->text('error_response')->nullable()->comment('Menyimpan pesan error jika gagal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
