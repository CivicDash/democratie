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
        Schema::create('ballot_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 128)->unique()->comment('Jeton opaque signé à usage unique');
            $table->boolean('consumed')->default(false)->comment('Jeton consommé après vote');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('expires_at')->comment('Expiration = voting_deadline_at du topic');
            $table->timestamps();
            
            // Un user = un jeton par topic
            $table->unique(['topic_id', 'user_id']);
            $table->index('token');
            $table->index(['topic_id', 'consumed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ballot_tokens');
    }
};

