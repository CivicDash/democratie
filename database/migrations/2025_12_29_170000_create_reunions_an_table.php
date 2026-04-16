<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reunions_an', function (Blueprint $table) {
            $table->string('uid')->primary();           // RUANR5L16S2024IDC452600
            $table->integer('legislature')->nullable();
            $table->string('session')->nullable();      // S2024, S2025
            $table->string('type_reunion')->nullable(); // Commission, Séance, Délégation...

            // Timing
            $table->dateTime('date_debut')->nullable();
            $table->dateTime('date_fin')->nullable();

            // Lieu
            $table->string('lieu_ref')->nullable();
            $table->string('lieu_libelle')->nullable();

            // Cycle de vie
            $table->string('etat')->nullable();         // Confirmé, Annulé, Terminé
            $table->date('date_creation')->nullable();
            $table->date('date_cloture')->nullable();

            // Liens
            $table->string('organe_ref')->nullable();   // FK vers organes_an
            $table->string('compte_rendu_ref')->nullable();
            $table->string('session_ref')->nullable();
            $table->string('demandeur')->nullable();

            // Ordre du jour
            $table->json('odj_convocation')->nullable(); // Items ODJ
            $table->json('odj_resume')->nullable();
            $table->json('points_odj')->nullable();

            // Participants
            $table->json('participants_internes')->nullable();    // UIDs députés
            $table->json('personnes_auditionnees')->nullable();   // Noms/fonctions

            // Métadonnées
            $table->string('format_reunion')->nullable(); // Ordinaire, Extraordinaire
            $table->boolean('visio_conference')->default(false);
            $table->boolean('ouverture_presse')->default(false);
            $table->boolean('captation_video')->default(false);
            $table->boolean('reunion_internationale')->default(false);
            $table->json('pays_reunion_internationale')->nullable();

            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index('date_debut');
            $table->index('organe_ref');
            $table->index('etat');
            $table->index('legislature');
            $table->index(['date_debut', 'etat']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reunions_an');
    }
};
