<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calendrier de campagne présidentielle.
 *
 * Distinct de `ingestion_documents`, qui décrit UNE SOURCE DÉPOUILLÉE : un événement à venir
 * n'a encore aucune source, et un débat réunit plusieurs candidats là où le document n'a
 * qu'une clé candidat. Les deux sémantiques ne tiennent pas dans la même table.
 *
 * Même discipline de publication que le reste du back-office présidentielle :
 * statut_validation + affiche_publiquement, rien n'est public sans validation humaine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements_campagne', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('election', 10)->default('2027');

            // Nature de l'ÉVÉNEMENT — à ne pas confondre avec ingestion_documents.type,
            // qui décrit la nature du SUPPORT (video, article, communique).
            $table->string('type', 30);
            $table->string('titre', 500);
            $table->text('description')->nullable();

            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->boolean('journee_entiere')->default(true);
            // Les meetings à venir sont souvent annoncés « mi-mars » : sans ce champ il
            // faudrait inventer un jour précis, donc mentir.
            $table->string('precision_date', 10)->default('jour'); // heure|jour|mois

            $table->string('lieu', 255)->nullable();
            $table->string('ville', 120)->nullable();
            $table->string('departement', 3)->nullable();

            $table->string('organisateur', 255)->nullable();
            $table->string('media', 120)->nullable();

            $table->string('url_source', 1000)->nullable();
            $table->string('url_video', 1000)->nullable();
            $table->string('archive_url', 1000)->nullable();

            // Rattachement OPTIONNEL à la source dépouillée : null pour un événement à venir.
            $table->foreignId('ingestion_document_id')->nullable()
                ->constrained('ingestion_documents')->nullOnDelete();

            // Deux statuts distincts : l'état réel de l'événement, et l'état de NOTRE
            // vérification. Un meeting peut être « annulé » et notre fiche « validée ».
            $table->string('statut', 20)->default('confirme');          // annonce|confirme|reporte|annule
            $table->string('statut_validation', 20)->default('detecte'); // detecte|a_completer|valide|rejete
            $table->boolean('affiche_publiquement')->default(false);
            $table->text('note_methodologique')->nullable();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->timestamps();

            $table->index(['election', 'date_debut']);
            $table->index(['affiche_publiquement', 'statut_validation']);
            $table->index('type');
            $table->index('ingestion_document_id');
        });

        // Un débat réunit plusieurs candidats : la relation ne peut pas être une clé simple.
        Schema::create('evenement_campagne_candidat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements_campagne')->cascadeOnDelete();
            $table->foreignId('candidat_id')->constrained('candidats_presidentielle')->cascadeOnDelete();
            $table->string('role', 20)->default('intervenant'); // intervenant|invite|absent
            $table->boolean('participation_confirmee')->default(true);
            $table->timestamps();

            $table->unique(['evenement_id', 'candidat_id']);
            $table->index('candidat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenement_campagne_candidat');
        Schema::dropIfExists('evenements_campagne');
    }
};
