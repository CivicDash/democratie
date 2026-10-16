<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Qui parle ? Une proposition est attribuée à son locuteur réel.
 *
 *  - `locuteur` nul : le candidat lui-même ;
 *  - `qualite_locuteur = equipe_campagne` : une personnalité qui parle pour le projet
 *    présidentiel aux côtés du candidat (Éric Coquerel à la conférence de Jean-Luc
 *    Mélenchon). Elle est du côté du candidat : ses propositions peuvent devenir des
 *    mesures, et son nom reste affiché partout ;
 *  - `qualite_locuteur = parti` : la vision d'un parti, qui peut différer de celle du
 *    candidat. Jamais une mesure du candidat : seulement « Autour du candidat ».
 *
 * `affiche_publiquement` ne régit que la section « Autour du candidat » : une proposition
 * du candidat ou de son équipe paraît dès qu'elle est validée ou rattachée.
 *
 * Aucune donnée existante n'a de locuteur (vérifié en production le 16/10/2026) : les
 * contraintes sont créées validées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingestion_propositions', function (Blueprint $t) {
            $t->string('locuteur', 150)->nullable()->after('candidat_id');
            $t->string('role_locuteur', 200)->nullable()->after('locuteur');
            $t->string('qualite_locuteur', 20)->nullable()->after('role_locuteur');
            $t->boolean('affiche_publiquement')->default(false)->after('statut');
            $t->index(['candidat_id', 'qualite_locuteur']);
        });

        DB::statement("ALTER TABLE ingestion_propositions ADD CONSTRAINT ingestion_propositions_qualite_locuteur_connue
            CHECK (qualite_locuteur IS NULL OR qualite_locuteur IN ('equipe_campagne', 'parti'))");

        // Un nom sans qualité ne dit pas de quel côté il parle ; une qualité sans nom n'attribue rien.
        DB::statement('ALTER TABLE ingestion_propositions ADD CONSTRAINT ingestion_propositions_locuteur_qualifie
            CHECK ((locuteur IS NULL) = (qualite_locuteur IS NULL))');

        // Invariant : la parole d'un parti ne devient jamais une mesure du candidat.
        DB::statement("ALTER TABLE ingestion_propositions ADD CONSTRAINT ingestion_propositions_parti_sans_mesure
            CHECK (qualite_locuteur IS DISTINCT FROM 'parti' OR mesure_id IS NULL)");
    }

    public function down(): void
    {
        foreach (['parti_sans_mesure', 'locuteur_qualifie', 'qualite_locuteur_connue'] as $c) {
            DB::statement("ALTER TABLE ingestion_propositions DROP CONSTRAINT IF EXISTS ingestion_propositions_{$c}");
        }

        Schema::table('ingestion_propositions', function (Blueprint $t) {
            $t->dropIndex(['candidat_id', 'qualite_locuteur']);
            $t->dropColumn(['locuteur', 'role_locuteur', 'qualite_locuteur', 'affiche_publiquement']);
        });
    }
};
