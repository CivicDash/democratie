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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('filename');
            $table->string('path')->comment('Chemin stockage sécurisé');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size')->comment('Taille en bytes');
            $table->string('hash', 64)->unique()->comment('SHA256 du fichier');
            
            // Association
            $table->morphs('documentable'); // topics, posts, etc.
            
            // Auteur (rôle restreint)
            $table->foreignId('uploader_id')->constrained('users')->onDelete('cascade')
                ->comment('Uploadé par (legislator, state, admin uniquement)');
            
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Note: morphs() crée déjà un index sur documentable_type + documentable_id
            $table->index('status');
            $table->index('uploader_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

