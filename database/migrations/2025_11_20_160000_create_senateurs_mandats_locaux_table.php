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
        Schema::create('senateurs_mandats_locaux', function (Blueprint $table) {
            $table->id();
            $table->string('senateur_matricule', 20);
            $table->string('type_mandat', 50); // 'MUNICIPAL', 'DEPARTEMENTAL', 'REGIONAL', 'EUROPEEN', 'DEPUTE'
            $table->string('fonction')->nullable(); // 'Maire', 'Conseiller municipal', etc.
            $table->string('collectivite')->nullable(); // Nom de la commune/département/région
            $table->string('code_collectivite', 10)->nullable(); // Code INSEE ou autre
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('en_cours')->default(false);
            $table->text('details')->nullable(); // JSON pour infos complémentaires
            $table->timestamps();

            $table->foreign('senateur_matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
            $table->index(['senateur_matricule', 'type_mandat']);
            $table->index('en_cours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senateurs_mandats_locaux');
    }
};

