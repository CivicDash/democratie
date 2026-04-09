<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_evenement_inscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('evenement_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedSmallInteger('nb_personnes')->default(1);
            $table->text('commentaire')->nullable();
            $table->string('statut', 20)->default('inscrit');

            $table->timestamps();

            $table->foreign('evenement_id')
                ->references('id')
                ->on('commune_evenements')
                ->cascadeOnDelete();

            $table->unique(['evenement_id', 'user_id']);
            $table->index('statut');
        });

        DB::statement("ALTER TABLE commune_evenement_inscriptions ADD CONSTRAINT commune_evenement_inscriptions_statut_check CHECK (statut IN ('inscrit', 'liste_attente', 'annule'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_evenement_inscriptions');
    }
};
