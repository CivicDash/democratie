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
        Schema::create('organes_an', function (Blueprint $table) {
            $table->string('uid', 20)->primary()->comment('UID organe (ex: PO838901)');
            $table->string('code_type', 50)->index()->comment('GP, COMPER, DELEG, etc.');
            $table->string('libelle', 255);
            $table->string('libelle_abrege', 100)->nullable();
            $table->integer('legislature')->nullable()->index();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('regime', 50)->nullable();
            $table->string('site_internet', 255)->nullable();
            $table->timestamps();
            
            // Index composites
            $table->index(['code_type', 'legislature']);
            $table->index(['legislature', 'date_fin']); // Pour filtrer les organes actifs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organes_an');
    }
};

