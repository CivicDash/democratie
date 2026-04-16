<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables pour le Budget de l'État (PLF/LFI)
     * Source : data.gouv.fr - Budget de l'État par programme
     */
    public function up(): void
    {
        // Missions budgétaires (ex: Défense, Enseignement scolaire, Justice...)
        Schema::create('budget_missions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->index();
            $table->string('libelle');
            $table->integer('annee')->index();
            $table->string('type_loi', 20)->default('plf'); // plf, lfi, lfr
            $table->decimal('credits_ae', 18, 2)->nullable(); // Autorisations d'engagement
            $table->decimal('credits_cp', 18, 2)->nullable(); // Crédits de paiement
            $table->integer('nb_programmes')->default(0);
            $table->timestamps();

            $table->unique(['code', 'annee', 'type_loi']);
        });

        // Programmes budgétaires (rattachés aux missions)
        Schema::create('budget_programmes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('budget_missions')->cascadeOnDelete();
            $table->string('code', 20)->index(); // Ex: 146, 150, 151...
            $table->string('libelle');
            $table->string('ministere')->nullable();
            $table->integer('annee')->index();
            $table->string('type_loi', 20)->default('plf');
            $table->decimal('credits_ae', 18, 2)->nullable();
            $table->decimal('credits_cp', 18, 2)->nullable();
            $table->decimal('credits_ae_prev', 18, 2)->nullable(); // Année N-1
            $table->decimal('credits_cp_prev', 18, 2)->nullable();
            $table->decimal('evolution_pct', 8, 2)->nullable(); // % évolution
            $table->timestamps();

            $table->unique(['code', 'annee', 'type_loi']);
        });

        // Ministères (agrégation par ministère)
        Schema::create('budget_ministeres', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->index();
            $table->string('nom');
            $table->string('sigle', 20)->nullable();
            $table->integer('annee')->index();
            $table->string('type_loi', 20)->default('plf');
            $table->decimal('budget_ae', 18, 2)->nullable();
            $table->decimal('budget_cp', 18, 2)->nullable();
            $table->integer('effectifs_etpt')->nullable(); // Équivalents temps plein
            $table->integer('nb_programmes')->default(0);
            $table->string('couleur', 7)->nullable(); // Pour les graphiques
            $table->timestamps();

            $table->unique(['code', 'annee', 'type_loi']);
        });

        // Historique annuel du budget global
        Schema::create('budget_annuel', function (Blueprint $table) {
            $table->id();
            $table->integer('annee')->unique();
            $table->decimal('recettes_nettes', 18, 2)->nullable();
            $table->decimal('depenses_nettes', 18, 2)->nullable();
            $table->decimal('deficit', 18, 2)->nullable();
            $table->decimal('dette_publique', 18, 2)->nullable();
            $table->decimal('pib', 18, 2)->nullable();
            $table->decimal('deficit_pib_pct', 8, 2)->nullable(); // Déficit en % du PIB
            $table->decimal('dette_pib_pct', 8, 2)->nullable(); // Dette en % du PIB
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Actions budgétaires (sous-programmes, optionnel)
        Schema::create('budget_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained('budget_programmes')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('libelle');
            $table->integer('annee');
            $table->decimal('credits_ae', 18, 2)->nullable();
            $table->decimal('credits_cp', 18, 2)->nullable();
            $table->timestamps();

            $table->index(['programme_id', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_actions');
        Schema::dropIfExists('budget_annuel');
        Schema::dropIfExists('budget_ministeres');
        Schema::dropIfExists('budget_programmes');
        Schema::dropIfExists('budget_missions');
    }
};
