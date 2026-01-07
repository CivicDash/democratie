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
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('verifier_id')->constrained('users')->onDelete('cascade')
                ->comment('Vérificateur (rôle: journalist, ong, admin)');
            $table->enum('status', ['verified', 'rejected', 'needs_review'])->comment('Statut vérification');
            $table->text('notes')->nullable()->comment('Commentaires du vérificateur');
            $table->json('metadata')->nullable()->comment('Données supplémentaires (sources, etc.)');
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('verifier_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};

