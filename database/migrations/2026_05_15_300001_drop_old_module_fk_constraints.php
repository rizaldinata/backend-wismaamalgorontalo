<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 10.12-10.14: Drop FK constraints from old Rental and Resident modules
return new class extends Migration
{
    public function up(): void
    {
        // Drop invoices.lease_id FK (was constrained to leases.id)
        if (Schema::hasTable('invoices') && $this->hasForeignKey('invoices', 'invoices_lease_id_foreign')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign('invoices_lease_id_foreign');
            });
        }

        // Drop guests.lease_id FK (was constrained to leases.id)
        if (Schema::hasTable('guests') && $this->hasForeignKey('guests', 'guests_lease_id_foreign')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->dropForeign('guests_lease_id_foreign');
            });
        }

        // Drop maintenance_requests.resident_id FK (was constrained to residents.id)
        if (Schema::hasTable('maintenance_requests') && $this->hasForeignKey('maintenance_requests', 'maintenance_requests_resident_id_foreign')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->dropForeign('maintenance_requests_resident_id_foreign');
            });
        }
    }

    public function down(): void
    {
        // FK constraints are intentionally not restored — Rental/Resident modules are permanently removed
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        $connection = config('database.default');
        $dbName     = config("database.connections.{$connection}.database");

        if (empty($dbName)) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $dbName)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
