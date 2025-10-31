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
        Schema::create('groupes_parlementaires', function (Blueprint $table) {
            $table->id();
            
            // Identification
            $table->string('source', 20)->comment('assemblee ou senat');
            $table->string('uid', 50)->nullable()->comment('Identifiant unique API');
            
            // Informations
            $table->string('nom', 150)->comment('Ex: Renaissance, Les Républicains');
            $table->string('sigle', 20)->comment('Ex: RE, LR, RN');
            $table->string('couleur_hex', 7)->default('#6B7280')->comment('Couleur du groupe');
            
            // Position politique
            $table->enum('position_politique', [
                'extreme_gauche',
                'gauche',
                'centre_gauche',
                'centre',
                'centre_droit',
                'droite',
                'extreme_droite',
                'non_inscrit'
            ])->default('centre');
            
            // Composition
            $table->integer('nombre_membres')->default(0);
            $table->string('president_nom', 150)->nullable();
            
            // Ressources
            $table->string('logo_url')->nullable();
            $table->string('url_officiel')->nullable();
            
            // Contexte
            $table->integer('legislature')->comment('Numéro de législature');
            $table->boolean('actif')->default(true);
            
            // Métadonnées
            $table->json('apparentes')->nullable()->comment('Groupes apparentés');
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['source', 'actif', 'legislature'], 'idx_source_actif_legislature');
            $table->index(['sigle'], 'idx_sigle');
            $table->index(['position_politique'], 'idx_position');
            $table->unique(['source', 'sigle', 'legislature'], 'unique_groupe_legislature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupes_parlementaires');
    }
};

