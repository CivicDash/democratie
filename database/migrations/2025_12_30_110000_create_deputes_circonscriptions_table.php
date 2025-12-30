<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de liaison député-circonscription
     * Contient les informations électorales extraites des mandats parlementaires
     */
    public function up(): void
    {
        Schema::create('deputes_circonscriptions', function (Blueprint $table) {
            $table->id();
            
            // Identification
            $table->string('acteur_uid', 20)->index();
            $table->string('mandat_uid', 20)->unique();
            $table->integer('legislature')->index();
            
            // Circonscription
            $table->string('circonscription_ref', 20)->nullable()->index(); // PO839429
            $table->string('departement', 100);
            $table->string('num_departement', 5)->index(); // 33, 2A, 2B, etc.
            $table->unsignedSmallInteger('num_circo')->index();
            $table->string('region', 100)->nullable();
            $table->string('region_type', 50)->nullable(); // Métropolitain, Outre-mer
            
            // Infos électorales
            $table->string('cause_mandat', 100)->nullable(); // élections générales, partielle, etc.
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->date('date_prise_fonction')->nullable();
            $table->string('cause_fin', 100)->nullable();
            $table->boolean('premiere_election')->default(false);
            $table->unsignedSmallInteger('place_hemicycle')->nullable();
            
            // Suppléant
            $table->string('suppleant_ref', 20)->nullable();
            
            // Métadonnées
            $table->timestamps();
            
            // Index composites
            $table->index(['num_departement', 'num_circo']);
            $table->index(['legislature', 'acteur_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deputes_circonscriptions');
    }
};
