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
        Schema::create('amendements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposition_loi_id')->constrained('propositions_loi')->onDelete('cascade');
            
            $table->string('source', 20)->nullable()->default('assemblee')->comment('assemblee ou senat');
            $table->string('numero', 50)->comment('Numéro de l\'amendement');
            $table->string('numero_parent', 50)->nullable()->comment('Numéro de l\'amendement parent (sous-amendement)');
            
            $table->string('objet')->nullable()->comment('Objet de l\'amendement');
            $table->text('dispositif')->comment('Texte du dispositif');
            $table->text('expose_motifs')->nullable()->comment('Exposé des motifs');
            $table->text('expose_sommaire')->nullable()->comment('Alias pour expose_motifs');
            
            $table->json('auteurs')->nullable()->comment('Liste des auteurs');
            $table->string('auteur_nom')->nullable()->comment('Nom de l\'auteur principal');
            $table->string('auteur_groupe')->nullable()->comment('Groupe de l\'auteur');
            $table->string('groupe_politique', 100)->nullable()->comment('Groupe politique');
            
            $table->string('sort', 30)->nullable()->default('en_discussion')->comment('adopte, rejete, retire, non_soutenu, tombe');
            $table->string('statut', 30)->nullable()->comment('Alias pour sort');
            $table->date('date_depot')->nullable();
            $table->date('date_discussion')->nullable();
            
            $table->string('lieu_discussion', 50)->nullable()->comment('commission ou hemicycle');
            
            $table->timestamps();
            
            // Index
            $table->index(['proposition_loi_id', 'sort'], 'idx_prop_sort');
            $table->index(['source', 'numero'], 'idx_source_numero');
            $table->index(['sort'], 'idx_sort');
            $table->fullText(['dispositif', 'expose_motifs'], 'fulltext_amendement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amendements');
    }
};

