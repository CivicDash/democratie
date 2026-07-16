<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte de l'argumentaire (plan §4, contrat v1.2) : les arguments deviennent des
 * FAITS SOURCÉS AUTONOMES, réutilisables sur plusieurs mesures — y compris opposées.
 *
 *  - `controverses` : regroupe les arguments d'une même question de fond (ex. âge de
 *    départ à la retraite) et porte la note méthodologique affichée en tête du dépliant.
 *  - `arguments` : perd `mesure_id` et `sens` (le sens n'est plus une propriété du fait),
 *    gagne `controverse_id`.
 *  - `argument_mesure_liens` : pivot portant LE SENS (pour|contre) et la `note_contextuelle`
 *    (pourquoi ce fait joue dans ce sens pour CETTE mesure). Chaque liaison a son propre
 *    cycle de validation ; une liaison « contre » exige une double validation.
 *
 * La base ne contient aucun argument (vérifié) : la refonte est faite sans migration de
 * données. Le `down()` restaure le schéma 1.x (sans données).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Controverses (regroupement transverse d'arguments).
        Schema::create('controverses', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('titre', 255);
            $table->foreignId('theme_id')->nullable()->constrained('programme_themes')->nullOnDelete();
            $table->text('note_methodologique')->nullable(); // pourquoi des études sérieuses divergent
            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false)->index();
            $table->integer('ordre')->default(0);

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. `arguments` : fait autonome. On retire mesure_id + sens, on ajoute controverse_id.
        Schema::table('arguments', function (Blueprint $table) {
            $table->dropIndex(['mesure_id', 'sens']);          // index composite obsolète
            $table->dropForeign(['mesure_id']);
            $table->dropColumn(['mesure_id', 'sens']);
            $table->foreignId('controverse_id')->nullable()->after('uuid')
                ->constrained('controverses')->nullOnDelete();
        });

        // 3. Pivot argument ↔ mesure : le SENS et la note contextuelle vivent ici,
        //    avec un cycle de validation propre (double validation pour « contre »).
        Schema::create('argument_mesure_liens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('argument_id')->constrained('arguments')->cascadeOnDelete();
            // Nullable : une liaison auto-détectée peut rester « à résoudre » (mesure non
            // encore appariée) — on conserve alors la cible proposée en clair.
            $table->foreignId('mesure_id')->nullable()->constrained('programme_mesures')->cascadeOnDelete();

            $table->string('sens', 10);                        // pour | contre
            $table->text('note_contextuelle')->nullable();     // OBLIGATOIRE à la publication

            // Cible proposée par l'auto-match (résolution humaine au BO si mesure_id null).
            $table->string('candidat_slug_propose', 160)->nullable();
            $table->text('mesure_proposee')->nullable();
            $table->string('source_detection', 20)->default('manuel'); // manuel | suggestion_auto
            $table->decimal('detection_confidence', 4, 3)->nullable();

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false);

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('double_valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('double_valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['argument_id', 'mesure_id', 'sens']); // pas deux fois le même lien
            $table->index(['mesure_id', 'sens']);
            $table->index(['affiche_publiquement', 'statut_validation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('argument_mesure_liens');

        Schema::table('arguments', function (Blueprint $table) {
            $table->dropForeign(['controverse_id']);
            $table->dropColumn('controverse_id');
            $table->foreignId('mesure_id')->nullable()->after('uuid')
                ->constrained('programme_mesures')->cascadeOnDelete();
            $table->string('sens', 10)->nullable()->after('mesure_id');
            $table->index(['mesure_id', 'sens']);
        });

        Schema::dropIfExists('controverses');
    }
};
