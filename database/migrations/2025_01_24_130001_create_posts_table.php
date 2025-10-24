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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('posts')->onDelete('cascade')
                ->comment('Pour les réponses (threading)');
            $table->text('content')->comment('Contenu Markdown restreint (pas d\'images/liens pour citoyens)');
            $table->boolean('is_official')->default(false)
                ->comment('Post officiel (législateur/État)');
            $table->integer('upvotes')->default(0);
            $table->integer('downvotes')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_hidden')->default(false)->comment('Masqué par modération');
            $table->string('hidden_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('topic_id');
            $table->index('user_id');
            $table->index('parent_id');
            $table->index(['topic_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};

