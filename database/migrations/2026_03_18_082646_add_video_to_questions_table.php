<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL enum'a 'video' değerini ekle
        DB::statement("ALTER TABLE questions MODIFY COLUMN media_type ENUM('none','image','youtube','video') NOT NULL DEFAULT 'none'");

        Schema::table('questions', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('video_path');
        });
        DB::statement("ALTER TABLE questions MODIFY COLUMN media_type ENUM('none','image','youtube') NOT NULL DEFAULT 'none'");
    }
};
