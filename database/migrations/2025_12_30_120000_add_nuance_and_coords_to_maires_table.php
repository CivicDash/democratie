<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les champs enrichis depuis data.gouv.fr :
     * - nuance_politique : étiquette politique (LDVG, LLR, etc.)
     * - mandature : période du mandat (2020-2026)
     * - coordonnées GPS de la mairie
     */
    public function up(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            // Ajout conditionnel pour éviter les erreurs si colonne existe déjà
            if (!Schema::hasColumn('maires', 'nuance_politique')) {
                $table->string('nuance_politique', 10)->nullable()->index()->after('population_commune');
            }
            if (!Schema::hasColumn('maires', 'mandature')) {
                $table->string('mandature', 20)->nullable()->after('nuance_politique');
            }
            if (!Schema::hasColumn('maires', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('mandature');
            }
            if (!Schema::hasColumn('maires', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            $table->dropColumn(['nuance_politique', 'mandature', 'latitude', 'longitude']);
        });
    }
};
