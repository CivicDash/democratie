<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pipeline d'ingestion semi-automatique (plan §11).
 * Contrat JSON identique pour le pipeline auto et le mode fallback "Claude chat".
 * Tout entre en statut `detecte` (file de modération) — jamais publié directement.
 * La citation_verbatim est vérifiée mot-pour-mot contre la transcription source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingestion_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('candidat_id')->nullable()
                ->constrained('candidats_presidentielle')->nullOnDelete();

            $table->string('type', 20);   // video | audio | article | communique
            $table->string('titre', 500);
            $table->string('url', 1000)->nullable();
            $table->date('date_publication')->nullable();
            $table->integer('duree_s')->nullable();
            $table->string('transcription_path', 1000)->nullable();
            $table->text('transcription_note')->nullable();
            $table->string('archive_url', 1000)->nullable();
            $table->string('hash_contenu', 64)->nullable();

            $table->string('contrat_version', 20)->nullable();
            $table->string('generateur', 60)->nullable();   // claude-chat-fallback | pipeline-auto | ...
            $table->string('statut', 20)->default('extrait'); // collecte|transcrit|extrait|traite|erreur

            $table->timestamps();

            $table->index('candidat_id');
            $table->index('statut');
        });

        Schema::create('ingestion_propositions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('document_id')
                ->constrained('ingestion_documents')->cascadeOnDelete();

            // Références brutes issues du JSON + résolution
            $table->string('candidat_slug', 100)->nullable();
            $table->foreignId('candidat_id')->nullable()
                ->constrained('candidats_presidentielle')->nullOnDelete();
            $table->string('theme_slug', 60)->nullable();
            $table->foreignId('theme_id')->nullable()
                ->constrained('programme_themes')->nullOnDelete();

            $table->string('type', 20);   // mesure | position | revirement | declaration
            $table->text('resume_propose');
            $table->text('citation_verbatim');
            $table->string('timestamp_ou_paragraphe', 100)->nullable();
            $table->string('source_url', 1000)->nullable();
            $table->decimal('confiance', 3, 2)->nullable();

            $table->boolean('verbatim_verifie')->default(false);

            $table->string('statut', 20)->default('detecte'); // detecte|validee|rattachee|rejetee
            $table->foreignId('mesure_id')->nullable()
                ->constrained('programme_mesures')->nullOnDelete();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->jsonb('raw_llm_output')->nullable();

            $table->timestamps();

            $table->index('document_id');
            $table->index('statut');
            $table->index(['candidat_id', 'theme_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingestion_propositions');
        Schema::dropIfExists('ingestion_documents');
    }
};
