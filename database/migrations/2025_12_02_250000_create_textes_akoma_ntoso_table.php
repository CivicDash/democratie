<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('textes_akoma_ntoso', function (Blueprint $table) {
            $table->string('uid', 50)->primary(); // ex: ppl25-171

            // Type et numéro
            $table->string('type', 10); // ppl, pjl, ppr, pjr
            $table->string('annee', 10)->nullable(); // 25 (2025)
            $table->integer('numero')->nullable(); // 171
            $table->string('session', 20)->nullable(); // 2025-2026

            // Métadonnées
            $table->string('titre')->nullable();
            $table->string('titre_court')->nullable();
            $table->string('url_senat')->nullable();
            $table->string('url_dossier')->nullable();
            $table->string('signet_dossier', 50)->nullable();

            // Auteur(s)
            $table->string('auteur_id', 100)->nullable(); // ID auteur (peut être multiple)
            $table->text('auteur_nom')->nullable();
            $table->string('commission', 100)->nullable();

            // Dates
            $table->date('date_depot')->nullable();
            $table->date('date_presentation')->nullable();
            $table->date('date_adoption')->nullable();
            $table->date('date_publication_xml')->nullable();

            // Workflow
            $table->string('etape_actuelle', 50)->nullable(); // lecture_1, lecture_2, cmp, etc.
            $table->string('statut', 50)->nullable(); // déposé, adopté, etc.

            // Contenu
            $table->text('preambule')->nullable();
            $table->text('corps_texte')->nullable(); // Texte complet du body
            $table->integer('nb_articles')->default(0);
            $table->integer('nb_titres')->default(0);

            // Source
            $table->string('source_url')->nullable();
            $table->timestamp('last_modified')->nullable();

            $table->timestamps();

            // Index
            $table->index('type');
            $table->index('date_depot');
            $table->index('date_adoption');
            $table->index(['type', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textes_akoma_ntoso');
    }
};
