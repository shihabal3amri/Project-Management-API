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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index(); // Index for title filtering
            $table->text('description');
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->enum('status', ['Active', 'Deferred', 'Completed'])->index(); 
            $table->foreignId('user_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
