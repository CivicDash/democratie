<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute des champs complémentaires aux villes
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('villes', function (Blueprint $table) {
            if (! Schema::hasColumn('villes', 'wikipedia_url')) {
                $table->string('wikipedia_url', 255)->nullable();
            }
            if (! Schema::hasColumn('villes', 'site_officiel')) {
                $table->string('site_officiel', 255)->nullable();
            }
            if (! Schema::hasColumn('villes', 'blason_url')) {
                $table->string('blason_url', 255)->nullable();
            }
            if (! Schema::hasColumn('villes', 'altitude_min')) {
                $table->integer('altitude_min')->nullable();
            }
            if (! Schema::hasColumn('villes', 'altitude_max')) {
                $table->integer('altitude_max')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('villes', function (Blueprint $table) {
            $table->dropColumn(['wikipedia_url', 'site_officiel', 'blason_url', 'altitude_min', 'altitude_max']);
        });
    }
};
