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
        Schema::create('acteurs_an', function (Blueprint $table) {
            $table->string('uid', 20)->primary()->comment('UID acteur (ex: PA1008)');
            $table->string('civilite', 10);
            $table->string('prenom', 100);
            $table->string('nom', 100);
            $table->string('trigramme', 3)->nullable()->index();
            $table->date('date_naissance')->nullable();
            $table->string('ville_naissance', 100)->nullable();
            $table->string('departement_naissance', 100)->nullable();
            $table->string('pays_naissance', 100)->nullable();
            $table->string('profession', 255)->nullable();
            $table->string('categorie_socio_pro', 100)->nullable();
            $table->string('url_hatvp', 255)->nullable();
            $table->json('adresses')->nullable()->comment('Adresses (circonscription, email, etc.)');
            $table->timestamps();
            
            // Index
            $table->index(['nom', 'prenom']);
        });

        // Full-text search sur nom et prénom
        DB::statement('CREATE INDEX acteurs_an_fulltext ON acteurs_an USING gin(to_tsvector(\'french\', nom || \' \' || prenom))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acteurs_an');
    }
};

