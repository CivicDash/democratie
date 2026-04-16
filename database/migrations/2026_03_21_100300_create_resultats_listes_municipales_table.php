<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Résultats par liste pour chaque commune/tour.
 * Voix, pourcentages, sièges obtenus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultats_listes_municipales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resultat_commune_id')
                ->constrained('resultats_municipaux')->cascadeOnDelete();
            $table->foreignId('liste_id')->nullable()
                ->constrained('listes_electorales')->nullOnDelete();

            $table->integer('numero_panneau')->nullable();
            $table->string('nom_liste')->nullable();
            $table->string('nuance_politique', 10)->nullable();
            $table->string('tete_de_liste_nom')->nullable();
            $table->string('tete_de_liste_prenom')->nullable();

            $table->integer('voix');
            $table->decimal('pourcentage_exprimes', 5, 2);
            $table->decimal('pourcentage_inscrits', 5, 2)->nullable();
            $table->boolean('elu')->default(false);
            $table->integer('sieges_obtenus')->nullable();
            $table->integer('sieges_conseil_communautaire')->nullable();

            $table->timestamps();

            $table->index(['resultat_commune_id', 'voix']);
            $table->index('nuance_politique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultats_listes_municipales');
    }
};
