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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->comment('Description Markdown restreint (pas d\'images/liens)');
            $table->enum('scope', ['national', 'region', 'dept'])->default('national');
            $table->foreignId('region_id')->nullable()->constrained('territories_regions')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('territories_departments')->onDelete('cascade');
            $table->enum('type', ['debate', 'bill', 'referendum'])->default('debate')
                ->comment('Type: débat, projet de loi, référendum');
            $table->enum('status', ['draft', 'open', 'closed', 'archived'])->default('draft');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade')
                ->comment('Auteur (rôle requis: legislator ou admin)');
            
            // Scrutin
            $table->boolean('has_ballot')->default(false)->comment('Active le scrutin');
            $table->timestamp('voting_opens_at')->nullable()->comment('Ouverture du scrutin');
            $table->timestamp('voting_deadline_at')->nullable()->comment('Fermeture + révélation');
            $table->enum('ballot_type', ['yes_no', 'multiple_choice', 'preferential'])->nullable()
                ->comment('Type de scrutin');
            $table->json('ballot_options')->nullable()->comment('Options de vote (pour multiple_choice)');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['scope', 'status']);
            $table->index(['region_id', 'department_id']);
            $table->index('voting_deadline_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};

