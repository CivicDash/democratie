<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            
            // Identification
            $table->string('code', 50)->unique()->comment('Ex: first_vote, vote_streak_7');
            $table->string('name', 100)->comment('Nom affiché');
            $table->text('description')->comment('Description du badge');
            
            // Visuel
            $table->string('icon', 100)->comment('Emoji ou classe icône');
            $table->string('color', 20)->default('blue')->comment('Couleur du badge');
            $table->string('image_url')->nullable()->comment('Image custom optionnelle');
            
            // Classification
            $table->string('category', 50)->comment('participation, legislative, budget, social, etc.');
            $table->string('rarity', 20)->default('common')->comment('common, rare, epic, legendary');
            $table->integer('points', false, true)->default(10)->comment('Points XP gagnés');
            
            // Conditions
            $table->string('trigger_type', 50)->comment('vote_count, topic_created, streak, etc.');
            $table->json('trigger_conditions')->nullable()->comment('Conditions spécifiques JSON');
            $table->integer('required_value')->default(1)->comment('Valeur cible (ex: 10 votes)');
            
            // Méta
            $table->integer('order')->default(0)->comment('Ordre d\'affichage');
            $table->boolean('is_secret')->default(false)->comment('Badge caché jusqu\'à obtention');
            $table->boolean('is_active')->default(true)->comment('Badge actif');
            
            // Stats (dénormalisées)
            $table->integer('unlock_count')->default(0)->comment('Nombre de déblocages');
            
            $table->timestamps();
            
            // Index
            $table->index(['category', 'rarity']);
            $table->index(['trigger_type']);
            $table->index(['is_active', 'is_secret']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
