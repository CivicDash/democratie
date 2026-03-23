<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affaires_judiciaires', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('personne_politique_id')->nullable()
                  ->constrained('personnes_politiques')->nullOnDelete();
            $table->string('acteur_an_uid', 20)->nullable()->index();
            $table->string('senateur_matricule', 20)->nullable()->index();

            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('parti_politique', 100)->nullable();
            $table->string('fonction_au_moment', 255)->nullable();

            $table->string('titre', 500);
            $table->text('description')->nullable();
            $table->string('type_affaire', 50);
            $table->string('categorie', 30);

            $table->date('date_faits')->nullable();
            $table->date('date_mise_en_examen')->nullable();
            $table->date('date_jugement_premiere_instance')->nullable();
            $table->date('date_jugement_appel')->nullable();
            $table->date('date_jugement_cassation')->nullable();
            $table->date('date_condamnation_definitive')->nullable();

            $table->string('statut_judiciaire', 40)->default('en_cours');
            $table->integer('peine_prison_mois')->nullable();
            $table->boolean('peine_prison_avec_sursis')->default(false);
            $table->decimal('peine_amende_euros', 12, 2)->nullable();
            $table->integer('peine_ineligibilite_mois')->nullable();
            $table->text('peine_complementaire')->nullable();

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();

            $table->string('source_detection', 30)->nullable();
            $table->timestamp('detecte_at')->nullable();
            $table->decimal('detection_confidence', 3, 2)->nullable();
            $table->jsonb('detection_raw_data')->nullable();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->string('juridiction', 255)->nullable();
            $table->string('numero_dossier', 100)->nullable();
            $table->string('lien_decision_justice', 500)->nullable();

            $table->integer('ordre_affichage')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('personne_politique_id');
            $table->index('statut_validation');
            $table->index('statut_judiciaire');
            $table->index('type_affaire');
            $table->index('categorie');
            $table->index('parti_politique');
            $table->index(['affiche_publiquement', 'statut_validation']);
        });

        Schema::create('affaires_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->constrained('affaires_judiciaires')->cascadeOnDelete();

            $table->string('type_source', 30);
            $table->string('titre', 500)->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('media', 200)->nullable();
            $table->date('date_publication')->nullable();
            $table->string('auteur', 200)->nullable();
            $table->text('extrait')->nullable();
            $table->string('archive_url', 1000)->nullable();

            $table->string('fiabilite', 20)->default('moyenne');

            $table->foreignId('verifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verifie_at')->nullable();
            $table->text('commentaire_verification')->nullable();

            $table->timestamps();

            $table->index(['affaire_id', 'fiabilite']);
            $table->index('type_source');
        });

        Schema::create('affaires_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->constrained('affaires_judiciaires')->cascadeOnDelete();

            $table->string('action', 30);
            $table->string('ancien_statut', 20)->nullable();
            $table->string('nouveau_statut', 20)->nullable();
            $table->text('commentaire')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['affaire_id', 'created_at']);
        });

        Schema::create('stats_affaires_judiciaires', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->string('scope_value', 50)->nullable();
            $table->jsonb('data');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'scope_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats_affaires_judiciaires');
        Schema::dropIfExists('affaires_moderation_logs');
        Schema::dropIfExists('affaires_sources');
        Schema::dropIfExists('affaires_judiciaires');
    }
};
