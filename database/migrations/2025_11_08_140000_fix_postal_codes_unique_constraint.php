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
        // Vérifier si la contrainte existe avant de la supprimer
        $constraintExists = DB::select("
            SELECT constraint_name 
            FROM information_schema.table_constraints 
            WHERE table_name = 'french_postal_codes' 
            AND constraint_name = 'unique_postal_city_insee'
        ");

        if (!empty($constraintExists)) {
            Schema::table('french_postal_codes', function (Blueprint $table) {
                $table->dropUnique('unique_postal_city_insee');
            });
        }

        // Vérifier si la nouvelle contrainte n'existe pas déjà
        $newConstraintExists = DB::select("
            SELECT constraint_name 
            FROM information_schema.table_constraints 
            WHERE table_name = 'french_postal_codes' 
            AND constraint_name = 'unique_postal_city'
        ");

        if (empty($newConstraintExists)) {
            Schema::table('french_postal_codes', function (Blueprint $table) {
                $table->unique(['postal_code', 'city_name'], 'unique_postal_city');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Vérifier si la contrainte existe avant de la supprimer
        $constraintExists = DB::select("
            SELECT constraint_name 
            FROM information_schema.table_constraints 
            WHERE table_name = 'french_postal_codes' 
            AND constraint_name = 'unique_postal_city'
        ");

        if (!empty($constraintExists)) {
            Schema::table('french_postal_codes', function (Blueprint $table) {
                $table->dropUnique('unique_postal_city');
            });
        }

        // Recréer l'ancienne contrainte si elle n'existe pas
        $oldConstraintExists = DB::select("
            SELECT constraint_name 
            FROM information_schema.table_constraints 
            WHERE table_name = 'french_postal_codes' 
            AND constraint_name = 'unique_postal_city_insee'
        ");

        if (empty($oldConstraintExists)) {
            Schema::table('french_postal_codes', function (Blueprint $table) {
                $table->unique(['postal_code', 'city_name', 'insee_code'], 'unique_postal_city_insee');
            });
        }
    }
};

