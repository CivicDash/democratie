<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senateurs_mandats', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 10)->index();
            $table->enum('type_mandat', ['SENATEUR', 'DEPUTE', 'EUROPEEN', 'METROPOLITAIN', 'MUNICIPAL']);
            $table->string('circonscription', 100)->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('motif_fin', 50)->nullable();
            $table->integer('numero_mandat')->nullable();
            $table->timestamps();
            
            $table->foreign('matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
            $table->index(['matricule', 'type_mandat']);
            $table->index(['type_mandat', 'date_debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senateurs_mandats');
    }
};

