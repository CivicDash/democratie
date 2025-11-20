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
        Schema::create('dossiers_legislatifs_senat', function (Blueprint $table) {
            $table->id();
            $table->string('numero_senat', 50)->unique(); // Ex: "23-264"
            $table->string('numero_an', 50)->nullable(); // Pour matching avec AN
            $table->string('legislature', 10);
            $table->string('type_dossier', 100)->nullable(); // 'Projet de loi', 'Proposition de loi'
            $table->text('titre');
            $table->text('titre_court')->nullable();
            $table->date('date_depot')->nullable();
            $table->date('date_adoption_senat')->nullable();
            $table->date('date_promulgation')->nullable();
            $table->string('statut', 100)->nullable(); // 'En cours', 'Adopté', 'Rejeté', 'Promulgué'
            $table->text('url_senat')->nullable();
            $table->text('url_legifrance')->nullable();
            $table->string('numero_loi', 100)->nullable(); // Ex: "2024-123"
            $table->jsonb('donnees_source')->nullable(); // CSV original
            
            // Lien vers dossier AN correspondant
            $table->string('dossier_an_uid', 30)->nullable();
            $table->foreign('dossier_an_uid')->references('uid')->on('dossiers_legislatifs_an')->onDelete('set null');
            
            $table->timestamps();

            $table->index('legislature');
            $table->index('statut');
            $table->index('date_depot');
            $table->index('numero_an');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dossiers_legislatifs_senat');
    }
};

