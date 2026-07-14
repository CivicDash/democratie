<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Argumentaire et contre-argumentaire factuels par mesure + leurs sources.
 * Règle éditoriale (contrôlée à la publication) : chaque mesure publiée doit avoir
 * >= 1 argument "pour" ET >= 1 "contre" validés, chacun avec une source haute/moyenne.
 * Double validation prévue pour les arguments "contre" (double_valide_par).
 * `argument_sources` reprend le pattern de `affaires_sources`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arguments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('mesure_id')
                ->constrained('programme_mesures')->cascadeOnDelete();

            $table->string('sens', 10);            // pour | contre
            $table->string('titre', 255);
            $table->string('contenu', 500);        // factuel, pas d'adjectifs militants
            $table->string('type_argument', 40);   // chiffrage | precedent_historique | avis_institution | etude | comparaison_internationale | faisabilite_juridique

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();
            $table->integer('ordre')->default(0);

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            // Double validation (obligatoire pour les arguments "contre")
            $table->foreignId('double_valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('double_valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['mesure_id', 'sens']);
            $table->index('statut_validation');
            $table->index(['affiche_publiquement', 'statut_validation']);
        });

        Schema::create('argument_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('argument_id')->constrained('arguments')->cascadeOnDelete();

            $table->string('type_source', 30);     // rapport_officiel | etude_academique | insee | cour_des_comptes | conseil_constitutionnel | ocde_eurostat | presse_nationale | fact_checking
            $table->string('titre', 500)->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('media', 200)->nullable();
            $table->date('date_publication')->nullable();
            $table->string('auteur', 200)->nullable();
            $table->text('extrait')->nullable();
            $table->string('archive_url', 1000)->nullable();   // archive.org obligatoire à la publication

            $table->string('fiabilite', 20)->default('moyenne'); // haute | moyenne | basse

            $table->foreignId('verifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verifie_at')->nullable();
            $table->text('commentaire_verification')->nullable();

            $table->timestamps();

            $table->index('argument_id');
            $table->index('type_source');
            $table->index('fiabilite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('argument_sources');
        Schema::dropIfExists('arguments');
    }
};
