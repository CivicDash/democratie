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
        // Mandats électifs déclarés
        Schema::create('hatvp_mandats_electifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('hatvp_declarations')->onDelete('cascade');
            $table->string('description', 500)->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('conservee')->default(true);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->index('declaration_id');
        });

        // Rémunérations par année (liées aux mandats)
        Schema::create('hatvp_remunerations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandat_id')->constrained('hatvp_mandats_electifs')->onDelete('cascade');
            $table->integer('annee');
            $table->decimal('montant', 12, 2)->nullable();
            $table->string('brut_net', 10)->nullable();
            $table->timestamps();
            
            $table->index(['mandat_id', 'annee']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hatvp_remunerations');
        Schema::dropIfExists('hatvp_mandats_electifs');
    }
};

