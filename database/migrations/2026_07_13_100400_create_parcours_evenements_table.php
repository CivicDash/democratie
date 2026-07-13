<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frise de parcours d'une personnalité : mandats, fonctions gouvernementales,
 * postes privés, engagements (luttes/causes/votes marquants).
 * Alimentation Wikidata (P39) / HATVP puis validation humaine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcours_evenements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('personne_politique_id')
                ->constrained('personnes_politiques')->cascadeOnDelete();

            $table->string('type', 30);   // mandat | fonction_gouvernementale | poste_prive | engagement
            $table->string('titre', 255);
            $table->string('organisation', 255)->nullable();
            $table->text('description')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('en_cours')->default(false);

            $table->string('source_url', 500)->nullable();

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();
            $table->integer('ordre')->default(0);

            $table->string('source_detection', 30)->nullable();
            $table->decimal('detection_confidence', 3, 2)->nullable();
            $table->jsonb('detection_raw_data')->nullable();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['personne_politique_id', 'type']);
            $table->index('statut_validation');
            $table->index(['date_debut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcours_evenements');
    }
};
