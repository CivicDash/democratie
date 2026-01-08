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
        Schema::create('french_postal_codes', function (Blueprint $table) {
            $table->id();
            
            // Code postal (ex: 75001, 13001)
            $table->string('postal_code', 5)->index();
            
            // Nom de la commune
            $table->string('city_name', 100)->index();
            
            // Code département (ex: 75, 13, 01)
            $table->string('department_code', 3)->index();
            
            // Nom du département
            $table->string('department_name', 100);
            
            // Code région
            $table->string('region_code', 2)->nullable()->index();
            
            // Nom de la région
            $table->string('region_name', 100)->nullable();
            
            // Circonscription législative (ex: 75-01, 13-05)
            $table->string('circonscription', 10)->nullable()->index();
            
            // Coordonnées GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Code INSEE de la commune
            $table->string('insee_code', 5)->nullable()->index();
            
            // Population (optionnel, pour stats)
            $table->integer('population')->nullable();
            
            $table->timestamps();
            
            // Index composés pour recherche rapide
            $table->index(['postal_code', 'city_name'], 'idx_postal_city');
            $table->index(['department_code', 'city_name'], 'idx_dept_city');
            $table->index(['circonscription'], 'idx_circonscription');
            
            // Unique sur code postal + ville uniquement (insee_code peut être NULL)
            $table->unique(['postal_code', 'city_name'], 'unique_postal_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('french_postal_codes');
    }
};
