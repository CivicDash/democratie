<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables pour les Élections Municipales 2026
 *
 * Architecture :
 * - listes_electorales : Une liste de candidats (ex: "Ensemble pour Strasbourg")
 * - candidats_municipaux : Les candidats individuels (tête de liste + colistiers)
 * - candidatures_documents : Justificatifs uploadés (récépissé préfecture, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // LISTES ÉLECTORALES
        // =====================================================================
        Schema::create('listes_electorales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Commune concernée
            $table->string('commune_code_insee', 5)->index();
            $table->string('commune_nom');
            $table->string('departement_code', 3)->index();

            // Informations de la liste
            $table->string('nom_liste'); // "Ensemble pour Strasbourg"
            $table->string('nuance_politique')->nullable(); // DVD, DVG, RN, LFI, etc.
            $table->string('parti_principal')->nullable(); // Parti politique principal
            $table->text('slogan')->nullable();
            $table->text('description')->nullable();

            // Visuel
            $table->string('logo_path')->nullable();
            $table->string('couleur_principale', 7)->default('#3B82F6'); // Hex color

            // Contact
            $table->string('email_contact')->nullable();
            $table->string('telephone_contact')->nullable();
            $table->string('site_web')->nullable();

            // Réseaux sociaux
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('tiktok_url')->nullable();

            // Programme
            $table->string('programme_pdf_path')->nullable();
            $table->text('resume_programme')->nullable(); // 500 caractères max

            // Statut de validation
            $table->enum('statut', [
                'brouillon',           // En cours de rédaction
                'en_attente',          // Soumis, en attente de validation
                'documents_requis',    // Documents manquants
                'en_verification',     // En cours de vérification par modérateur
                'valide',              // Validé par modération
                'rejete',              // Rejeté (fraude, doublon, etc.)
                'suspendu',            // Suspendu temporairement
            ])->default('brouillon');

            $table->text('motif_rejet')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();

            // Créateur (compte utilisateur qui a créé la liste)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Index composés
            $table->index(['commune_code_insee', 'statut']);
            $table->index(['departement_code', 'statut']);
        });

        // =====================================================================
        // CANDIDATS MUNICIPAUX
        // =====================================================================
        Schema::create('candidats_municipaux', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Lien avec la liste
            $table->foreignId('liste_id')->constrained('listes_electorales')->cascadeOnDelete();

            // Lien avec un compte utilisateur (optionnel)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Informations personnelles
            $table->string('civilite', 10)->nullable(); // M., Mme
            $table->string('nom');
            $table->string('prenom');
            $table->string('nom_usage')->nullable(); // Nom d'usage si différent
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('profession')->nullable();

            // Position sur la liste
            $table->unsignedInteger('position')->default(1); // 1 = tête de liste
            $table->boolean('est_tete_de_liste')->default(false);
            $table->string('fonction_visee')->nullable(); // "Maire", "1er adjoint", etc.

            // Photo
            $table->string('photo_path')->nullable();

            // Bio et parcours
            $table->text('biographie')->nullable(); // 1000 caractères max
            $table->json('parcours')->nullable(); // [{annee, description}]
            $table->json('engagements')->nullable(); // Thèmes prioritaires

            // Contact personnel (optionnel)
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();

            // Réseaux sociaux personnels
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();

            // Statut
            $table->enum('statut', [
                'actif',
                'demissionnaire',
                'invalide',
            ])->default('actif');

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['liste_id', 'position']);
            $table->index('est_tete_de_liste');
        });

        // =====================================================================
        // DOCUMENTS DE CANDIDATURE (justificatifs)
        // =====================================================================
        Schema::create('candidatures_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Lien polymorphe (peut être lié à une liste ou un candidat)
            $table->morphs('documentable');

            // Type de document
            $table->enum('type', [
                'recepisse_prefecture',    // Récépissé de dépôt en préfecture
                'piece_identite',          // CNI ou passeport
                'attestation_eligibilite', // Attestation d'éligibilité
                'declaration_candidature', // Déclaration de candidature
                'photo_officielle',        // Photo officielle
                'programme_pdf',           // Programme complet
                'autre',
            ]);

            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->string('mime_type', 100);
            $table->unsignedInteger('taille_octets');

            // Métadonnées
            $table->text('description')->nullable();
            $table->date('date_document')->nullable(); // Date sur le document
            $table->string('numero_reference')->nullable(); // Numéro de récépissé

            // Statut de vérification
            $table->enum('statut_verification', [
                'en_attente',
                'en_cours',
                'valide',
                'invalide',
                'expire',
            ])->default('en_attente');

            $table->text('commentaire_verification')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();

            // Uploader
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['documentable_type', 'documentable_id', 'type']);
            $table->index('statut_verification');
        });

        // =====================================================================
        // HISTORIQUE DES ACTIONS DE MODÉRATION
        // =====================================================================
        Schema::create('candidatures_moderation_logs', function (Blueprint $table) {
            $table->id();

            // Entité concernée (liste ou candidat)
            $table->morphs('moderatable');

            // Action effectuée
            $table->enum('action', [
                'creation',
                'soumission',
                'demande_documents',
                'validation',
                'rejet',
                'suspension',
                'reactivation',
                'modification',
                'commentaire',
            ]);

            $table->string('ancien_statut')->nullable();
            $table->string('nouveau_statut')->nullable();
            $table->text('commentaire')->nullable();
            $table->json('metadata')->nullable(); // Données supplémentaires

            // Modérateur
            $table->unsignedBigInteger('moderator_id')->nullable();
            $table->foreign('moderator_id')->references('id')->on('users')->nullOnDelete();

            $table->timestamp('created_at');

            // Index
            $table->index(['moderatable_type', 'moderatable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures_moderation_logs');
        Schema::dropIfExists('candidatures_documents');
        Schema::dropIfExists('candidats_municipaux');
        Schema::dropIfExists('listes_electorales');
    }
};
