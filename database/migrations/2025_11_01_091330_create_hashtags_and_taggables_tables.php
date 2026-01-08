<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration système hashtags (style Twitter)
 * 
 * Permet de taguer posts et topics avec #motclé
 * Navigation thématique organique + découverte de contenu
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table hashtags (uniques, normalisés)
        Schema::create('hashtags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->comment('Ex: climat (lowercase, no accents)');
            $table->string('display_name')->comment('Ex: Climat (original case)');
            $table->integer('usage_count')->default(0)->comment('Nombre total utilisations');
            $table->boolean('is_trending')->default(false)->comment('Hashtag tendance (24h)');
            $table->boolean('is_official')->default(false)->comment('Hashtag officiel thématique');
            $table->boolean('is_moderated')->default(false)->comment('Nécessite modération (sensible)');
            $table->text('description')->nullable()->comment('Description si hashtag officiel');
            $table->timestamp('last_used_at')->nullable()->comment('Dernière utilisation');
            $table->timestamps();
            
            $table->index('slug');
            $table->index(['is_trending', 'usage_count']);
            $table->index('last_used_at');
        });

        // Pivot polymorphic (posts, topics, etc.)
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hashtag_id')->constrained()->onDelete('cascade');
            $table->morphs('taggable', 'taggables_morph_index'); // Custom index name
            $table->timestamps();
            
            // Un hashtag ne peut être ajouté qu'une fois par contenu
            $table->unique(['hashtag_id', 'taggable_type', 'taggable_id'], 'taggables_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('hashtags');
    }
};
