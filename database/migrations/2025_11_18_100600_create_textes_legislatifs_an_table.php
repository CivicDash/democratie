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
        Schema::create('textes_legislatifs_an', function (Blueprint $table) {
            $table->string('uid', 30)->primary()->comment('UID texte (ex: PIONANR5L17B0689)');
            $table->string('dossier_ref', 30)->nullable()->index();
            $table->integer('legislature')->index();
            $table->string('type_texte', 10)->comment('PION, PRJL, etc.');
            $table->integer('numero')->nullable();
            $table->string('titre', 500)->nullable();
            $table->date('date_depot')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('dossier_ref')->references('uid')->on('dossiers_legislatifs_an')->onDelete('set null');
            
            // Index composites
            $table->index(['legislature', 'type_texte']);
            $table->index(['legislature', 'date_depot']);
        });

        // Full-text search sur le titre
        DB::statement('CREATE INDEX textes_legislatifs_an_titre_fulltext ON textes_legislatifs_an USING gin(to_tsvector(\'french\', titre))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('textes_legislatifs_an');
    }
};

