<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->string('slogan', 200)->nullable()->after('parti_soutien');
        });
    }

    public function down(): void
    {
        Schema::table('candidats_presidentielle', function (Blueprint $table) {
            $table->dropColumn('slogan');
        });
    }
};
