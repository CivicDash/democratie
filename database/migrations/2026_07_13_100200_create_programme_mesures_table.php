<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mesures de programme rattachées à un candidat et à un thème.
 * Règle : une mesure = une source officielle obligatoire (contrôlée à la publication).
 * `est_mise_en_avant` : mesure phare sélectionnée pour le comparateur (symétrie §11.5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programme_mesures', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('candidat_id')
                ->constrained('candidats_presidentielle')->cascadeOnDelete();
            $table->foreignId('theme_id')
                ->constrained('programme_themes')->restrictOnDelete();

            $table->string('titre', 300);
            $table->string('resume', 300)->nullable();
            $table->text('description_complete')->nullable();
            $table->string('chiffrage_annonce', 255)->nullable();
            $table->string('source_officielle_url', 500)->nullable();
            $table->date('date_annonce')->nullable();
            $table->string('statut_mesure', 20)->default('annoncee');
            $table->boolean('est_mise_en_avant')->default(false)->index();

            // Workflow de validation
            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();
            $table->integer('ordre')->default(0);

            $table->string('source_detection', 30)->nullable();
            $table->decimal('detection_confidence', 3, 2)->nullable();
            $table->jsonb('detection_raw_data')->nullable();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['candidat_id', 'theme_id']);
            $table->index('statut_validation');
            $table->index(['affiche_publiquement', 'statut_validation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_mesures');
    }
};
