<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senateurs_etudes', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 10)->index();
            $table->string('diplome')->nullable();
            $table->string('etablissement')->nullable();
            $table->integer('annee_obtention')->nullable();
            $table->timestamps();
            
            $table->foreign('matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senateurs_etudes');
    }
};

