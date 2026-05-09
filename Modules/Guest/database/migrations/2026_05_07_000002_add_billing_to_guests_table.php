<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->unsignedInteger('total_days')->default(0)->after('relationship');
            $table->unsignedInteger('billable_days')->default(0)->after('total_days');
            $table->decimal('charge_amount', 12, 2)->default(0)->after('billable_days');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['total_days', 'billable_days', 'charge_amount']);
        });
    }
};
