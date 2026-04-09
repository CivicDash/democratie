<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('commune_page_id')->constrained('commune_pages')->cascadeOnDelete();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();

            $table->string('titre');
            $table->string('slug');
            $table->text('contenu');
            $table->string('extrait', 500)->nullable();
            $table->string('image_path')->nullable();

            $table->string('categorie', 30)->default('info_generale');

            $table->boolean('epingle')->default(false);
            $table->boolean('publie')->default(false);
            $table->timestamp('publie_at')->nullable();
            $table->unsignedBigInteger('vues_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['commune_page_id', 'slug']);
            $table->index(['commune_page_id', 'publie', 'publie_at']);
            $table->index('categorie');
        });

        DB::statement("ALTER TABLE commune_articles ADD CONSTRAINT commune_articles_categorie_check CHECK (categorie IN ('info_generale', 'travaux', 'culture', 'sport', 'association', 'urbanisme', 'securite', 'environnement', 'education', 'social', 'officiel'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_articles');
    }
};
