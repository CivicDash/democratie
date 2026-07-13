<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal de modération transverse du domaine présidentielle (plan §5 / §8).
 * Polymorphe : couvre candidats, mesures, arguments, liens de scrutin, propositions.
 * Trace chaque transition de statut (qui, quand, pourquoi) — support du droit de réponse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presidentielle_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entite_type');           // morph : App\Models\ProgrammeMesure, ...
            $table->unsignedBigInteger('entite_id');
            $table->string('action', 40);            // prise_en_charge|validation|publication|depublication|double_validation|rejet|demande_complement
            $table->string('ancien_statut', 30)->nullable();
            $table->string('nouveau_statut', 30)->nullable();
            $table->text('commentaire')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['entite_type', 'entite_id']);
            $table->index('action');
            $table->index('moderator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presidentielle_moderation_logs');
    }
};
