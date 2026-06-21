<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_toggles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('feature_toggles')->cascadeOnDelete();
            $table->string('key', 100)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('parent_id');
        });

        Schema::create('feature_toggle_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toggle_id')->constrained('feature_toggles')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->boolean('old_value');
            $table->boolean('new_value');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_toggle_logs');
        Schema::dropIfExists('feature_toggles');
    }
};
