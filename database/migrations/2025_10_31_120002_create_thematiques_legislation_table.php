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
        Schema::create('thematiques_legislation', function (Blueprint $table) {
            $table->id();
            
            // Identification
            $table->string('code', 10)->unique()->comment('Ex: SECU, FISC, SOCI');
            $table->string('slug', 100)->unique()->comment('Slug URL-friendly');
            $table->string('nom', 100)->comment('Ex: Sécurité & Justice');
            $table->text('description')->nullable();
            
            // Affichage
            $table->string('couleur_hex', 7)->default('#3B82F6');
            $table->string('icone', 50)->comment('Emoji ou nom FontAwesome');
            
            // Hiérarchie
            $table->foreignId('parent_id')->nullable()->constrained('thematiques_legislation')->onDelete('cascade');
            $table->integer('ordre')->default(0)->comment('Ordre d\'affichage');
            
            // Métadonnées
            $table->json('mots_cles')->nullable()->comment('Mots-clés pour détection automatique');
            $table->json('synonymes')->nullable()->comment('Synonymes et variantes');
            
            // Statistiques (dénormalisées pour perf)
            $table->integer('nb_propositions')->default(0);
            
            $table->timestamps();
            
            // Index
            $table->index(['parent_id'], 'idx_parent');
            $table->index(['ordre'], 'idx_ordre');
            $table->fullText(['nom', 'description'], 'fulltext_thematiques');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thematiques_legislation');
    }
};

