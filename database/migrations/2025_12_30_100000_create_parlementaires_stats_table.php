<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de statistiques pré-calculées pour les parlementaires
     * Mise à jour quotidiennement pour optimiser les performances
     */
    public function up(): void
    {
        Schema::create('parlementaires_stats', function (Blueprint $table) {
            $table->id();

            // Identification du parlementaire
            $table->string('parlementaire_id', 50)->index(); // UID pour députés, matricule pour sénateurs
            $table->enum('type', ['depute', 'senateur'])->index();
            $table->integer('legislature')->nullable(); // Législature pour les députés

            // Statistiques de votes
            $table->integer('votes_total')->default(0);
            $table->integer('votes_pour')->default(0);
            $table->integer('votes_contre')->default(0);
            $table->integer('votes_abstention')->default(0);
            $table->integer('scrutins_total')->default(0); // Total scrutins dans la période
            $table->decimal('taux_presence', 5, 2)->default(0); // Pourcentage

            // Statistiques d'amendements
            $table->integer('amendements_total')->default(0);
            $table->integer('amendements_adoptes')->default(0);
            $table->integer('amendements_rejetes')->default(0);
            $table->integer('amendements_retires')->default(0);
            $table->decimal('taux_adoption_amendements', 5, 2)->default(0); // Pourcentage

            // Discipline de groupe (députés)
            $table->decimal('discipline_groupe', 5, 2)->nullable(); // Pourcentage
            $table->integer('votes_rebelles')->default(0);

            // Activité générale
            $table->integer('questions_total')->default(0);
            $table->integer('interventions_total')->default(0);

            // Métadonnées
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            // Index unique pour éviter les doublons
            $table->unique(['parlementaire_id', 'type', 'legislature'], 'parlementaires_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parlementaires_stats');
    }
};
