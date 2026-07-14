<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réseaux sociaux complémentaires des personnalités politiques
 * (twitter/facebook/linkedin/instagram/site_web existent déjà).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnes_politiques', function (Blueprint $table) {
            $table->string('mastodon_url', 500)->nullable()->after('instagram_url');
            $table->string('bluesky_url', 500)->nullable()->after('mastodon_url');
            $table->string('youtube_url', 500)->nullable()->after('bluesky_url');
        });
    }

    public function down(): void
    {
        Schema::table('personnes_politiques', function (Blueprint $table) {
            $table->dropColumn(['mastodon_url', 'bluesky_url', 'youtube_url']);
        });
    }
};
