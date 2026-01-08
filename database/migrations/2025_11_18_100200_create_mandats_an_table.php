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
        Schema::create('mandats_an', function (Blueprint $table) {
            $table->string('uid', 20)->primary()->comment('UID mandat (ex: PM842426)');
            $table->string('acteur_ref', 20)->index();
            $table->string('organe_ref', 20)->nullable()->index();
            $table->integer('legislature')->nullable()->index();
            $table->string('type_organe', 50)->comment('ASSEMBLEE, COMPER, GP, etc.');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('code_qualite', 50)->comment('Membre, Président, etc.');
            $table->string('libelle_qualite', 100);
            $table->integer('preseance')->nullable()->comment('Ordre de préséance');
            $table->boolean('nomination_principale')->default(false);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('acteur_ref')->references('uid')->on('acteurs_an')->onDelete('cascade');
            $table->foreign('organe_ref')->references('uid')->on('organes_an')->onDelete('set null');
            
            // Index composites
            $table->index(['acteur_ref', 'legislature']);
            $table->index(['organe_ref', 'legislature']);
            $table->index(['legislature', 'type_organe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandats_an');
    }
};

