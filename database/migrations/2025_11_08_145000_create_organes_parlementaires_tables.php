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
        // 1. TABLE DES ORGANES PARLEMENTAIRES
        // ========================================
        Schema::create('organes_parlementaires', function (Blueprint $table) {
            $table->id();
            
            $table->string('source', 20); // assemblee/senat
            $table->string('type', 50); // groupe/commission/delegation/mission/office
            
            $table->string('slug', 255)->unique();
            $table->string('sigle', 50)->nullable()->index(); // RE, LFI, COMM-AFF-ECO
            $table->string('nom');
            $table->text('nom_long')->nullable();
            
            $table->text('description')->nullable();
            $table->string('couleur_hex', 7)->nullable(); // #3B82F6
            $table->string('position_politique', 50)->nullable(); // gauche/centre/droite
            
            $table->integer('nombre_membres')->default(0);
            
            $table->string('url_nosdeputes')->nullable();
            $table->string('url_assemblee')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['source', 'type'], 'idx_source_type');
            $table->index('sigle');
            $table->index('type');
        });

        // ========================================
        // 2. TABLE DES MEMBRES D'ORGANES
        // ========================================
        Schema::create('membres_organes', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('organe_id')
                ->constrained('organes_parlementaires')
                ->onDelete('cascade');
            
            $table->foreignId('depute_senateur_id')
                ->constrained('deputes_senateurs')
                ->onDelete('cascade');
            
            $table->string('fonction', 100)->nullable(); // president/vice-president/rapporteur/membre
            $table->integer('ordre')->nullable(); // Ordre d'affichage
            
            $table->date('date_debut')->index();
            $table->date('date_fin')->nullable()->index();
            $table->boolean('actif')->default(true)->index();
            
            $table->string('groupe_a_fin_fonction', 100)->nullable(); // Groupe au moment de la fin
            
            $table->timestamps();
            
            // Index
            $table->index(['organe_id', 'actif'], 'idx_organe_actif');
            $table->index(['depute_senateur_id', 'actif'], 'idx_depute_actif');
            $table->unique(['organe_id', 'depute_senateur_id', 'date_debut'], 'unique_membre_organe');
        });

        // ========================================
        // 3. MISE À JOUR TABLE GROUPES_PARLEMENTAIRES
        // ========================================
        // Ajouter relation vers organes_parlementaires
        if (Schema::hasTable('groupes_parlementaires')) {
            Schema::table('groupes_parlementaires', function (Blueprint $table) {
                if (!Schema::hasColumn('groupes_parlementaires', 'organe_parlementaire_id')) {
                    $table->foreignId('organe_parlementaire_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('organes_parlementaires')
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
        if (Schema::hasTable('groupes_parlementaires')) {
            Schema::table('groupes_parlementaires', function (Blueprint $table) {
                if (Schema::hasColumn('groupes_parlementaires', 'organe_parlementaire_id')) {
                    $table->dropForeign(['organe_parlementaire_id']);
                    $table->dropColumn('organe_parlementaire_id');
                }
            });
        }
        
        Schema::dropIfExists('membres_organes');
        Schema::dropIfExists('organes_parlementaires');
    }
};

