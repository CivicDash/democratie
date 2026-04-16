<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de suivi des imports de données
     */
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('command'); // 'import:reunions-an', 'import:agenda-senat', etc.
            $table->string('source'); // 'an', 'senat', 'elysee', 'hatvp', 'wikipedia'
            $table->string('status'); // 'running', 'success', 'failed', 'partial'

            // Statistiques
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);
            $table->unsignedInteger('errors_count')->default(0);

            // Timing
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            // Détails
            $table->json('options')->nullable(); // Options passées à la commande
            $table->text('error_message')->nullable(); // Dernier message d'erreur
            $table->json('error_details')->nullable(); // Stack trace ou détails

            // Utilisateur qui a lancé (si manuel)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            // Index
            $table->index('command');
            $table->index('source');
            $table->index('status');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
