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
        // Ajouter la colonne pour tracker l'import des détails
        Schema::table('hatvp_declarations', function (Blueprint $table) {
            $table->timestamp('details_imported_at')->nullable()->after('updated_at');
        });

        // Créer la table de rémunérations polymorphique pour les mandats
        if (!Schema::hasTable('hatvp_remunerations')) {
            Schema::create('hatvp_remunerations', function (Blueprint $table) {
                $table->id();
                $table->string('remuneratable_type'); // HatvpMandatElectif, etc.
                $table->unsignedBigInteger('remuneratable_id');
                $table->integer('annee');
                $table->decimal('montant', 12, 2)->nullable();
                $table->string('brut_net', 10)->nullable();
                $table->timestamps();

                $table->index(['remuneratable_type', 'remuneratable_id'], 'hatvp_rem_morph_idx');
                $table->index(['remuneratable_type', 'remuneratable_id', 'annee'], 'hatvp_rem_morph_annee_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hatvp_declarations', function (Blueprint $table) {
            $table->dropColumn('details_imported_at');
        });

        Schema::dropIfExists('hatvp_remunerations');
    }
};

