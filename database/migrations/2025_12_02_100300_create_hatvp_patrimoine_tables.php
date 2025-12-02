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
        // Biens immobiliers
        Schema::create('hatvp_immeubles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('nature', 100)->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('localite', 200)->nullable();
            $table->integer('superficie_bati')->nullable();
            $table->integer('superficie_non_bati')->nullable();
            $table->string('date_acquisition', 20)->nullable(); // Année
            $table->string('origine', 100)->nullable(); // Mode acquisition
            $table->string('droit_reel', 100)->nullable();
            $table->string('quote_part', 50)->nullable();
            $table->decimal('prix_acquisition', 12, 2)->nullable();
            $table->decimal('prix_travaux', 12, 2)->nullable();
            $table->decimal('valeur_venale', 12, 2)->nullable();
            $table->string('regime_juridique', 100)->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Véhicules
        Schema::create('hatvp_vehicules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('nature', 100)->nullable();
            $table->string('marque', 100)->nullable();
            $table->integer('annee_achat')->nullable();
            $table->decimal('valeur_achat', 12, 2)->nullable();
            $table->decimal('valeur', 12, 2)->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Comptes bancaires
        Schema::create('hatvp_comptes_bancaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('type_compte', 100)->nullable();
            $table->string('etablissement', 200)->nullable();
            $table->string('titulaire', 200)->nullable();
            $table->decimal('valeur', 12, 2)->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Assurances vie
        Schema::create('hatvp_assurances_vie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('souscripteur', 200)->nullable();
            $table->string('etablissement', 200)->nullable();
            $table->string('date_souscription', 20)->nullable();
            $table->decimal('valeur_rachat', 12, 2)->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Valeurs mobilières non cotées
        Schema::create('hatvp_valeurs_non_cotees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('denomination', 500)->nullable();
            $table->decimal('valeur_actuelle', 12, 2)->nullable();
            $table->string('participation', 50)->nullable(); // % capital
            $table->string('droit_reel', 100)->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Valeurs mobilières cotées
        Schema::create('hatvp_valeurs_cotees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('titulaire', 200)->nullable();
            $table->string('etablissement', 200)->nullable();
            $table->string('nature_placement', 200)->nullable();
            $table->decimal('valeur', 12, 2)->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Passif (dettes)
        Schema::create('hatvp_passif', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('nom_creancier', 300)->nullable();
            $table->string('nature', 200)->nullable();
            $table->string('date_passif', 20)->nullable();
            $table->text('objet_dette')->nullable();
            $table->decimal('montant', 12, 2)->nullable();
            $table->string('duree', 50)->nullable();
            $table->decimal('restant_du', 12, 2)->nullable();
            $table->decimal('mensualite', 12, 2)->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Revenus annuels
        Schema::create('hatvp_revenus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->integer('annee');
            $table->string('type_revenu', 50)->nullable(); // indemnites, salaires, pensions, etc.
            $table->decimal('montant_elu', 12, 2)->nullable();
            $table->decimal('montant_conjoint', 12, 2)->nullable();
            $table->string('brut_net', 10)->nullable();
            $table->timestamps();
            
            $table->index(['declaration_id', 'annee']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hatvp_revenus');
        Schema::dropIfExists('hatvp_passif');
        Schema::dropIfExists('hatvp_valeurs_cotees');
        Schema::dropIfExists('hatvp_valeurs_non_cotees');
        Schema::dropIfExists('hatvp_assurances_vie');
        Schema::dropIfExists('hatvp_comptes_bancaires');
        Schema::dropIfExists('hatvp_vehicules');
        Schema::dropIfExists('hatvp_immeubles');
    }
};

