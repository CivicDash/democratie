<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pour gérer le suivi d'élus par les citoyens
 * Permet une granularité fine sur les types de notifications
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elu_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Type d'élu (polymorphique simplifié)
            $table->string('elu_type', 20); // 'depute', 'senateur', 'maire', 'ministre'
            $table->string('elu_id', 50);   // UID pour députés, matricule pour sénateurs, etc.

            // Informations dénormalisées pour affichage rapide
            $table->string('elu_nom', 150)->nullable();
            $table->string('elu_photo_url', 500)->nullable();
            $table->string('elu_groupe', 100)->nullable();
            $table->string('elu_circonscription', 200)->nullable();

            // Préférences de notification granulaires
            $table->boolean('notify_votes')->default(true);           // Votes en séance
            $table->boolean('notify_interventions')->default(true);   // Questions, débats
            $table->boolean('notify_amendements')->default(false);    // Amendements déposés
            $table->boolean('notify_propositions')->default(true);    // Propositions de loi
            $table->boolean('notify_rapports')->default(false);       // Rapports
            $table->boolean('notify_commissions')->default(false);    // Activité en commission
            $table->boolean('notify_actualites')->default(true);      // Changements de fonction, etc.

            // Canal de notification
            $table->boolean('notify_site')->default(true);
            $table->boolean('notify_email')->default(false);

            // Fréquence pour les emails
            $table->string('email_frequency', 20)->default('instant'); // instant, daily, weekly

            // Statistiques de suivi
            $table->timestamp('followed_at')->useCurrent();
            $table->timestamp('last_activity_notified_at')->nullable();
            $table->unsignedInteger('notifications_received')->default(0);

            $table->timestamps();

            // Un utilisateur ne peut suivre qu'une fois le même élu
            $table->unique(['user_id', 'elu_type', 'elu_id']);

            // Index pour les requêtes fréquentes
            $table->index(['elu_type', 'elu_id']);
            $table->index(['user_id', 'notify_votes']);
            $table->index(['user_id', 'notify_email']);
        });

        // Table pour tracker les activités d'élus déjà notifiées
        Schema::create('elu_activity_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elu_follower_id')->constrained('elu_followers')->onDelete('cascade');

            // Type d'activité
            $table->string('activity_type', 30); // vote, intervention, amendement, proposition, etc.
            $table->string('activity_id', 100);  // ID de l'activité (scrutin_id, question_id, etc.)

            // Notification envoyée
            $table->foreignId('notification_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('email_sent')->default(false);

            $table->timestamp('notified_at')->useCurrent();

            // Éviter les doublons
            $table->unique(['elu_follower_id', 'activity_type', 'activity_id'], 'elu_activity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elu_activity_notifications');
        Schema::dropIfExists('elu_followers');
    }
};
