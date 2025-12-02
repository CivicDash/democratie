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
        // Table principale des déclarations HATVP
        Schema::create('hatvp_declarations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->timestamp('date_depot');
            $table->string('type_declaration', 10); // DIA, DSP, DIAC, DSPC, DIAI, DSPI
            $table->string('origine', 20)->nullable(); // ADEL
            $table->boolean('complete')->default(true);
            $table->string('version', 20)->nullable();
            
            // Lien vers le parlementaire
            $table->string('parlementaire_type', 20)->nullable(); // senateur, depute
            $table->string('parlementaire_id', 20)->nullable(); // matricule ou uid
            
            // Infos déclarant
            $table->string('civilite', 10)->nullable();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->date('date_naissance')->nullable();
            
            // Mandat
            $table->string('type_mandat', 50)->nullable();
            $table->string('code_categorie_mandat', 10)->nullable();
            $table->string('code_departement', 10)->nullable();
            $table->string('label_organe', 200)->nullable();
            $table->date('date_debut_mandat')->nullable();
            $table->date('date_fin_mandat')->nullable();
            
            // Observations
            $table->text('observations_interet')->nullable();
            $table->text('observations_patrimoine')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['parlementaire_type', 'parlementaire_id'], 'idx_hatvp_parlementaire');
            $table->index(['nom', 'prenom'], 'idx_hatvp_nom');
            $table->index('type_declaration');
            $table->index('date_depot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hatvp_declarations');
    }
};

