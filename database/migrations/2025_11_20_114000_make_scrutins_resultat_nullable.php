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
        Schema::table('scrutins_an', function (Blueprint $table) {
            // Certains scrutins n'ont pas de résultat dans les données JSON
            $table->string('resultat_code', 20)->nullable()->change();
            $table->string('resultat_libelle', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scrutins_an', function (Blueprint $table) {
            $table->string('resultat_code', 20)->nullable(false)->change();
            $table->string('resultat_libelle', 255)->nullable(false)->change();
        });
    }
};

