<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senateurs', function (Blueprint $table) {
            $table->string('matricule', 10)->primary()->comment('Matricule sénateur (ex: 21077M)');
            $table->string('civilite', 10);
            $table->string('nom_usuel', 100);
            $table->string('prenom_usuel', 100);
            $table->enum('etat', ['ACTIF', 'ANCIEN'])->index();
            $table->date('date_naissance')->nullable();
            $table->date('date_deces')->nullable();
            $table->string('groupe_politique', 100)->nullable()->index();
            $table->string('type_appartenance_groupe', 50)->nullable();
            $table->string('commission_permanente', 100)->nullable();
            $table->string('circonscription', 100)->nullable()->index();
            $table->string('fonction_bureau_senat', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('pcs_insee')->nullable();
            $table->string('categorie_socio_pro')->nullable();
            $table->string('description_profession')->nullable();
            $table->timestamps();
            
            $table->index(['nom_usuel', 'prenom_usuel']);
        });

        DB::statement('CREATE INDEX senateurs_fulltext ON senateurs USING gin(to_tsvector(\'french\', nom_usuel || \' \' || prenom_usuel))');
    }

    public function down(): void
    {
        Schema::dropIfExists('senateurs');
    }
};

