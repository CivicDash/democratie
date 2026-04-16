<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Corrige la table hatvp_remunerations pour utiliser une structure polymorphique
     */
    public function up(): void
    {
        // Supprimer l'ancienne table et la recréer avec la bonne structure
        Schema::dropIfExists('hatvp_remunerations');

        Schema::create('hatvp_remunerations', function (Blueprint $table) {
            $table->id();
            $table->string('remuneratable_type'); // App\Models\HatvpMandatElectif, etc.
            $table->unsignedBigInteger('remuneratable_id');
            $table->integer('annee');
            $table->decimal('montant', 12, 2)->nullable();
            $table->string('brut_net', 10)->nullable();
            $table->timestamps();

            $table->index(['remuneratable_type', 'remuneratable_id'], 'hatvp_rem_morph_idx');
            $table->unique(['remuneratable_type', 'remuneratable_id', 'annee'], 'hatvp_rem_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hatvp_remunerations');
    }
};
