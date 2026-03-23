<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enrichit la table maires pour la transition 2020-2026 → 2026-2032 :
 * lien prédécesseur, score électoral, statut réélection, liste gagnante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            $table->unsignedBigInteger('predecesseur_id')->nullable()->after('ville_id');
            $table->foreign('predecesseur_id')
                  ->references('id')->on('maires')->nullOnDelete();

            $table->decimal('score_election_pct', 5, 2)->nullable()->after('predecesseur_id');
            $table->tinyInteger('tour_election')->nullable()->after('score_election_pct');
            $table->boolean('reelu')->nullable()->after('tour_election');

            $table->foreignId('liste_id')->nullable()
                  ->constrained('listes_electorales')->nullOnDelete();

            $table->index('reelu');
        });
    }

    public function down(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            $table->dropForeign(['predecesseur_id']);
            $table->dropForeign(['liste_id']);
            $table->dropIndex(['reelu']);
            $table->dropColumn([
                'predecesseur_id', 'score_election_pct',
                'tour_election', 'reelu', 'liste_id',
            ]);
        });
    }
};
