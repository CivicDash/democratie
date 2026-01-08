<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            if (!Schema::hasColumn('maires', 'wikipedia_url')) {
                $table->string('wikipedia_url', 500)->nullable()->after('photo_wikipedia_url');
            }
            if (!Schema::hasColumn('maires', 'wikidata_id')) {
                $table->string('wikidata_id', 50)->nullable()->after('wikipedia_url');
            }
            if (!Schema::hasColumn('maires', 'wikipedia_extract')) {
                $table->text('wikipedia_extract')->nullable()->after('wikidata_id');
            }
            if (!Schema::hasColumn('maires', 'lieu_naissance')) {
                $table->string('lieu_naissance', 255)->nullable()->after('date_naissance');
            }
            if (!Schema::hasColumn('maires', 'formation')) {
                $table->string('formation', 500)->nullable()->after('profession');
            }
            if (!Schema::hasColumn('maires', 'mandats_precedents')) {
                $table->json('mandats_precedents')->nullable()->after('formation');
            }
            if (!Schema::hasColumn('maires', 'wikipedia_last_sync')) {
                $table->timestamp('wikipedia_last_sync')->nullable()->after('wikipedia_extract');
            }
        });

        // Index pour optimiser les requêtes de sync
        Schema::table('maires', function (Blueprint $table) {
            $table->index('wikipedia_last_sync');
            $table->index('population_commune');
        });
    }

    public function down(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            $table->dropIndex(['wikipedia_last_sync']);
            $table->dropIndex(['population_commune']);
            $table->dropColumn([
                'wikipedia_url',
                'wikidata_id', 
                'wikipedia_extract',
                'lieu_naissance',
                'formation',
                'mandats_precedents',
                'wikipedia_last_sync',
            ]);
        });
    }
};
