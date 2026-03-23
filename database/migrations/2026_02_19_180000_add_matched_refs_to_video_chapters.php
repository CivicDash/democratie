<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_chapters', function (Blueprint $table) {
            $table->string('question_uid')->nullable()->after('speaker_name');
            $table->index('question_uid');
        });
    }

    public function down(): void
    {
        Schema::table('video_chapters', function (Blueprint $table) {
            $table->dropIndex(['question_uid']);
            $table->dropColumn('question_uid');
        });
    }
};
