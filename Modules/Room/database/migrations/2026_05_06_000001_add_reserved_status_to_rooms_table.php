<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available', 'reserved', 'occupied', 'maintenance') NOT NULL DEFAULT 'available'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Ubah kamar reserved kembali ke available sebelum rollback enum
            DB::statement("UPDATE rooms SET status = 'available' WHERE status = 'reserved'");
            DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available'");
        }
    }
};
