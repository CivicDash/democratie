<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_commentaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('commentable_type');
            $table->uuid('commentable_id');
            $table->uuid('parent_id')->nullable();
            $table->text('contenu');
            $table->boolean('masque')->default(false);
            $table->string('masque_raison')->nullable();
            $table->integer('signalements_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index('parent_id');
            $table->index('user_id');
        });

        Schema::create('commune_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reactable_type');
            $table->uuid('reactable_id');
            $table->string('type');
            $table->timestamps();

            $table->unique(['user_id', 'reactable_type', 'reactable_id', 'type'], 'commune_reactions_unique');
            $table->index(['reactable_type', 'reactable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_reactions');
        Schema::dropIfExists('commune_commentaires');
    }
};
