<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fiabilisation du rattachement HATVP pour la présidentielle.
 *
 *  - `hatvp_declarations.personne_politique_id` : lien EXPLICITE et déterministe vers une
 *    personne (aujourd'hui le rattachement se fait par un matching ILIKE nom/prénom, fragile).
 *    Rempli via l'écran de rattachement du back-office (validation humaine).
 *  - `candidats_presidentielle.hatvp_statut` : état honnête pour un affichage symétrique
 *    (a_verifier | lie | non_soumis | non_disponible).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatvp_declarations', function (Blueprint $table) {
            $table->foreignId('personne_politique_id')->nullable()->after('uuid')
                ->constrained('personnes_politiques')->nullOnDelete();
            $table->index('personne_politique_id');
        });

        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->string('hatvp_statut', 20)->default('a_verifier')->after('affiche_publiquement');
        });
    }

    public function down(): void
    {
        Schema::table('hatvp_declarations', function (Blueprint $table) {
            $table->dropForeign(['personne_politique_id']);
            $table->dropColumn('personne_politique_id');
        });

        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->dropColumn('hatvp_statut');
        });
    }
};
