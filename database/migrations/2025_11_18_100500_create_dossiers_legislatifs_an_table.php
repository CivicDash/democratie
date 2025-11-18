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
        Schema::create('dossiers_legislatifs_an', function (Blueprint $table) {
            $table->string('uid', 30)->primary()->comment('UID dossier (ex: DLR5L17N51035)');
            $table->integer('legislature')->index();
            $table->integer('numero')->nullable();
            $table->string('titre', 500)->nullable();
            $table->date('date_creation')->nullable();
            $table->timestamps();
            
            // Index composites
            $table->index(['legislature', 'numero']);
        });

        // Full-text search sur le titre
        DB::statement('CREATE INDEX dossiers_legislatifs_an_titre_fulltext ON dossiers_legislatifs_an USING gin(to_tsvector(\'french\', titre))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dossiers_legislatifs_an');
    }
};

