<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_expense_entries', function (Blueprint $table) {
            $table->boolean('is_filled')->default(false)->after('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_expense_entries', function (Blueprint $table) {
            $table->dropColumn('is_filled');
        });
    }
};
