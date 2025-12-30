<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elus_global_stats', function (Blueprint $table) {
            $table->id();
            $table->string('type_elu', 50)->unique(); // deputes, senateurs, maires
            
            // Effectifs
            $table->integer('total')->default(0);
            $table->integer('actifs')->default(0);
            
            // Parité
            $table->integer('hommes')->default(0);
            $table->integer('femmes')->default(0);
            $table->float('pct_femmes')->default(0);
            
            // Âges
            $table->float('age_moyen')->nullable();
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
            $table->json('tranches_age')->nullable(); // {"18-30": 10, "31-40": 50, ...}
            
            // Professions (top 10)
            $table->json('top_professions')->nullable(); // [{"nom": "...", "count": 50}, ...]
            
            // Groupes politiques ou nuances (top 10)
            $table->json('top_groupes')->nullable(); // [{"nom": "...", "count": 50, "couleur": "#..."}, ...]
            
            // Données brutes pour graphiques
            $table->json('data_parite')->nullable();
            $table->json('data_ages')->nullable();
            $table->json('data_groupes')->nullable();
            
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elus_global_stats');
    }
};
