<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insee_salaires', function (Blueprint $table) {
            $table->id();
            $table->integer('annee');
            $table->string('type', 50)->default('global'); // global, prive, public, cadres, ouvriers, etc.
            $table->string('categorie', 100)->nullable(); // Sous-catégorie optionnelle
            
            // Salaires nets mensuels en euros
            $table->decimal('salaire_median', 12, 2)->nullable();
            $table->decimal('salaire_moyen', 12, 2)->nullable();
            
            // Déciles pour distribution complète
            $table->decimal('d1', 12, 2)->nullable(); // 10% gagnent moins
            $table->decimal('d2', 12, 2)->nullable();
            $table->decimal('d3', 12, 2)->nullable();
            $table->decimal('d4', 12, 2)->nullable();
            $table->decimal('d5', 12, 2)->nullable(); // = médiane
            $table->decimal('d6', 12, 2)->nullable();
            $table->decimal('d7', 12, 2)->nullable();
            $table->decimal('d8', 12, 2)->nullable();
            $table->decimal('d9', 12, 2)->nullable(); // 10% gagnent plus
            
            // Écart et ratio
            $table->decimal('rapport_interdecile', 6, 2)->nullable(); // D9/D1
            $table->decimal('part_sous_smic', 6, 2)->nullable(); // % de salariés sous le SMIC
            
            // Métadonnées
            $table->string('source', 255)->default('INSEE');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['annee', 'type', 'categorie']);
            $table->index('annee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insee_salaires');
    }
};
