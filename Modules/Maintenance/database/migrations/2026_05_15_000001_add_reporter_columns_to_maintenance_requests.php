<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK only if it still exists (may have been removed from the original migration)
        if ($this->hasForeignKey('maintenance_requests', 'maintenance_requests_resident_id_foreign')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->dropForeign('maintenance_requests_resident_id_foreign');
            });
        }

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('resident_id')->nullable()->change();

            // Kolom baru: simpan data reporter tanpa FK ke modul Resident
            $table->unsignedBigInteger('reporter_user_id')->nullable()->after('resident_id');
            $table->string('reporter_name')->nullable()->after('reporter_user_id');
            $table->string('reporter_phone')->nullable()->after('reporter_name');

            $table->index('reporter_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex(['reporter_user_id']);
            $table->dropColumn(['reporter_user_id', 'reporter_name', 'reporter_phone']);

            $table->unsignedBigInteger('resident_id')->nullable(false)->change();
            $table->foreign('resident_id')->references('id')->on('residents')->cascadeOnDelete();
        });
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        $connection = config('database.default');
        $dbName     = config("database.connections.{$connection}.database");

        if (empty($dbName)) {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $dbName)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
