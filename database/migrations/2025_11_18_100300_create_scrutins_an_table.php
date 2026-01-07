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
        Schema::create('scrutins_an', function (Blueprint $table) {
            $table->string('uid', 30)->primary()->comment('UID scrutin (ex: VTANR5L17V1000)');
            $table->integer('numero')->index();
            $table->string('organe_ref', 20)->index()->comment('Référence organe (Assemblée)');
            $table->integer('legislature')->index();
            $table->date('date_scrutin')->index();
            $table->string('type_vote_code', 10);
            $table->string('type_vote_libelle', 100);
            $table->string('resultat_code', 20)->comment('adopté, rejeté');
            $table->string('resultat_libelle', 255);
            $table->text('titre');
            $table->integer('nombre_votants');
            $table->integer('suffrages_exprimes');
            $table->integer('suffrage_requis');
            $table->integer('pour');
            $table->integer('contre');
            $table->integer('abstentions');
            $table->integer('non_votants')->nullable();
            $table->json('ventilation_votes')->nullable()->comment('Votes par groupe (JSON complet)');
            $table->timestamps();
            
            // Index composites
            $table->index(['legislature', 'date_scrutin']);
            $table->index(['legislature', 'resultat_code']);
        });

        // Full-text search sur le titre
        DB::statement('CREATE INDEX scrutins_an_titre_fulltext ON scrutins_an USING gin(to_tsvector(\'french\', titre))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrutins_an');
    }
};

