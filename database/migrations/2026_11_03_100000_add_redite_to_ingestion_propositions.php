<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redites : une proposition qui reprend une mesure déjà au comparateur pointe vers elle.
 * Rattachée en modération, elle ajoute une prise de parole à la mesure au lieu d'en créer
 * une seconde ; le nombre de prises de parole d'une mesure en découle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingestion_propositions', function (Blueprint $table) {
            $table->foreignId('redite_de_mesure_id')->nullable()->after('mesure_id')
                ->constrained('programme_mesures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ingestion_propositions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('redite_de_mesure_id');
        });
    }
};
