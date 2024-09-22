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
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->integer('recurrence_interval')->nullable(); // e.g., repeat every X days/weeks
            $table->json('recurrence_day_of_week')->nullable(); // Store days of the week as JSON (e.g., ["Monday", "Wednesday"])
            $table->integer('recurrence_day_of_month')->nullable(); // Store specific day of the month (e.g., 15)
            $table->date('recurrence_end_date')->nullable()->index(); // Optional end date for the recurrence
        });
    }
    
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'is_recurring',
                'recurrence_type',
                'recurrence_interval',
                'recurrence_day_of_week',
                'recurrence_day_of_month',
                'recurrence_end_date'
            ]);
        });
    }
    
};
