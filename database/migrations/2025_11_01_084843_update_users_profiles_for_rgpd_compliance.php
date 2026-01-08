<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration RGPD & FranceConnect+ Compliance
 * 
 * Objectifs :
 * 1. Anonymiser users.name → Utiliser display_name par défaut
 * 2. Chiffrer données sensibles FranceConnect+ dans profiles
 * 3. Ajouter champ franceconnect_sub dans users (identifiant unique FC+)
 * 4. Supprimer stockage JSON en clair des données FC+
 * 5. Ajouter flag is_public_figure pour distinguer anonymes/transparents
 * 
 * Conformité :
 * - RGPD Art. 5 (minimisation des données)
 * - RGPD Art. 32 (chiffrement données sensibles)
 * - FranceConnect+ : Chiffrement JWE/JWS tokens
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ajouter champ franceconnect_sub dans users (identifiant unique FC+)
        Schema::table('users', function (Blueprint $table) {
            $table->string('franceconnect_sub')->nullable()->unique()->after('email')
                ->comment('Identifiant unique FranceConnect+ (sub claim)');
        });

        // 2. Modifier profiles pour conformité RGPD
        Schema::table('profiles', function (Blueprint $table) {
            // Flag pour distinguer comptes publics (journaliste, personnalité) des citoyens anonymes
            $table->boolean('is_public_figure')->default(false)->after('is_verified')
                ->comment('Compte public (nom réel visible) vs citoyen anonyme');
            
            // Données FranceConnect+ chiffrées (si nécessaire pour admin/audit)
            // Note : En production, utiliser Laravel Encryption ou Vault
            $table->text('encrypted_fc_data')->nullable()->after('verified_at')
                ->comment('Données FC+ chiffrées (birthdate, gender, etc.) pour audit uniquement');
            
            // Nom réel chiffré (accessible uniquement admin avec clé déchiffrement)
            $table->text('encrypted_real_name')->nullable()->after('encrypted_fc_data')
                ->comment('Nom réel chiffré (Prénom + Nom) - Admin uniquement');
            
            // Email réel chiffré (backup si different de users.email)
            $table->text('encrypted_real_email')->nullable()->after('encrypted_real_name')
                ->comment('Email réel chiffré - Admin uniquement');
            
            // Supprimer les anciens champs non-chiffrés si ils existent
            // Note : given_name, family_name, franceconnect_data seront migrés vers encrypted_fc_data
            if (Schema::hasColumn('profiles', 'given_name')) {
                $table->dropColumn(['given_name', 'family_name']);
            }
            if (Schema::hasColumn('profiles', 'birthdate')) {
                $table->dropColumn(['birthdate', 'gender', 'birthplace', 'birthcountry']);
            }
            if (Schema::hasColumn('profiles', 'franceconnect_data')) {
                $table->dropColumn('franceconnect_data');
            }
        });

        // 3. Créer table user_consents (RGPD Art. 7)
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('consent_type', [
                'data_processing',      // Traitement données personnelles
                'cookies',              // Cookies non essentiels
                'notifications',        // Notifications email/push
                'franceconnect_data',   // Collecte données FranceConnect+
                'analytics',            // Analytics anonymes
            ])->comment('Type de consentement');
            $table->boolean('is_granted')->default(false)->comment('Consentement accordé');
            $table->string('policy_version')->comment('Version politique confidentialité acceptée');
            $table->text('consent_proof')->nullable()->comment('Preuve consentement (IP, user-agent, timestamp)');
            $table->timestamp('granted_at')->nullable()->comment('Date consentement');
            $table->timestamp('revoked_at')->nullable()->comment('Date révocation');
            $table->timestamps();
            
            $table->index(['user_id', 'consent_type']);
            $table->index(['consent_type', 'is_granted']);
        });

        // 4. Créer table policy_versions (versioning politique confidentialité)
        Schema::create('policy_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique()->comment('Ex: 1.0.0, 1.1.0');
            $table->enum('policy_type', ['privacy', 'terms', 'cookies'])
                ->comment('Type de politique');
            $table->text('content_summary')->comment('Résumé changements majeurs');
            $table->string('file_path')->comment('Chemin fichier Markdown complet');
            $table->boolean('is_current')->default(false)->comment('Version active');
            $table->timestamp('effective_at')->comment('Date entrée en vigueur');
            $table->timestamps();
            
            $table->index(['policy_type', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('franceconnect_sub');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'is_public_figure',
                'encrypted_fc_data',
                'encrypted_real_name',
                'encrypted_real_email',
            ]);
        });

        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('policy_versions');
    }
};
