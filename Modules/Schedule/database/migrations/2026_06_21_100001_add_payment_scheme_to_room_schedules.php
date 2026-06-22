<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_schedules', function (Blueprint $table) {
            $table->string('payment_scheme')->default('full')->after('agreed_price');
            $table->decimal('dp_amount', 15, 2)->nullable()->after('payment_scheme');
            $table->timestamp('dp_paid_at')->nullable()->after('dp_amount');
        });
    }

    public function down(): void
    {
        Schema::table('room_schedules', function (Blueprint $table) {
            $table->dropColumn(['payment_scheme', 'dp_amount', 'dp_paid_at']);
        });
    }
};
