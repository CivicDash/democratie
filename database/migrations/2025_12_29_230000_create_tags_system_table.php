<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Système de tags hybride pour CivicDash
     * Étend la table tags existante avec de nouvelles colonnes
     */
    public function up(): void
    {
        // Étendre la table tags existante
        Schema::table('tags', function (Blueprint $table) {
            // Renommer 'name' en 'nom' pour cohérence FR (si name existe)
            if (Schema::hasColumn('tags', 'name') && !Schema::hasColumn('tags', 'nom')) {
                $table->renameColumn('name', 'nom');
            }
            
            // Renommer 'color' en 'couleur' (si color existe)
            if (Schema::hasColumn('tags', 'color') && !Schema::hasColumn('tags', 'couleur')) {
                $table->renameColumn('color', 'couleur');
            }
            
            // Renommer 'icon' en 'icone' (si icon existe)
            if (Schema::hasColumn('tags', 'icon') && !Schema::hasColumn('tags', 'icone')) {
                $table->renameColumn('icon', 'icone');
            }
        });

        // Ajouter les nouvelles colonnes
        Schema::table('tags', function (Blueprint $table) {
            if (!Schema::hasColumn('tags', 'type')) {
                $table->string('type', 20)->default('keyword')->after('icone');
            }
            if (!Schema::hasColumn('tags', 'source')) {
                $table->string('source', 20)->default('user')->after('type');
            }
            if (!Schema::hasColumn('tags', 'validated')) {
                $table->boolean('validated')->default(false)->after('source');
            }
            if (!Schema::hasColumn('tags', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('validated');
            }
            if (!Schema::hasColumn('tags', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
        });

        // Ajouter les index
        Schema::table('tags', function (Blueprint $table) {
            $table->index(['type', 'validated'], 'tags_type_validated_idx');
        });

        // Table pivot pour les lois
        if (!Schema::hasTable('loi_tag')) {
            Schema::create('loi_tag', function (Blueprint $table) {
                $table->id();
                $table->string('loi_loicod', 20); // FK vers senat_dosleg_loi
                $table->foreignId('tag_id')->constrained()->onDelete('cascade');
                
                // Source de l'association
                $table->string('source', 20)->default('official');
                $table->float('confidence', 3, 2)->nullable(); // 0.00 - 1.00 pour IA
                
                // Qui a suggéré (si user)
                $table->foreignId('suggested_by')->nullable();
                $table->timestamp('suggested_at')->nullable();
                
                // Validation
                $table->boolean('validated')->default(true);
                $table->foreignId('validated_by')->nullable();
                
                $table->timestamps();
                
                $table->unique(['loi_loicod', 'tag_id']);
                $table->index('source');
            });
        }

        // Table pivot pour les textes JO
        if (!Schema::hasTable('texte_jo_tag')) {
            Schema::create('texte_jo_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('texte_jo_id')->constrained('textes_jo')->onDelete('cascade');
                $table->foreignId('tag_id')->constrained()->onDelete('cascade');
                $table->string('source', 20)->default('ai');
                $table->float('confidence', 3, 2)->nullable();
                $table->foreignId('suggested_by')->nullable();
                $table->boolean('validated')->default(false);
                $table->timestamps();
                
                $table->unique(['texte_jo_id', 'tag_id']);
            });
        }

        // Table pivot pour les topics/discussions du forum
        if (!Schema::hasTable('topic_tag')) {
            Schema::create('topic_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('topic_id')->constrained()->onDelete('cascade');
                $table->foreignId('tag_id')->constrained()->onDelete('cascade');
                $table->string('source', 20)->default('user');
                $table->float('confidence', 3, 2)->nullable();
                $table->timestamps();
                
                $table->unique(['topic_id', 'tag_id']);
            });
        }

        // Suggestions de tags en attente de validation
        if (!Schema::hasTable('tag_suggestions')) {
            Schema::create('tag_suggestions', function (Blueprint $table) {
                $table->id();
                $table->string('nom', 100);
                $table->text('justification')->nullable();
                $table->foreignId('suggested_by')->constrained('users')->onDelete('cascade');
                
                // Contexte de la suggestion
                $table->string('taggable_type');
                $table->string('taggable_id');
                
                // Statut
                $table->string('status', 20)->default('pending');
                $table->foreignId('reviewed_by')->nullable();
                $table->text('review_comment')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                
                $table->timestamps();
                
                $table->index(['status', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_suggestions');
        Schema::dropIfExists('topic_tag');
        Schema::dropIfExists('texte_jo_tag');
        Schema::dropIfExists('loi_tag');
        
        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('tags_type_validated_idx');
            
            if (Schema::hasColumn('tags', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
            if (Schema::hasColumn('tags', 'validated_by')) {
                $table->dropColumn('validated_by');
            }
            if (Schema::hasColumn('tags', 'validated')) {
                $table->dropColumn('validated');
            }
            if (Schema::hasColumn('tags', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('tags', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
