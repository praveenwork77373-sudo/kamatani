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
        // Drop old table if it exists with singular name
        Schema::dropIfExists('employee_status_history');
        
        Schema::create('employee_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['employed', 'on_leave', 'retired'])->default('employed');
            $table->date('effective_from');
            $table->date('effective_until')->nullable(); // null means current status
            $table->text('notes')->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Index for efficient queries
            $table->index(['employee_id', 'effective_from']);
            $table->index(['employee_id', 'effective_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_status_histories');
    }
};
