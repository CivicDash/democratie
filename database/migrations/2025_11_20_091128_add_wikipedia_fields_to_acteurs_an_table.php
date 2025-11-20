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
        Schema::table('acteurs_an', function (Blueprint $table) {
            $table->string('wikipedia_url', 500)->nullable()->after('url_hatvp');
            $table->string('photo_wikipedia_url', 500)->nullable()->after('wikipedia_url');
            $table->text('wikipedia_extract')->nullable()->after('photo_wikipedia_url');
            $table->timestamp('wikipedia_last_sync')->nullable()->after('wikipedia_extract');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acteurs_an', function (Blueprint $table) {
            $table->dropColumn([
                'wikipedia_url',
                'photo_wikipedia_url',
                'wikipedia_extract',
                'wikipedia_last_sync',
            ]);
        });
    }
};
