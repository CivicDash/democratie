<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_consultations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('commune_page_id')->constrained('commune_pages')->cascadeOnDelete();
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre');
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->json('options');
            $table->boolean('multiple')->default(false);
            $table->boolean('publie')->default(false);
            $table->boolean('fermee')->default(false);
            $table->timestamp('publie_at')->nullable();
            $table->timestamp('ferme_at')->nullable();
            $table->integer('votes_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('commune_consultation_votes', function (Blueprint $table) {
            $table->id();
            $table->uuid('consultation_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('option_key');
            $table->timestamps();

            $table->foreign('consultation_id')->references('id')->on('commune_consultations')->cascadeOnDelete();
            $table->unique(['consultation_id', 'user_id', 'option_key'], 'commune_consultation_votes_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_consultation_votes');
        Schema::dropIfExists('commune_consultations');
    }
};
