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
        Schema::create('proposition_loi_thematique', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('proposition_loi_id')->constrained('propositions_loi')->onDelete('cascade');
            $table->foreignId('thematique_legislation_id')->constrained('thematiques_legislation')->onDelete('cascade');
            
            // Type de relation
            $table->boolean('est_principal')->default(false)->comment('Thématique principale de la proposition');
            
            // Détection automatique
            $table->integer('confiance')->default(100)->comment('Niveau de confiance 0-100');
            $table->json('tags_keywords')->nullable()->comment('Mots-clés détectés');
            $table->string('tagged_by', 50)->default('manual')->comment('user_id, auto, admin');
            
            $table->timestamps();
            
            // Index
            $table->unique(['proposition_loi_id', 'thematique_legislation_id'], 'unique_proposition_thematique');
            $table->index(['thematique_legislation_id', 'est_principal'], 'idx_thematique_principal');
            $table->index(['tagged_by'], 'idx_tagged_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposition_loi_thematique');
    }
};

