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
        Schema::create('votes_legislatifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposition_loi_id')->constrained('propositions_loi')->onDelete('cascade');
            
            $table->string('titre')->nullable()->comment('Titre du vote');
            $table->string('source', 20)->comment('assemblee ou senat');
            $table->string('numero_scrutin', 20)->comment('Numéro du scrutin');
            $table->string('type_vote', 50)->comment('solennel, ordinaire, main_levee');
            
            $table->integer('votes_pour')->default(0);
            $table->integer('pour')->default(0)->comment('Alias pour votes_pour');
            $table->integer('votes_contre')->default(0);
            $table->integer('contre')->default(0)->comment('Alias pour votes_contre');
            $table->integer('abstentions')->default(0);
            $table->integer('abstention')->default(0)->comment('Alias pour abstentions');
            $table->integer('non_votants')->default(0);
            
            $table->string('resultat', 20)->comment('adopte ou rejete');
            
            $table->json('detail_votes')->nullable()->comment('Détail par député/sénateur');
            $table->json('detail_groupes')->nullable()->comment('Statistiques par groupe politique');
            
            $table->dateTime('date_vote');
            $table->string('lieu', 50)->nullable()->comment('commission ou hemicycle');
            $table->integer('quorum')->nullable()->comment('Quorum requis');
            
            $table->timestamps();
            
            // Index
            $table->index(['proposition_loi_id', 'date_vote'], 'idx_prop_date');
            $table->index(['source', 'numero_scrutin'], 'idx_source_scrutin');
            $table->index(['resultat'], 'idx_resultat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes_legislatifs');
    }
};

