<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numéro de page d'une entrée de référentiel dans le livre imprimé (ex. L'Avenir en commun 2027,
 * 212 pages) : le texte en ligne n'a pas de pagination, la page permet de citer « p. 58 ».
 * Nullable : les référentiels tirés d'un site (édition 2025) n'en ont pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programme_document_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('page')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('programme_document_items', function (Blueprint $table) {
            $table->dropColumn('page');
        });
    }
};
