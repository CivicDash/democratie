<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer l'ancienne contrainte unique si elle existe
        Schema::table('french_postal_codes', function (Blueprint $table) {
            try {
                $table->dropUnique('unique_postal_city_insee');
            } catch (\Exception $e) {
                // La contrainte n'existe peut-être pas, on ignore l'erreur
            }
        });

        // Ajouter la nouvelle contrainte unique (sans insee_code)
        Schema::table('french_postal_codes', function (Blueprint $table) {
            try {
                $table->unique(['postal_code', 'city_name'], 'unique_postal_city');
            } catch (\Exception $e) {
                // La contrainte existe peut-être déjà, on ignore l'erreur
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('french_postal_codes', function (Blueprint $table) {
            try {
                $table->dropUnique('unique_postal_city');
            } catch (\Exception $e) {
                // Ignorer les erreurs
            }
            
            try {
                $table->unique(['postal_code', 'city_name', 'insee_code'], 'unique_postal_city_insee');
            } catch (\Exception $e) {
                // Ignorer les erreurs
            }
        });
    }
};

