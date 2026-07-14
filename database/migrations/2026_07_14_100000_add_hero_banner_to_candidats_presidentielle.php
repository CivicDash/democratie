<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Image de bannière (hero) du candidat + crédit/licence obligatoires — en plus du
 * portrait (photo_*). L'export n'émet la bannière que si crédit + licence présents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->string('hero_banner_url', 500)->nullable()->after('photo_licence');
            $table->string('hero_credit', 255)->nullable()->after('hero_banner_url');
            $table->string('hero_licence', 120)->nullable()->after('hero_credit');
        });
    }

    public function down(): void
    {
        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->dropColumn(['hero_banner_url', 'hero_credit', 'hero_licence']);
        });
    }
};
