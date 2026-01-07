<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des tags/thèmes
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('color', 7)->default('#3B82F6'); // Hex color
            $table->string('icon')->nullable(); // Emoji ou classe icon
            $table->text('description')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index('slug');
        });

        // Relation tags <-> dossiers législatifs
        Schema::create('dossier_legislatif_tag', function (Blueprint $table) {
            $table->id();
            $table->string('dossier_legislatif_uid', 30);
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['dossier_legislatif_uid', 'tag_id']);
            $table->foreign('dossier_legislatif_uid')
                ->references('uid')
                ->on('dossiers_legislatifs_an')
                ->onDelete('cascade');
        });

        // Relation tags <-> scrutins
        Schema::create('scrutin_tag', function (Blueprint $table) {
            $table->id();
            $table->string('scrutin_uid', 30);
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['scrutin_uid', 'tag_id']);
            $table->foreign('scrutin_uid')
                ->references('uid')
                ->on('scrutins_an')
                ->onDelete('cascade');
        });

        // Relation tags <-> topics
        Schema::create('tag_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['topic_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_topic');
        Schema::dropIfExists('scrutin_tag');
        Schema::dropIfExists('dossier_legislatif_tag');
        Schema::dropIfExists('tags');
    }
};

