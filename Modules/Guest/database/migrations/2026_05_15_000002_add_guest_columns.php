<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('lease_id');
            $table->unsignedBigInteger('schedule_reference_id')->nullable()->after('user_id');
            $table->string('tenant_name')->nullable()->after('schedule_reference_id');
            $table->string('tenant_email')->nullable()->after('tenant_name');
            $table->string('tenant_phone')->nullable()->after('tenant_email');

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'schedule_reference_id', 'tenant_name', 'tenant_email', 'tenant_phone']);
        });
    }
};
