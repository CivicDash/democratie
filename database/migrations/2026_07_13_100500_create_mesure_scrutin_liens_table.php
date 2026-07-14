<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « Cohérence discours / actes » (plan §4 bis).
 * Lie une mesure de campagne à un scrutin réel (AN/Sénat/amendement) déjà en base.
 * Règles bloquantes : jamais de publication auto ; explication humaine obligatoire
 * avant publication ; attribution honnête (vote_personnel vs position_groupe vs absence).
 * `scrutin_ref` = référence polymorphe (id/uid) vers la table indiquée par `scrutin_type`.
 * Champs dénormalisés (scrutin_*) pour l'export JSON et le lien vers civicdash.fr.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesure_scrutin_liens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('mesure_id')
                ->constrained('programme_mesures')->cascadeOnDelete();

            $table->string('scrutin_type', 30);        // scrutin_an | scrutin_senat | amendement_an | amendement_senat
            $table->string('scrutin_ref', 100);        // FK polymorphe (id ou uid) vers la table existante

            $table->string('sens_lien', 20);           // coherent | contradictoire | contexte
            $table->string('niveau', 20);              // vote_personnel | position_groupe | absence
            $table->text('explication')->nullable();   // OBLIGATOIRE avant publication (contrôlé à l'export)

            // Champs dénormalisés pour l'export / le front
            $table->date('scrutin_date')->nullable();
            $table->string('scrutin_intitule', 500)->nullable();
            $table->string('scrutin_resultat', 100)->nullable();
            $table->string('scrutin_url', 500)->nullable();

            $table->string('source_detection', 30)->default('manuel'); // manuel | suggestion_auto
            $table->decimal('detection_confidence', 3, 2)->nullable();

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['mesure_id', 'sens_lien']);
            $table->index(['scrutin_type', 'scrutin_ref']);
            $table->index('statut_validation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesure_scrutin_liens');
    }
};
