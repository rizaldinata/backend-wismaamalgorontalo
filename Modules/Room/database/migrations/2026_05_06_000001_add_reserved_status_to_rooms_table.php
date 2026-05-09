<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available', 'reserved', 'occupied', 'maintenance') NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        // Ubah kamar reserved kembali ke available sebelum rollback enum
        DB::statement("UPDATE rooms SET status = 'available' WHERE status = 'reserved'");
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available'");
    }
};
