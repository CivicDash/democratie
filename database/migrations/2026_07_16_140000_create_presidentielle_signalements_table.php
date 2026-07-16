<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signalements citoyens (« Signaler une erreur ») reçus depuis le front public
 * objectif2027.fr et traités dans le back-office présidentielle.
 *
 * Contrainte zéro-donnée : AUCUNE IP ni user-agent stocké. L'email est facultatif
 * (droit de réponse / suivi). L'anti-spam repose sur un honeypot + un rate-limit
 * éphémère (cache framework), jamais persisté.
 *
 * Rattachement souple par slug/ref (pas de morph) : les entités publiques sont
 * identifiées côté front par slug (candidat, thème) ou ref/uuid (argument) ; une
 * mesure n'a pas d'identifiant public stable → contexte libre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presidentielle_signalements', function (Blueprint $table) {
            $table->id();
            $table->string('type_incident', 40);              // cf. PresidentielleSignalement::TYPES_INCIDENT
            $table->text('description');
            $table->string('email', 255)->nullable();         // facultatif, RGPD

            // Contexte visé (facultatif, renseigné par le signaleur / la page).
            $table->string('candidat_slug', 160)->nullable();
            $table->string('theme_slug', 160)->nullable();
            $table->string('argument_ref', 64)->nullable();   // uuid d'argument (clé « ref » de l'export)
            $table->string('contexte_url', 1000)->nullable();
            $table->string('content_hash', 128)->nullable();  // snapshot de données visé

            $table->string('statut', 20)->default('nouveau'); // nouveau | en_cours | resolu | rejete
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('statut');
            $table->index('type_incident');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presidentielle_signalements');
    }
};
