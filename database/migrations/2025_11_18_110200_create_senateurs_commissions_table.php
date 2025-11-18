<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senateurs_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 10)->index();
            $table->string('commission', 100);
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('fonction', 50)->nullable()->comment('Président, Vice-président, Membre');
            $table->timestamps();
            
            $table->foreign('matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
            $table->index(['matricule', 'commission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senateurs_commissions');
    }
};

