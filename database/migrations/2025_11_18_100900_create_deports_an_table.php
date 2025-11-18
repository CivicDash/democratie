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
        Schema::create('deports_an', function (Blueprint $table) {
            $table->string('uid', 50)->primary()->comment('UID déport (ex: DPTR5L17PA795950D0001)');
            $table->string('acteur_ref', 20)->index();
            $table->string('scrutin_ref', 30)->nullable()->index();
            $table->integer('legislature')->index();
            $table->string('raison', 255)->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('acteur_ref')->references('uid')->on('acteurs_an')->onDelete('cascade');
            $table->foreign('scrutin_ref')->references('uid')->on('scrutins_an')->onDelete('set null');
            
            // Index composites
            $table->index(['legislature', 'acteur_ref']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deports_an');
    }
};

