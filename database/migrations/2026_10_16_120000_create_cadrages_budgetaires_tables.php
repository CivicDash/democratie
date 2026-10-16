<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cadrage budgétaire : une grille commune pour tous les candidats, et une ligne de
 * référence officielle (PLF ou loi de finances, comptes INSEE, avis du HCFP).
 *
 * Un cadrage = un document chiffré d'un candidat (premier cadrage, budget de première
 * année, programme chiffré complet). La référence est un cadrage SANS candidat. Chaque
 * ligne porte une valeur telle que la source la donne — jamais convertie, jamais
 * additionnée — avec sa citation, son repérage et, le cas échéant, son locuteur.
 *
 * Seules les lignes vérifiées par un humain paraissent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadrages_budgetaires', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('election', 10)->default('2027');
            // NULL = ligne de référence officielle.
            $t->foreignId('candidat_id')->nullable()->constrained('candidats_presidentielle')->cascadeOnDelete();
            $t->string('nature', 30);
            $t->string('titre', 300);
            // Date de présentation : seul critère d'ordre, donc d'export déterministe.
            $t->date('date_source');
            $t->foreignId('ingestion_document_id')->nullable()->constrained('ingestion_documents')->restrictOnDelete();
            $t->foreignId('programme_document_id')->nullable()->constrained('programme_documents')->restrictOnDelete();
            // La liste « non précisé » de l'ingestion : traçabilité interne, jamais exportée.
            $t->jsonb('rubriques_non_precisees')->nullable();
            $t->text('note_methodologique')->nullable();
            $t->string('statut_validation', 20)->default('detecte');
            $t->boolean('affiche_publiquement')->default(false);
            $t->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('valide_at')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['candidat_id', 'affiche_publiquement', 'statut_validation']);
            $t->index(['election', 'nature']);
        });

        DB::statement("ALTER TABLE cadrages_budgetaires ADD CONSTRAINT cadrages_budgetaires_reference_sans_candidat
            CHECK ((candidat_id IS NULL) = (nature = 'reference_officielle'))");
        DB::statement('ALTER TABLE cadrages_budgetaires ADD CONSTRAINT cadrages_budgetaires_un_seul_document
            CHECK (ingestion_document_id IS NULL OR programme_document_id IS NULL)');

        Schema::create('cadrage_lignes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cadrage_id')->constrained('cadrages_budgetaires')->cascadeOnDelete();
            $t->string('rubrique', 30);
            $t->string('libelle', 300)->nullable();
            $t->decimal('montant', 14, 2)->nullable();
            $t->decimal('montant_max', 14, 2)->nullable();      // fourchette « de 30 à 40 »
            $t->string('unite', 10);
            $t->string('qualificatif', 10)->nullable();         // environ | au_moins | plus_de | au_plus | moins_de
            $t->string('portee', 10)->nullable();               // annuelle | cumulee — null = non précisé
            $t->smallInteger('annee')->nullable();
            $t->text('valeur_texte')->nullable();               // la valeur telle que dite ou écrite
            $t->text('citation')->nullable();
            $t->string('reperage', 100)->nullable();            // hh:mm:ss ou « p. 12 »
            $t->string('locuteur', 150)->nullable();
            $t->string('role_locuteur', 200)->nullable();
            $t->string('qualite_locuteur', 20)->nullable();
            $t->foreignId('proposition_id')->nullable()->constrained('ingestion_propositions')->nullOnDelete();
            // Source propre à la ligne quand elle diffère de l'en-tête du cadrage.
            $t->foreignId('ingestion_document_id')->nullable()->constrained('ingestion_documents')->restrictOnDelete();
            // Ligne de référence : producteur et publication.
            $t->string('source_producteur', 120)->nullable();
            $t->string('source_titre', 300)->nullable();
            $t->string('source_url', 1000)->nullable();
            $t->date('source_date')->nullable();
            // Notes de l'ingestion et citations voisines : aide au modérateur, jamais exportées.
            $t->text('note')->nullable();
            $t->jsonb('autres_citations')->nullable();
            $t->boolean('verbatim_verifie')->default(false);
            $t->string('verification', 12)->default('a_verifier');
            $t->foreignId('verifie_par')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verifie_at')->nullable();
            $t->integer('ordre')->default(0);
            $t->timestamps();

            $t->index(['cadrage_id', 'rubrique']);
        });

        // Le cadrage d'un candidat n'admet que lui et son équipe de campagne : la parole
        // d'un parti n'y entre pas. Un nom va toujours avec sa qualité.
        DB::statement("ALTER TABLE cadrage_lignes ADD CONSTRAINT cadrage_lignes_qualite_equipe
            CHECK (qualite_locuteur IS NULL OR qualite_locuteur = 'equipe_campagne')");
        DB::statement('ALTER TABLE cadrage_lignes ADD CONSTRAINT cadrage_lignes_locuteur_qualifie
            CHECK ((locuteur IS NULL) = (qualite_locuteur IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('cadrage_lignes');
        Schema::dropIfExists('cadrages_budgetaires');
    }
};
