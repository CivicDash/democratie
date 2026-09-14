<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque validation d'affaire faisait `$affaire->sources()->delete()` avant de
 * recréer les sources reçues du formulaire. Sans SoftDeletes et sans journal, le
 * sourçage d'une fiche judiciaire nominative — publiée, nommant une personne et une
 * infraction — disparaissait définitivement, avec la trace de qui l'avait vérifié.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affaires_sources', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('affaires_sources', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
