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
        Schema::table('profiles', function (Blueprint $table) {
            // Localisation du citoyen
            $table->string('city_name', 150)->nullable()->after('department_id')->comment('Nom de la ville/commune');
            $table->string('postal_code', 10)->nullable()->after('city_name')->comment('Code postal');
            $table->string('circonscription', 20)->nullable()->after('postal_code')->comment('Circonscription législative (ex: 75-01)');
            
            // Index pour recherche rapide
            $table->index(['department_id', 'circonscription'], 'idx_location');
            $table->index('postal_code', 'idx_postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('idx_location');
            $table->dropIndex('idx_postal_code');
            $table->dropColumn(['city_name', 'postal_code', 'circonscription']);
        });
    }
};
