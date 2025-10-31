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
        Schema::create('deputes_senateurs', function (Blueprint $table) {
            $table->id();
            
            $table->string('source', 20)->comment('assemblee ou senat');
            $table->string('uid', 50)->unique()->comment('Identifiant unique de l\'élu');
            
            $table->string('nom');
            $table->string('prenom');
            $table->string('nom_complet')->nullable()->comment('Nom complet calculé');
            
            $table->string('civilite', 10)->nullable();
            $table->string('groupe_politique', 100)->nullable();
            $table->string('groupe_sigle', 20)->nullable();
            
            $table->string('circonscription', 100)->nullable()->comment('Département ou région');
            $table->string('numero_circonscription', 10)->nullable();
            
            $table->string('profession', 150)->nullable();
            $table->date('date_naissance')->nullable();
            
            $table->integer('legislature')->nullable();
            $table->date('debut_mandat')->nullable();
            $table->date('fin_mandat')->nullable();
            $table->boolean('en_exercice')->default(true);
            
            $table->string('photo_url')->nullable();
            $table->string('url_profil')->nullable()->comment('URL du profil sur site officiel');
            
            $table->json('fonctions')->nullable()->comment('Fonctions (président, rapporteur, etc.)');
            $table->json('commissions')->nullable()->comment('Commissions dont il est membre');
            
            $table->integer('nb_propositions')->default(0)->comment('Nombre de propositions déposées');
            $table->integer('nb_amendements')->default(0)->comment('Nombre d\'amendements déposés');
            $table->decimal('taux_presence', 5, 2)->nullable()->comment('Taux de présence en séance');
            
            $table->timestamps();
            
            // Index
            $table->index(['source', 'en_exercice'], 'idx_source_exercice');
            $table->index(['groupe_politique'], 'idx_groupe');
            $table->index(['circonscription'], 'idx_circonscription');
            $table->fullText(['nom', 'prenom'], 'fulltext_nom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deputes_senateurs');
    }
};

