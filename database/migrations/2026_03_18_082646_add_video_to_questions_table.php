<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // video_path sütunu henüz yoksa ekle
            if (!Schema::hasColumn('questions', 'video_path')) {
                $table->string('video_path')->nullable()->after('image_path');
            }
        });

        // PostgreSQL: media_type CHECK constraint'e 'video' ekle
        // (MySQL'de MODIFY COLUMN gerekirdi, PostgreSQL'de constraint güncellenir)
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE questions MODIFY COLUMN media_type ENUM('none','image','youtube','video') NOT NULL DEFAULT 'none'");
            } elseif ($driver === 'pgsql') {
                // Mevcut CHECK constraint'i kaldır ve yenisini ekle
                DB::statement("ALTER TABLE questions DROP CONSTRAINT IF EXISTS questions_media_type_check");
                DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_media_type_check CHECK (media_type IN ('none','image','youtube','video'))");
            }
        } catch (\Exception $e) {
            // Constraint zaten güncel ise hata yoksay
        }
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'video_path')) {
                $table->dropColumn('video_path');
            }
        });

        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE questions MODIFY COLUMN media_type ENUM('none','image','youtube') NOT NULL DEFAULT 'none'");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE questions DROP CONSTRAINT IF EXISTS questions_media_type_check");
                DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_media_type_check CHECK (media_type IN ('none','image','youtube'))");
            }
        } catch (\Exception $e) {
            // Yoksay
        }
    }
};
