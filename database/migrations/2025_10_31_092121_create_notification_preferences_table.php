<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Préférences par type de notification
            $table->boolean('notify_new_reply')->default(true);
            $table->boolean('notify_new_vote_on_topic')->default(true);
            $table->boolean('notify_legislative_vote_result')->default(true);
            $table->boolean('notify_mention')->default(true);
            $table->boolean('notify_vote_on_my_proposal')->default(true);
            $table->boolean('notify_new_thematique_proposition')->default(false);
            $table->boolean('notify_system_announcement')->default(true);
            $table->boolean('notify_followed_topic_update')->default(true);
            $table->boolean('notify_followed_legislation_update')->default(true);
            
            // Canaux de notification
            $table->boolean('channel_in_app')->default(true);
            $table->boolean('channel_email')->default(false);
            
            // Fréquence pour les emails
            $table->string('email_frequency')->default('instant'); // instant, daily, weekly, never
            
            // Heures calmes (ne pas notifier)
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            
            // Groupement des notifications
            $table->boolean('group_similar_notifications')->default(true);
            
            $table->timestamps();
            
            // Un seul enregistrement par utilisateur
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
