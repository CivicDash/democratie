<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refactorisation du schéma Gouvernement pour supporter :
     * - Historisation des gouvernements (numérotés)
     * - Personnes politiques réutilisables
     * - Postes ministériels avec dates
     */
    public function up(): void
    {
        // 1. Table des personnes politiques (ministres, premiers ministres, etc.)
        Schema::create('personnes_politiques', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            // Identité
            $table->string('civilite')->nullable(); // M., Mme
            $table->string('prenom');
            $table->string('nom');
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->date('date_deces')->nullable();

            // Profession et parcours
            $table->string('profession')->nullable();
            $table->text('biographie')->nullable();

            // Affiliation politique
            $table->string('parti_politique')->nullable();
            $table->string('nuance_politique')->nullable();

            // Photos et liens
            $table->string('photo_url')->nullable();
            $table->string('photo_officielle_url')->nullable();
            $table->string('wikipedia_url')->nullable();
            $table->text('wikipedia_extract')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('site_web')->nullable();

            // Liens avec les tables d'élus existantes
            $table->string('uid_an')->nullable()->index(); // Lien vers acteurs_an
            $table->string('uid_senat')->nullable()->index(); // Lien vers senateurs
            $table->unsignedBigInteger('maire_id')->nullable()->index(); // Lien vers maires

            // Métadonnées
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['nom', 'prenom']);
        });

        // 2. Ajouter le numéro au gouvernement
        Schema::table('gouvernements', function (Blueprint $table) {
            // Le numéro existe peut-être déjà, on vérifie
            if (! Schema::hasColumn('gouvernements', 'numero')) {
                $table->integer('numero')->nullable()->after('id'); // 48, 47, 46...
            }
            if (! Schema::hasColumn('gouvernements', 'suffixe')) {
                $table->string('suffixe')->nullable()->after('numero'); // "", "II", "III"
            }
        });

        // 3. Table de liaison : postes ministériels
        Schema::create('postes_ministeriels', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('personne_id')->constrained('personnes_politiques')->onDelete('cascade');
            $table->foreignId('gouvernement_id')->constrained('gouvernements')->onDelete('cascade');
            $table->foreignId('ministere_id')->nullable()->constrained('ministeres')->onDelete('set null');

            // Poste
            $table->string('fonction'); // Titre complet du poste
            $table->enum('type_fonction', [
                'president',
                'premier_ministre',
                'ministre_etat',
                'ministre',
                'ministre_delegue',
                'secretaire_etat',
                'haut_commissaire',
            ])->default('ministre');
            $table->integer('ordre')->default(99); // Ordre protocolaire

            // Dates
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);

            // Décret de nomination
            $table->string('decret_nomination')->nullable();
            $table->date('date_decret')->nullable();

            // Métadonnées
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Index pour les recherches
            $table->index(['gouvernement_id', 'actif']);
            $table->index(['personne_id', 'date_debut']);
            $table->unique(['personne_id', 'gouvernement_id', 'fonction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postes_ministeriels');
        Schema::dropIfExists('personnes_politiques');

        Schema::table('gouvernements', function (Blueprint $table) {
            if (Schema::hasColumn('gouvernements', 'suffixe')) {
                $table->dropColumn('suffixe');
            }
        });
    }
};
