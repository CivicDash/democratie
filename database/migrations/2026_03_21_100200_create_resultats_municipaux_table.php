<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Résultats électoraux par commune et par tour.
 * Stocke la participation et les méta-données du scrutin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultats_municipaux', function (Blueprint $table) {
            $table->id();

            $table->string('code_commune', 5)->index();
            $table->string('nom_commune');
            $table->string('code_departement', 3)->index();

            $table->tinyInteger('tour');

            $table->integer('inscrits');
            $table->integer('abstentions');
            $table->decimal('taux_abstention', 5, 2);
            $table->integer('votants');
            $table->decimal('taux_participation', 5, 2);
            $table->integer('blancs')->default(0);
            $table->integer('nuls')->default(0);
            $table->integer('exprimes');

            $table->integer('nb_sieges_a_pourvoir')->nullable();
            $table->integer('nb_sieges_pourvus')->nullable();
            $table->integer('nb_listes')->nullable();

            $table->string('statut_commune', 20)->nullable();

            $table->foreignId('ville_id')->nullable()
                  ->constrained('villes')->nullOnDelete();

            $table->timestamps();

            $table->unique(['code_commune', 'tour']);
            $table->index(['code_departement', 'tour']);
            $table->index('statut_commune');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultats_municipaux');
    }
};
