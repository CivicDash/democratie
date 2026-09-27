<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Précision des dates du parcours : jour, mois ou année.
 *
 * La page candidat affichait la date complète : une période déclarée à la HATVP au mois
 * près (« 06/2022 ») paraissait commencer un 1er juin, et une fonction connue à l'année
 * ne pouvait pas être versée sans inventer un 1er janvier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcours_evenements', function (Blueprint $table) {
            $table->string('precision_debut', 5)->default('jour')->after('date_fin');
            $table->string('precision_fin', 5)->default('jour')->after('precision_debut');
        });

        // Les déclarations HATVP sont au mois près. Une ligne dont un modérateur a repris
        // les dates au jour près garde « jour » : sa correction le dit (dates_au_jour).
        DB::table('parcours_evenements')
            ->where('source_detection', 'hatvp')
            ->whereRaw("coalesce(detection_raw_data->>'dates_au_jour', '') <> 'oui'")
            ->update(['precision_debut' => 'mois', 'precision_fin' => 'mois']);
    }

    public function down(): void
    {
        Schema::table('parcours_evenements', function (Blueprint $table) {
            $table->dropColumn(['precision_debut', 'precision_fin']);
        });
    }
};
