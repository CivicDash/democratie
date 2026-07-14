<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidats à l'élection présidentielle. Pivot = personnes_politiques (existant).
 * Réutilise le workflow de validation des affaires judiciaires
 * (statut_validation, source_detection, detection_confidence...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidats_presidentielle', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('personne_politique_id')
                ->constrained('personnes_politiques')->cascadeOnDelete();

            $table->string('election', 9)->default('2027')->index();
            $table->string('statut_candidature', 30)->default('pressenti');
            $table->date('date_declaration')->nullable();
            $table->string('parti_soutien', 150)->nullable();
            $table->string('nuance_politique', 10)->nullable();
            $table->string('condition', 255)->nullable();

            $table->string('site_campagne_url', 500)->nullable();
            $table->string('programme_url_officiel', 500)->nullable();
            $table->string('couleur_hex', 9)->nullable();

            // Workflow de validation (parité affaires_judiciaires)
            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();
            $table->integer('ordre_affichage')->default(0);

            $table->string('source_detection', 30)->nullable();
            $table->timestamp('detecte_at')->nullable();
            $table->decimal('detection_confidence', 3, 2)->nullable();
            $table->jsonb('detection_raw_data')->nullable();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['personne_politique_id', 'election']);
            $table->index('statut_candidature');
            $table->index('statut_validation');
            $table->index(['affiche_publiquement', 'statut_validation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidats_presidentielle');
    }
};
