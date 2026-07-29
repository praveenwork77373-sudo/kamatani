<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('database_backup_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->string('time', 5)->default('02:00'); // HH:MM
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=Sunday … 6=Saturday
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1–28
            $table->unsignedInteger('retain_days')->default(30);
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backup_settings');
    }
};
