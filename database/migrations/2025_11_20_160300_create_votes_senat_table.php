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
        Schema::create('votes_senat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrutin_senat_id')->constrained('scrutins_senat')->onDelete('cascade');
            $table->string('senateur_matricule', 20);
            $table->string('position', 20); // 'pour', 'contre', 'abstention', 'non_votant'
            $table->jsonb('donnees_source')->nullable();
            $table->timestamps();

            $table->foreign('senateur_matricule')->references('matricule')->on('senateurs')->onDelete('cascade');
            $table->index(['scrutin_senat_id', 'senateur_matricule']);
            $table->index(['senateur_matricule', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes_senat');
    }
};

