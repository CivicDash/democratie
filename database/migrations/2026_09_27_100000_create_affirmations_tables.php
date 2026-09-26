<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * « Ce qu'on entend » : des affirmations du débat public confrontées aux données.
 *
 * C'est le seul endroit du site où nous rendons un verdict au lieu de citer. Le schéma
 * porte donc les garde-fous du cadre éditorial plutôt que de les laisser aux intentions :
 *
 *  - plusieurs verdicts par fiche, sans verdict « principal » : une fiche sur deux en a
 *    deux (« infirmé au sens littéral, reculs ciblés confirmés »), et n'en montrer qu'un
 *    trahirait la fiche ;
 *  - chaque constat porte son état de vérification. Une fiche n'est publiable que quand
 *    tous le sont — masquer les constats non vérifiés changerait l'équilibre de la fiche ;
 *  - un graphique est rattaché à la phrase chiffrée qui l'accompagne : il n'est jamais le
 *    seul support d'une conclusion ;
 *  - la coloration politique PERÇUE de l'affirmation sert au contrôle de symétrie interne.
 *    Elle ne sort jamais du back-office (l'exporteur ne la lit pas, un test le vérifie).
 *
 * Les séries Eurostat vivent dans leur propre table : une extraction mensuelle détecte les
 * révisions, et seule une série relue par un humain est publiée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affirmations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('election', 10)->default('2027');
            $table->string('slug', 120);
            $table->string('enonce', 300);
            $table->text('resume')->nullable();
            $table->foreignId('theme_id')->constrained('programme_themes')->restrictOnDelete();
            // Le cœur de l'affirmation est un jugement (« trop », « explose ») : les chiffres
            // n'en tranchent que la partie mesurable.
            $table->boolean('part_de_valeur')->default(false);
            $table->date('derniere_verification')->nullable();
            // INTERNE — gauche / droite / transversale. Jamais exportée.
            $table->string('coloration_percue', 20)->nullable();

            $table->string('statut_validation', 20)->default('detecte');
            $table->boolean('affiche_publiquement')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['election', 'slug']);
            $table->index(['election', 'affiche_publiquement']);
            $table->index('statut_validation');
        });

        Schema::create('affirmation_theme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affirmation_id')->constrained('affirmations')->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained('programme_themes')->cascadeOnDelete();
            $table->unique(['affirmation_id', 'theme_id']);
        });

        Schema::create('affirmation_verdicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affirmation_id')->constrained('affirmations')->cascadeOnDelete();
            $table->integer('ordre')->default(0);
            // Ce sur quoi porte le verdict, quand il y en a plusieurs.
            $table->string('portee', 300)->nullable();
            $table->string('verdict', 20);
            $table->timestamps();
        });

        Schema::create('affirmation_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affirmation_id')->constrained('affirmations')->cascadeOnDelete();
            $table->string('cle', 80);
            $table->string('producteur', 200);
            $table->string('titre', 500);
            // null + tâche de modération, jamais de placeholder : une source sans URL rend
            // la fiche impubliable, elle ne se déguise pas en source.
            $table->string('url', 1000)->nullable();
            $table->string('archive_url', 1000)->nullable();
            $table->string('categorie', 30);
            $table->date('date_publication')->nullable();
            $table->date('date_consultation')->nullable();
            $table->timestamps();

            $table->unique(['affirmation_id', 'cle']);
        });

        Schema::create('affirmation_constats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affirmation_id')->constrained('affirmations')->cascadeOnDelete();
            $table->string('section', 20);
            // Sous-titre libre : « Complément A — Les départs de France », « Leviers en cours ».
            $table->string('groupe', 200)->nullable();
            $table->integer('ordre')->default(0);
            $table->text('texte');
            // `verification` et non `fiabilite` : dans argument_sources, `fiabilite` désigne
            // la qualité d'une source. Ici c'est un état — vérifié ou non.
            $table->string('verification', 20)->default('a_verifier');
            // INTERNE — ce qui reste à contrôler (« valeur 2024 à reprendre du rapport »).
            $table->text('note_verification')->nullable();
            $table->foreignId('verifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verifie_at')->nullable();
            $table->timestamps();

            $table->index(['affirmation_id', 'section']);
        });

        Schema::create('affirmation_constat_source', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constat_id')->constrained('affirmation_constats')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('affirmation_sources')->cascadeOnDelete();
            $table->unique(['constat_id', 'source_id']);
        });

        Schema::create('affirmation_graphiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affirmation_id')->constrained('affirmations')->cascadeOnDelete();
            // La phrase chiffrée qui accompagne le graphique (annexe D.7).
            $table->foreignId('constat_id')->nullable()->constrained('affirmation_constats')->nullOnDelete();
            $table->integer('ordre')->default(0);
            $table->string('type', 20);
            $table->string('titre', 300);
            $table->string('sous_titre', 300)->nullable();
            $table->json('indicateurs');
            $table->json('options')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('eurostat_indicateurs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('titre', 300);
            $table->string('unite', 60);
            $table->text('note_methodo')->nullable();
            $table->string('pertinence', 20)->nullable();
            $table->json('sources');
            // Ce que le site montre : la dernière série relue.
            $table->json('series_publiees')->nullable();
            $table->date('extraction_publiee')->nullable();
            // Ce que la dernière extraction a trouvé, tant que personne ne l'a relu.
            $table->json('series_detectees')->nullable();
            $table->date('extraction_detectee')->nullable();
            $table->json('diff')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eurostat_indicateurs');
        Schema::dropIfExists('affirmation_graphiques');
        Schema::dropIfExists('affirmation_constat_source');
        Schema::dropIfExists('affirmation_constats');
        Schema::dropIfExists('affirmation_sources');
        Schema::dropIfExists('affirmation_verdicts');
        Schema::dropIfExists('affirmation_theme');
        Schema::dropIfExists('affirmations');
    }
};
