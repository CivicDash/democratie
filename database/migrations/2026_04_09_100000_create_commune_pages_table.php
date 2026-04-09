<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_pages', function (Blueprint $table) {
            $table->id();
            $table->string('code_insee', 5)->unique();
            $table->foreignId('ville_id')->constrained('villes')->cascadeOnDelete();

            // Personnalisation visuelle
            $table->string('image_couverture_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('couleur_primaire', 7)->default('#1e40af');
            $table->string('couleur_secondaire', 7)->default('#3b82f6');
            $table->text('description_courte')->nullable();
            $table->text('mot_du_maire')->nullable();

            // Contact mairie (enrichi via API service-public.fr)
            $table->string('adresse_mairie')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email_mairie')->nullable();
            $table->string('site_officiel')->nullable();
            $table->jsonb('horaires_ouverture')->nullable();

            // Réseaux sociaux
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('linkedin_url')->nullable();

            // Fonctionnalités activées
            $table->boolean('actus_actives')->default(false);
            $table->boolean('evenements_actifs')->default(false);
            $table->boolean('forum_actif')->default(true);
            $table->boolean('notifications_actives')->default(false);

            // Statut de la page
            $table->string('statut', 20)->default('auto_generee');
            $table->foreignId('reclamee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reclamee_at')->nullable();
            $table->foreignId('verifiee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verifiee_at')->nullable();
            $table->string('verification_niveau', 20)->nullable();
            $table->string('verification_code', 6)->nullable();
            $table->timestamp('verification_code_expire_at')->nullable();

            // Analytics
            $table->unsignedBigInteger('vues_totales')->default(0);
            $table->unsignedBigInteger('abonnes_count')->default(0);

            $table->timestamps();

            $table->index('statut');
            $table->index('ville_id');
        });

        DB::statement("ALTER TABLE commune_pages ADD CONSTRAINT commune_pages_statut_check CHECK (statut IN ('auto_generee', 'reclamee', 'active', 'suspendue'))");
        DB::statement("ALTER TABLE commune_pages ADD CONSTRAINT commune_pages_verification_niveau_check CHECK (verification_niveau IS NULL OR verification_niveau IN ('email_officiel', 'domaine_email', 'manuelle'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_pages');
    }
};
