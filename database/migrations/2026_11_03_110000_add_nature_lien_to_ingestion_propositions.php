<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nature du lien d'une proposition à la mesure qu'elle vise : redite (même engagement, même
 * chiffre), chiffre modifié, précision ou revirement. Les trois dernières forment l'évolution
 * de la mesure, affichée datée sous elle ; un revirement ne compte pas comme « dite ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingestion_propositions', function (Blueprint $table) {
            $table->string('nature_lien', 20)->nullable()->after('redite_de_mesure_id');
        });
    }

    public function down(): void
    {
        Schema::table('ingestion_propositions', function (Blueprint $table) {
            $table->dropColumn('nature_lien');
        });
    }
};
