<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les champs pour la gestion des suspensions et bannissements
 */
return new class extends Migration
{
    public function up(): void
    {
        // Vérifier les colonnes existantes avant le callback
        $hasAccountStatus = Schema::hasColumn('users', 'account_status');
        $hasSuspendedAt = Schema::hasColumn('users', 'suspended_at');
        $hasSuspendedUntil = Schema::hasColumn('users', 'suspended_until');
        $hasSuspensionReason = Schema::hasColumn('users', 'suspension_reason');
        $hasSuspendedBy = Schema::hasColumn('users', 'suspended_by');
        $hasSuspensionCount = Schema::hasColumn('users', 'suspension_count');
        $hasDeletedAt = Schema::hasColumn('users', 'deleted_at');

        Schema::table('users', function (Blueprint $table) use (
            $hasAccountStatus, $hasSuspendedAt, $hasSuspendedUntil,
            $hasSuspensionReason, $hasSuspendedBy, $hasSuspensionCount, $hasDeletedAt
        ) {
            // Statut du compte
            if (! $hasAccountStatus) {
                $table->string('account_status', 20)->default('active'); // active, suspended, banned, deleted
            }

            // Suspension temporaire
            if (! $hasSuspendedAt) {
                $table->timestamp('suspended_at')->nullable();
            }
            if (! $hasSuspendedUntil) {
                $table->timestamp('suspended_until')->nullable(); // null = permanent (banned)
            }
            if (! $hasSuspensionReason) {
                $table->text('suspension_reason')->nullable();
            }
            if (! $hasSuspendedBy) {
                $table->foreignId('suspended_by')->nullable()->constrained('users')->onDelete('set null');
            }

            // Historique des sanctions (pour appel)
            if (! $hasSuspensionCount) {
                $table->integer('suspension_count')->default(0);
            }

            // Soft delete pour suppression de compte
            if (! $hasDeletedAt) {
                $table->softDeletes();
            }
        });

        // Table d'historique des sanctions
        if (! Schema::hasTable('user_sanctions')) {
            Schema::create('user_sanctions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('moderator_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('type', 20); // warning, suspension, ban, unban
                $table->text('reason');
                $table->integer('duration_days')->nullable(); // null = permanent
                $table->timestamp('starts_at');
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('appeal_message')->nullable();
                $table->string('appeal_status', 20)->nullable(); // pending, accepted, rejected
                $table->foreignId('appeal_reviewed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('appeal_reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
                $table->index(['type', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        $hasSuspendedBy = Schema::hasColumn('users', 'suspended_by');

        Schema::table('users', function (Blueprint $table) use ($hasSuspendedBy) {
            if ($hasSuspendedBy) {
                $table->dropForeign(['suspended_by']);
            }
        });

        $columns = ['account_status', 'suspended_at', 'suspended_until', 'suspension_reason', 'suspended_by', 'suspension_count'];
        foreach ($columns as $col) {
            if (Schema::hasColumn('users', $col)) {
                Schema::table('users', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        Schema::dropIfExists('user_sanctions');
    }
};
