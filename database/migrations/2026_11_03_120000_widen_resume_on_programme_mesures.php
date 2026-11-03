<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le résumé d'une mesure porte le texte complet de la proposition dont elle est issue. En
 * varchar(300), il était coupé à 295 caractères au milieu d'un mot, et le titre avec lui :
 * 62 mesures en production le 03/11/2026, jusqu'à 478 caractères. Le titre reste court
 * (300), coupé au dernier mot entier suivi de « … » (ProgrammeMesure::titreDepuis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programme_mesures', function (Blueprint $table) {
            $table->text('resume')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Un retour en varchar(300) échouerait sur les résumés restaurés : on ne coupe pas
        // un texte en silence, on refuse.
        Schema::table('programme_mesures', function (Blueprint $table) {
            $table->string('resume', 300)->nullable()->change();
        });
    }
};
