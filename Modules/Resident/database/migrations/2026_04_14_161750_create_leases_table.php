<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The leases table is created by Modules/Rental migration.
        // Keep this migration idempotent to avoid duplicate table creation.
        if (Schema::hasTable('leases')) {
            return;
        }
    }

    public function down(): void
    {
        // No-op: this migration does not own the leases table lifecycle.
    }
};