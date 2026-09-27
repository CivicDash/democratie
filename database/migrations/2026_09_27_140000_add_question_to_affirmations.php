<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les fiches « Ce qu'on entend » deviennent des repères chiffrés, sans verdict, posés dans
 * les pages thèmes (décision du 27/09/2026). Une fiche ne part plus d'une affirmation
 * entendue (« Il y a trop d'immigration ») mais d'une question neutre (« Combien d'immigrés
 * vivent en France… ? »). L'énoncé reste en base comme trace de l'origine et n'est plus
 * exporté ; les verdicts restent en base, sans écran ni export — rien n'est détruit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affirmations', function (Blueprint $table) {
            $table->string('question', 300)->nullable()->after('enonce');
        });
    }

    public function down(): void
    {
        Schema::table('affirmations', function (Blueprint $table) {
            $table->dropColumn('question');
        });
    }
};
