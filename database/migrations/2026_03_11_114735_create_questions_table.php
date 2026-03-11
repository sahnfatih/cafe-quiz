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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->text('text');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c')->nullable();
            $table->string('option_d')->nullable();
            $table->enum('correct_option', ['A', 'B', 'C', 'D']);
            $table->unsignedInteger('points')->default(100);
            $table->enum('media_type', ['none', 'image', 'youtube'])->default('none');
            $table->string('image_path')->nullable();
            $table->string('youtube_url')->nullable();
            $table->unsignedInteger('youtube_start')->nullable(); // saniye
            $table->unsignedInteger('youtube_end')->nullable();   // saniye
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
