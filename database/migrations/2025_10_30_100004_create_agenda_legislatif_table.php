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
        Schema::create('agenda_legislatif', function (Blueprint $table) {
            $table->id();
            
            $table->string('source', 20)->comment('assemblee ou senat');
            $table->date('date')->nullable()->comment('Date de l\'événement (peut être extrait de date_debut)');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->dateTime('date_debut')->nullable()->comment('Date et heure de début (format datetime)');
            $table->dateTime('date_fin')->nullable()->comment('Date et heure de fin (format datetime)');
            
            $table->string('type', 50)->comment('seance_publique, commission, questions_gouvernement');
            $table->string('lieu', 100)->nullable()->comment('Salle/commission');
            
            $table->string('titre')->comment('Titre de la séance');
            $table->text('description')->nullable()->comment('Description/ordre du jour');
            
            $table->json('sujets')->nullable()->comment('Liste des sujets à l\'ordre du jour');
            $table->json('textes_examines')->nullable()->comment('Références aux textes examinés');
            
            $table->string('url_externe')->nullable()->comment('URL sur le site officiel');
            $table->string('url_video')->nullable()->comment('URL du direct/replay');
            
            $table->string('statut', 30)->default('prevu')->comment('prevu, en_cours, termine, annule');
            
            $table->timestamps();
            
            // Index
            $table->index(['source', 'date'], 'idx_source_date');
            $table->index(['date', 'type'], 'idx_date_type');
            $table->index(['statut'], 'idx_statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_legislatif');
    }
};

