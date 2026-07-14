<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date de la dernière revue judiciaire du candidat (revue presse systématique).
 * Alimente l'état vide daté du volet affaires : « aucune affaire recensée —
 * dernière vérification : JJ/MM/AAAA » (une absence vérifiée est une information).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->date('revue_judiciaire_at')->nullable()->after('hero_licence');
        });
    }

    public function down(): void
    {
        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->dropColumn('revue_judiciaire_at');
        });
    }
};
