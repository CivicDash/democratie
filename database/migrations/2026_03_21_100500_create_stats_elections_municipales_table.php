<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statistiques agrégées des élections municipales.
 * Stocke les résultats pré-calculés en jsonb par scope (national/département/région).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stats_elections_municipales', function (Blueprint $table) {
            $table->id();
            $table->integer('annee');
            $table->string('scope', 20);
            $table->string('scope_code', 5)->nullable();
            $table->jsonb('data');
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['annee', 'scope', 'scope_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats_elections_municipales');
    }
};
