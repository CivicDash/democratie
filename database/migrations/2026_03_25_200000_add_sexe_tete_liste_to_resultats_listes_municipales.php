<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultats_listes_municipales', function (Blueprint $table) {
            $table->string('tete_de_liste_sexe', 1)->nullable()->after('tete_de_liste_prenom');
        });
    }

    public function down(): void
    {
        Schema::table('resultats_listes_municipales', function (Blueprint $table) {
            $table->dropColumn('tete_de_liste_sexe');
        });
    }
};
