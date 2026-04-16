<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de statistiques pré-calculées pour les lois
     * Mise à jour quotidiennement pour optimiser les performances
     */
    public function up(): void
    {
        Schema::create('lois_stats', function (Blueprint $table) {
            $table->id();

            // Identification de la loi
            $table->string('loicod', 10)->unique()->index(); // Code Sénat

            // Statistiques d'étapes
            $table->integer('etapes_total')->default(0);
            $table->integer('etapes_an')->default(0);
            $table->integer('etapes_senat')->default(0);

            // Statistiques d'amendements
            $table->integer('amendements_total')->default(0);
            $table->integer('amendements_adoptes')->default(0);
            $table->integer('amendements_rejetes')->default(0);
            $table->integer('amendements_retires')->default(0);
            $table->decimal('taux_adoption_amendements', 5, 2)->default(0);

            // Statistiques de scrutins liés
            $table->integer('scrutins_total')->default(0);
            $table->integer('scrutins_adoptes')->default(0);
            $table->integer('scrutins_rejetes')->default(0);

            // Temps de parcours
            $table->integer('duree_jours')->nullable(); // Nombre de jours depuis dépôt
            $table->date('date_premiere_etape')->nullable();
            $table->date('date_derniere_etape')->nullable();

            // Score d'engagement (mesure de l'activité autour de la loi)
            $table->integer('score_engagement')->default(0); // Somme pondérée

            // Métadonnées
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            // Note: pas de foreign key car loicod n'est pas unique dans senat_dosleg_loi
            // La cohérence est assurée par la logique applicative
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lois_stats');
    }
};
