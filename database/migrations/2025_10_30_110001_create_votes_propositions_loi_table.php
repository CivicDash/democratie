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
        Schema::create('votes_propositions_loi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('proposition_loi_id')->constrained('propositions_loi')->onDelete('cascade');
            
            $table->string('type_vote', 20)->comment('upvote ou downvote');
            $table->text('commentaire')->nullable()->comment('Commentaire optionnel du citoyen');
            
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            // Un utilisateur ne peut voter qu'une fois par proposition
            $table->unique(['user_id', 'proposition_loi_id'], 'unique_user_vote_proposition');
            
            // Index pour performance
            $table->index(['proposition_loi_id', 'type_vote'], 'idx_prop_type');
            $table->index(['user_id', 'created_at'], 'idx_user_date');
            $table->index(['type_vote'], 'idx_type_vote');
        });

        // Ajouter les compteurs de votes dans la table propositions_loi
        Schema::table('propositions_loi', function (Blueprint $table) {
            $table->integer('votes_pour')->default(0)->after('url_pdf')->comment('Nombre de upvotes');
            $table->integer('votes_contre')->default(0)->after('votes_pour')->comment('Nombre de downvotes');
            $table->integer('score_vote')->default(0)->after('votes_contre')->comment('Score net (pour - contre)');
            
            $table->index(['score_vote'], 'idx_score_vote');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propositions_loi', function (Blueprint $table) {
            $table->dropColumn(['votes_pour', 'votes_contre']);
        });
        
        Schema::dropIfExists('votes_propositions_loi');
    }
};

