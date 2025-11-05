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
        Schema::create('votes_groupes_parlementaires', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('vote_legislatif_id')->constrained('votes_legislatifs')->onDelete('cascade');
            $table->foreignId('groupe_parlementaire_id')->constrained('groupes_parlementaires')->onDelete('cascade');
            
            // Position du groupe
            $table->enum('position_groupe', ['pour', 'contre', 'abstention', 'mixte'])->comment('Position majoritaire du groupe');
            
            // Détails des votes
            $table->integer('nombre_pour')->default(0);
            $table->integer('pour')->default(0)->comment('Alias pour nombre_pour');
            $table->integer('nombre_contre')->default(0);
            $table->integer('contre')->default(0)->comment('Alias pour nombre_contre');
            $table->integer('nombre_abstention')->default(0);
            $table->integer('abstention')->default(0)->comment('Alias pour nombre_abstention');
            $table->integer('nombre_absents')->default(0);
            $table->integer('non_votants')->default(0)->comment('Alias pour nombre_absents');
            
            // Cohésion
            $table->decimal('pourcentage_discipline', 5, 2)->nullable()->comment('% de membres ayant voté avec la ligne du groupe');
            
            // Métadonnées
            $table->text('commentaire_officiel')->nullable()->comment('Communiqué du groupe');
            $table->json('deputes_dissidents')->nullable()->comment('Liste des députés n\'ayant pas suivi la ligne');
            
            $table->timestamps();
            
            // Index
            $table->unique(['vote_legislatif_id', 'groupe_parlementaire_id'], 'unique_vote_groupe');
            $table->index(['groupe_parlementaire_id', 'position_groupe'], 'idx_groupe_position');
            $table->index(['vote_legislatif_id'], 'idx_vote');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes_groupes_parlementaires');
    }
};

