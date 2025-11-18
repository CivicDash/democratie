<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senateurs_historique_groupes', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 10)->index();
            $table->string('groupe_politique', 100);
            $table->string('type_appartenance', 50)->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();
            
            $table->foreign('matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
            $table->index(['matricule', 'date_debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senateurs_historique_groupes');
    }
};

