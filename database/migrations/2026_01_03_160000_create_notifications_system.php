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
        // Ajouter les colonnes manquantes à la table notifications existante
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (! Schema::hasColumn('notifications', 'acknowledged_at')) {
                    $table->timestamp('acknowledged_at')->nullable()->after('read_at');
                }
                if (! Schema::hasColumn('notifications', 'actioned_at')) {
                    $table->timestamp('actioned_at')->nullable()->after('acknowledged_at');
                }
                if (! Schema::hasColumn('notifications', 'action_type')) {
                    $table->string('action_type')->nullable()->after('actioned_at');
                }
                if (! Schema::hasColumn('notifications', 'category')) {
                    $table->string('category', 50)->nullable()->after('type');
                }
            });
        }

        // Préférences de notification utilisateur
        if (! Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('channel', 50); // email, site, push
                $table->string('category', 100); // interpellation, response, mention, vote, moderation
                $table->boolean('enabled')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'channel', 'category']);
            });
        }

        // Historique des emails envoyés (pour éviter spam)
        if (! Schema::hasTable('notification_emails')) {
            Schema::create('notification_emails', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('notification_id')->nullable();
                $table->string('type', 100);
                $table->string('status', 20)->default('pending'); // pending, sent, failed
                $table->timestamp('sent_at')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'type', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_emails');
        Schema::dropIfExists('notification_preferences');

        // Retirer les colonnes ajoutées
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $columns = ['acknowledged_at', 'actioned_at', 'action_type', 'category'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('notifications', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
