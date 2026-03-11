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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('presentation_session_id')->constrained()->cascadeOnDelete();
            $table->enum('selected_option', ['A', 'B', 'C', 'D']);
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('base_points')->default(0);
            $table->unsignedInteger('speed_bonus_points')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
