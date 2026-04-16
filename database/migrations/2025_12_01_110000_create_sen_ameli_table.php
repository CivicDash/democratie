<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de correspondance entre entid (ID numérique) et mat (matricule)
     * pour les sénateurs dans le système AMELI.
     *
     * Cette table est nécessaire pour faire le lien entre :
     * - senat_ameli_amdsen.senid (ID numérique)
     * - senat_senateurs_sen.senmat (matricule comme "20110Q")
     */
    public function up(): void
    {
        Schema::create('sen_ameli', function (Blueprint $table) {
            $table->integer('entid')->primary()->comment('ID numérique AMELI');
            $table->integer('grpid')->nullable()->comment('ID groupe politique');
            $table->integer('comid')->nullable()->comment('ID commission');
            $table->integer('comspcid')->nullable()->comment('ID commission spéciale');
            $table->string('mat', 10)->index()->comment('Matricule sénateur (ex: 20110Q)');
            $table->string('qua', 10)->nullable()->comment('Qualité (M., Mme)');
            $table->string('nomuse', 64)->nullable();
            $table->string('prenomuse', 64)->nullable();
            $table->string('nomtec', 100)->nullable();
            $table->string('hom', 1)->default('N');
            $table->string('app', 1)->default('N');
            $table->string('ratt', 1)->default('N');
            $table->string('nomusemin', 64)->nullable();
            $table->string('senfem', 1)->default('N');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sen_ameli');
    }
};
