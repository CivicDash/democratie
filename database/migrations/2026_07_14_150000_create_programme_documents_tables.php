<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel des programmes officiels structurés (plan §11.5 — cas pilote AEC/LFI).
 * Ce référentiel n'est PAS le comparateur : il alimente le bandeau « programme
 * complet — consulter », le rattachement des propositions et la curation des
 * mesures phares. Items = titres + liens d'ancre vers le texte officiel,
 * jamais le texte intégral (propriété intellectuelle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programme_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('candidat_id')->constrained('candidats_presidentielle')->cascadeOnDelete();

            $table->string('titre', 300);
            $table->string('version', 60)->nullable();
            $table->string('url', 500);
            $table->string('archive_url', 500)->nullable();
            $table->string('hash_contenu', 64)->nullable();   // détection d'évolutions (programme vivant)
            $table->jsonb('structure')->nullable();            // chapitres/sections (squelette)

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['candidat_id', 'url']);
        });

        Schema::create('programme_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('programme_documents')->cascadeOnDelete();

            $table->integer('chapitre_numero')->nullable();
            $table->string('chapitre_titre', 300)->nullable();
            $table->string('type', 30)->default('sous_page');  // chapitre | section | sous_page | mesure
            $table->string('numero', 20)->nullable();          // numéro de mesure (extraction fine, plus tard)
            $table->string('titre', 500);
            $table->string('texte_court', 300)->nullable();
            $table->string('url_ancre', 500)->nullable();
            $table->integer('ordre')->default(0);

            $table->timestamps();

            $table->index(['document_id', 'chapitre_numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_document_items');
        Schema::dropIfExists('programme_documents');
    }
};
