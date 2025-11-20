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
        Schema::create('scrutins_senat', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique(); // Ex: "2024-25-123"
            $table->string('legislature', 10);
            $table->date('date_scrutin');
            $table->string('titre');
            $table->text('objet')->nullable();
            $table->string('type_vote', 50)->nullable(); // 'solennel', 'ordinaire'
            $table->integer('pour')->default(0);
            $table->integer('contre')->default(0);
            $table->integer('abstentions')->default(0);
            $table->integer('non_votants')->default(0);
            $table->string('resultat', 50)->nullable(); // 'adopté', 'rejeté'
            $table->text('url')->nullable();
            $table->jsonb('donnees_source')->nullable(); // JSON brut de NosSénateurs.fr
            $table->timestamps();

            $table->index('legislature');
            $table->index('date_scrutin');
            $table->index('resultat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrutins_senat');
    }
};

