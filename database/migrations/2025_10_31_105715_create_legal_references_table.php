<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_references', function (Blueprint $table) {
            $table->id();
            
            // Relation avec proposition de loi
            $table->foreignId('proposition_loi_id')
                ->constrained('propositions_loi')
                ->onDelete('cascade');
            
            // Référence juridique
            $table->string('reference_text', 50)->comment('Ex: L. 123-4');
            $table->string('code_name', 255)->comment('Ex: Code civil');
            $table->string('legifrance_id', 255)->nullable()->comment('ID Légifrance de l\'article');
            
            // Texte de l\'article
            $table->json('article_current_text')->nullable()->comment('Texte actuel de l\'article');
            $table->json('article_proposed_text')->nullable()->comment('Texte proposé (si modification)');
            
            // Métadonnées
            $table->text('context_description')->nullable()->comment('Description du contexte');
            $table->integer('position_start')->nullable()->comment('Position dans le texte (début)');
            $table->integer('position_end')->nullable()->comment('Position dans le texte (fin)');
            $table->string('matched_text', 500)->nullable()->comment('Texte correspondant exact');
            
            // Type d\'article
            $table->enum('article_type', ['legislative', 'regulatory', 'decree', 'order', 'unknown'])
                ->default('unknown')
                ->comment('Type d\'article (L, R, D, A)');
            
            // Statistiques
            $table->integer('jurisprudence_count')->default(0)->comment('Nombre de jurisprudences liées');
            $table->integer('related_articles_count')->default(0)->comment('Nombre d\'articles connexes');
            
            // Plages d\'articles
            $table->boolean('is_range')->default(false)->comment('Fait partie d\'une plage');
            $table->string('range_start', 50)->nullable()->comment('Début de plage');
            $table->string('range_end', 50)->nullable()->comment('Fin de plage');
            
            // Synchronisation
            $table->timestamp('last_synced_at')->nullable()->comment('Dernière synchro Légifrance');
            $table->boolean('sync_success')->default(false)->comment('Synchro réussie');
            $table->text('sync_error')->nullable()->comment('Erreur de synchro');
            
            $table->timestamps();
            
            // Index
            $table->index(['proposition_loi_id', 'sync_success']);
            $table->index(['reference_text', 'code_name']);
            $table->index('legifrance_id');
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_references');
    }
};
