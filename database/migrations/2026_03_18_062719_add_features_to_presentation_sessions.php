<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_limit')->default(30)->after('status'); // saniye, 0 = sınırsız
            $table->boolean('answers_locked')->default(false)->after('time_limit');
        });
    }

    public function down(): void
    {
        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->dropColumn(['time_limit', 'answers_locked']);
        });
    }
};
