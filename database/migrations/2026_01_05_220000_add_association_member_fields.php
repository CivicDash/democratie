<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajout des champs pour les membres de l'association Civis-Consilium
 * et système de modération des photos de profil
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Statut membre association Civis-Consilium (is_association_member existe déjà)
            if (!Schema::hasColumn('users', 'association_member_since')) {
                $table->timestamp('association_member_since')->nullable()->after('is_association_member');
            }
            if (!Schema::hasColumn('users', 'association_member_id')) {
                $table->string('association_member_id', 50)->nullable()->after('association_member_since'); // ID Dolibarr ou interne
            }
            
            // Modération photo de profil
            if (!Schema::hasColumn('users', 'profile_photo_status')) {
                $table->enum('profile_photo_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('profile_photo_path');
            }
            if (!Schema::hasColumn('users', 'profile_photo_rejection_reason')) {
                $table->text('profile_photo_rejection_reason')->nullable()->after('profile_photo_status');
            }
            if (!Schema::hasColumn('users', 'profile_photo_submitted_at')) {
                $table->timestamp('profile_photo_submitted_at')->nullable()->after('profile_photo_rejection_reason');
            }
            if (!Schema::hasColumn('users', 'profile_photo_moderated_at')) {
                $table->timestamp('profile_photo_moderated_at')->nullable()->after('profile_photo_submitted_at');
            }
            if (!Schema::hasColumn('users', 'profile_photo_moderated_by')) {
                $table->foreignId('profile_photo_moderated_by')->nullable()->constrained('users')->nullOnDelete()->after('profile_photo_moderated_at');
            }
            
            // Email visible pour admin/modo (pour export Dolibarr)
            if (!Schema::hasColumn('users', 'email_visible_to_admin')) {
                $table->boolean('email_visible_to_admin')->default(true)->after('email');
            }
        });

        // Table pour l'historique des modérations de photos
        if (!Schema::hasTable('profile_photo_moderations')) {
            Schema::create('profile_photo_moderations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('photo_path', 500);
                $table->enum('action', ['submitted', 'approved', 'rejected']);
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_photo_moderated_by')) {
                $table->dropForeign(['profile_photo_moderated_by']);
            }
            
            $columns = [
                'association_member_since',
                'association_member_id',
                'profile_photo_status',
                'profile_photo_rejection_reason',
                'profile_photo_submitted_at',
                'profile_photo_moderated_at',
                'profile_photo_moderated_by',
                'email_visible_to_admin',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('profile_photo_moderations');
    }
};
