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
        // Fonctions bénévoles
        Schema::create('hatvp_fonctions_benevoles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('nom_structure', 500)->nullable();
            $table->text('description_activite')->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Participations aux organes dirigeants
        Schema::create('hatvp_participations_dirigeantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('nom_societe', 500)->nullable();
            $table->text('activite')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Participations financières directes
        Schema::create('hatvp_participations_financieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('nom_societe', 500)->nullable();
            $table->string('evaluation', 200)->nullable();
            $table->string('capital_detenu', 50)->nullable();
            $table->string('nombre_parts', 50)->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Collaborateurs parlementaires
        Schema::create('hatvp_collaborateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('nom', 200)->nullable();
            $table->string('employeur', 500)->nullable();
            $table->text('description_activite')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Activités de consultant
        Schema::create('hatvp_activites_consultant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('nom_employeur', 500)->nullable();
            $table->text('description')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Activités professionnelles (5 dernières années)
        Schema::create('hatvp_activites_professionnelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('employeur', 500)->nullable();
            $table->text('description')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hatvp_activites_professionnelles');
        Schema::dropIfExists('hatvp_activites_consultant');
        Schema::dropIfExists('hatvp_collaborateurs');
        Schema::dropIfExists('hatvp_participations_financieres');
        Schema::dropIfExists('hatvp_participations_dirigeantes');
        Schema::dropIfExists('hatvp_fonctions_benevoles');
    }
};

