<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photo de portrait du candidat + crédit et licence OBLIGATOIRES (plan §3 itération 2).
 * L'export refuse une photo sans crédit + licence. Fallback front = dégradé + initiales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->string('photo_url', 500)->nullable()->after('couleur_hex');
            $table->string('photo_credit', 255)->nullable()->after('photo_url');
            $table->string('photo_licence', 120)->nullable()->after('photo_credit');
        });
    }

    public function down(): void
    {
        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->dropColumn(['photo_url', 'photo_credit', 'photo_licence']);
        });
    }
};
