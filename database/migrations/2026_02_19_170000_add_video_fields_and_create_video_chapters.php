<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reunions_an', function (Blueprint $table) {
            $table->string('video_id')->nullable()->after('captation_video');
            $table->string('url_video')->nullable()->after('video_id');
            $table->index('video_id');
        });

        Schema::table('scrutins_an', function (Blueprint $table) {
            $table->string('seance_ref', 60)->nullable()->after('organe_ref');
            $table->index('seance_ref');
        });

        Schema::create('video_chapters', function (Blueprint $table) {
            $table->id();
            $table->string('video_id')->index();
            $table->string('reunion_uid')->nullable()->index();
            $table->string('chapter_nid')->index();
            $table->string('parent_nid')->nullable();
            $table->text('label');
            $table->unsignedSmallInteger('chapter_type_key')->nullable();
            $table->string('chapter_type_label')->nullable();
            $table->unsignedSmallInteger('theme_key')->nullable();
            $table->string('theme_label')->nullable();
            $table->string('speaker_vodalys_id')->nullable();
            $table->string('speaker_an_uid')->nullable()->index();
            $table->string('speaker_name')->nullable();
            $table->unsignedInteger('timecode_seconds')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['video_id', 'chapter_nid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_chapters');

        Schema::table('scrutins_an', function (Blueprint $table) {
            $table->dropIndex(['seance_ref']);
            $table->dropColumn('seance_ref');
        });

        Schema::table('reunions_an', function (Blueprint $table) {
            $table->dropIndex(['video_id']);
            $table->dropColumn(['video_id', 'url_video']);
        });
    }
};
