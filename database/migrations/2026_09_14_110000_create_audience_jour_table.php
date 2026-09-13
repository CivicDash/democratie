<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audience d'objectif2027.fr — AGRÉGATS QUOTIDIENS UNIQUEMENT.
 *
 * Aucune ligne de log, aucune adresse IP, aucun identifiant de visiteur n'est stocké ici :
 * la table ne contient que des compteurs par jour et par page. C'est ce qui permet de
 * mesurer l'impact du site sans cookie, sans script côté visiteur et sans service tiers —
 * cohérent avec la promesse « sans compte, sans traceur ».
 *
 * La source est le journal d'accès du serveur, qui existe de toute façon : rien n'est
 * collecté en plus de ce que le serveur écrit déjà pour fonctionner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_jour', function (Blueprint $table) {
            $table->id();
            $table->string('site', 60)->default('objectif2027.fr');
            $table->date('jour');
            $table->string('chemin', 300);

            // Deux compteurs séparés plutôt qu'un total : l'écart entre les deux est
            // lui-même l'information intéressante.
            $table->unsignedInteger('vues_humaines')->default(0);
            $table->unsignedInteger('vues_bots')->default(0);
            // Approximation de visiteurs distincts : nombre d'empreintes distinctes vues
            // ce jour-là. L'empreinte n'est jamais stockée, seul son décompte l'est.
            $table->unsignedInteger('visiteurs_estimes')->default(0);

            $table->timestamps();

            $table->unique(['site', 'jour', 'chemin']);
            $table->index(['site', 'jour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_jour');
    }
};
