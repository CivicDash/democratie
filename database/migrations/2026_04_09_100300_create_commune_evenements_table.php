<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_evenements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('commune_page_id')->constrained('commune_pages')->cascadeOnDelete();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();

            $table->string('titre');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            // Lieu
            $table->string('lieu_nom')->nullable();
            $table->string('lieu_adresse')->nullable();
            $table->decimal('lieu_latitude', 10, 7)->nullable();
            $table->decimal('lieu_longitude', 10, 7)->nullable();

            // Dates
            $table->timestamp('date_debut');
            $table->timestamp('date_fin')->nullable();
            $table->boolean('journee_entiere')->default(false);
            $table->string('recurrence', 20)->nullable();

            // Inscriptions
            $table->boolean('inscription_requise')->default(false);
            $table->unsignedInteger('places_max')->nullable();
            $table->unsignedInteger('inscrits_count')->default(0);
            $table->timestamp('inscription_limite')->nullable();
            $table->text('inscription_infos')->nullable();

            $table->string('categorie', 30)->default('autre');

            $table->boolean('publie')->default(false);
            $table->boolean('annule')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['commune_page_id', 'publie', 'date_debut']);
            $table->index('categorie');
        });

        DB::statement("ALTER TABLE commune_evenements ADD CONSTRAINT commune_evenements_categorie_check CHECK (categorie IN ('ceremonie', 'culture', 'sport', 'marche', 'reunion', 'atelier', 'fete', 'environnement', 'solidarite', 'autre'))");
        DB::statement("ALTER TABLE commune_evenements ADD CONSTRAINT commune_evenements_recurrence_check CHECK (recurrence IS NULL OR recurrence IN ('quotidien', 'hebdomadaire', 'mensuel', 'annuel'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_evenements');
    }
};
