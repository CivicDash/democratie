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
        // ========================================
        // 1. TABLE DES VOTES DÉTAILLÉS
        // ========================================
        Schema::create('votes_deputes', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('depute_senateur_id')
                ->constrained('deputes_senateurs')
                ->onDelete('cascade');
            
            $table->string('numero_scrutin', 50)->index();
            $table->date('date_vote')->index();
            $table->text('titre');
            $table->string('position', 20)->index(); // pour/contre/abstention/absent
            $table->string('resultat', 20)->nullable(); // adopte/rejete
            
            // Résultats du scrutin
            $table->integer('pour')->nullable();
            $table->integer('contre')->nullable();
            $table->integer('abstentions')->nullable();
            $table->integer('absents')->nullable();
            
            $table->string('type_vote', 50)->nullable(); // solennel, ordinaire, etc.
            $table->string('url_scrutin')->nullable();
            $table->text('contexte')->nullable(); // Sur quel texte
            
            $table->timestamps();
            
            // Index
            $table->index(['depute_senateur_id', 'date_vote'], 'idx_depute_date');
            $table->index(['position', 'resultat'], 'idx_position_resultat');
            $table->unique(['depute_senateur_id', 'numero_scrutin'], 'unique_depute_scrutin');
        });

        // ========================================
        // 2. TABLE DES INTERVENTIONS
        // ========================================
        Schema::create('interventions_parlementaires', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('depute_senateur_id')
                ->constrained('deputes_senateurs')
                ->onDelete('cascade');
            
            $table->date('date_intervention')->index();
            $table->string('type', 50); // seance, commission, question_orale, etc.
            $table->string('titre');
            $table->text('sujet')->nullable();
            $table->text('contenu')->nullable(); // Texte de l'intervention
            
            $table->integer('duree_secondes')->nullable(); // Durée en secondes
            $table->integer('nb_mots')->nullable(); // Nombre de mots
            
            $table->string('url_video')->nullable();
            $table->string('url_texte')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['depute_senateur_id', 'date_intervention'], 'idx_depute_date_inter');
            $table->index(['type', 'date_intervention'], 'idx_type_date');
        });

        // ========================================
        // 3. TABLE DES QUESTIONS AU GOUVERNEMENT
        // ========================================
        Schema::create('questions_gouvernement', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('depute_senateur_id')
                ->constrained('deputes_senateurs')
                ->onDelete('cascade');
            
            $table->string('type', 20); // ecrite, orale
            $table->string('numero', 50)->unique();
            
            $table->date('date_depot')->index();
            $table->date('date_reponse')->nullable();
            
            $table->string('ministere', 150)->nullable();
            $table->string('titre');
            $table->text('question');
            $table->text('reponse')->nullable();
            
            $table->string('statut', 50)->default('en_attente'); // en_attente, repondu, retire
            $table->string('url')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['depute_senateur_id', 'type'], 'idx_depute_type_question');
            $table->index(['statut', 'date_depot'], 'idx_statut_date');
        });

        // ========================================
        // 4. MISE À JOUR TABLE AMENDEMENTS
        // ========================================
        // Ajouter des colonnes si la table existe déjà
        if (Schema::hasTable('amendements')) {
            Schema::table('amendements', function (Blueprint $table) {
                if (!Schema::hasColumn('amendements', 'depute_senateur_id')) {
                    $table->foreignId('depute_senateur_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('deputes_senateurs')
                        ->onDelete('set null');
                }
                
                if (!Schema::hasColumn('amendements', 'cosignataires')) {
                    $table->json('cosignataires')->nullable()->after('auteur_uid');
                }
            });
        }

        // ========================================
        // 5. MISE À JOUR TABLE PROPOSITIONS_LOI
        // ========================================
        if (Schema::hasTable('propositions_loi')) {
            Schema::table('propositions_loi', function (Blueprint $table) {
                if (!Schema::hasColumn('propositions_loi', 'premier_signataire_id')) {
                    $table->foreignId('premier_signataire_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('deputes_senateurs')
                        ->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions_gouvernement');
        Schema::dropIfExists('interventions_parlementaires');
        Schema::dropIfExists('votes_deputes');
        
        if (Schema::hasTable('amendements')) {
            Schema::table('amendements', function (Blueprint $table) {
                if (Schema::hasColumn('amendements', 'depute_senateur_id')) {
                    $table->dropForeign(['depute_senateur_id']);
                    $table->dropColumn('depute_senateur_id');
                }
                if (Schema::hasColumn('amendements', 'cosignataires')) {
                    $table->dropColumn('cosignataires');
                }
            });
        }
        
        if (Schema::hasTable('propositions_loi')) {
            Schema::table('propositions_loi', function (Blueprint $table) {
                if (Schema::hasColumn('propositions_loi', 'premier_signataire_id')) {
                    $table->dropForeign(['premier_signataire_id']);
                    $table->dropColumn('premier_signataire_id');
                }
            });
        }
    }
};

