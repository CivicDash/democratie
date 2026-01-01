<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables pour le Gouvernement
     * Source : info.gouv.fr, JORF
     */
    public function up(): void
    {
        // Gouvernements successifs
        Schema::create('gouvernements', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // Ex: "Gouvernement Barnier"
            $table->string('premier_ministre');
            $table->string('president');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(false);
            $table->integer('numero')->nullable(); // Numéro sous la Ve République
            $table->string('legislature', 20)->nullable();
            $table->text('contexte')->nullable(); // Contexte politique
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('actif');
        });

        // Ministères
        Schema::create('ministeres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gouvernement_id')->constrained('gouvernements')->cascadeOnDelete();
            $table->string('nom');
            $table->string('sigle', 30)->nullable();
            $table->string('type', 30)->default('ministere'); // ministere, secretariat_etat, ministere_delegue
            $table->string('rattachement')->nullable(); // Ministère de rattachement si secrétariat
            $table->integer('ordre')->default(0); // Ordre protocolaire
            $table->string('couleur', 10)->nullable();
            $table->string('icone', 10)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            
            $table->index('gouvernement_id');
        });

        // Ministres
        Schema::create('ministres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministere_id')->nullable()->constrained('ministeres')->nullOnDelete();
            $table->foreignId('gouvernement_id')->constrained('gouvernements')->cascadeOnDelete();
            
            $table->string('civilite', 10)->nullable(); // M., Mme
            $table->string('prenom');
            $table->string('nom');
            $table->string('fonction'); // Titre officiel
            $table->string('type_fonction', 30)->default('ministre'); // ministre, secretaire_etat, ministre_delegue
            
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);
            
            // Infos complémentaires
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('profession')->nullable();
            $table->string('parti_politique')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('twitter')->nullable();
            $table->string('wikipedia_url')->nullable();
            
            // JORF
            $table->string('decret_nomination')->nullable();
            $table->date('date_decret')->nullable();
            
            $table->timestamps();
            
            $table->index(['gouvernement_id', 'actif']);
        });

        // Historique des remaniements
        Schema::create('remaniements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gouvernement_id')->constrained('gouvernements')->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 30); // formation, remaniement, demission
            $table->text('description')->nullable();
            $table->string('decret_jorf')->nullable();
            $table->integer('nb_entrants')->default(0);
            $table->integer('nb_sortants')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remaniements');
        Schema::dropIfExists('ministres');
        Schema::dropIfExists('ministeres');
        Schema::dropIfExists('gouvernements');
    }
};
