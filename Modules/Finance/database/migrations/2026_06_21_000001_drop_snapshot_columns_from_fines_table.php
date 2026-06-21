<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->dropColumn(['tenant_name', 'tenant_phone']);
        });
    }

    public function down(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->string('tenant_name')->after('schedule_id');
            $table->string('tenant_phone')->nullable()->after('tenant_name');
        });
    }
};
