<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel transverse des thèmes de programme (clé du comparateur présidentielle).
 * Grille NEUTRE : ne dérive jamais d'un programme de candidat (cf. plan §4).
 * Sources autorisées : nomenclatures institutionnelles (LOLF, COFOG, périmètres
 * ministériels, sections CESE), documentées dans `sources_taxonomie`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programme_themes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nom', 120);
            $table->string('icone', 60)->nullable();
            $table->text('description')->nullable();
            $table->text('sources_taxonomie')->nullable();
            $table->integer('ordre')->default(0);
            $table->boolean('actif')->default(true)->index();
            $table->timestamps();

            $table->index('ordre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_themes');
    }
};
