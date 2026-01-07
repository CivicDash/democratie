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
        Schema::table('maires', function (Blueprint $table) {
            // Augmenter la taille du champ uid pour les noms très longs
            $table->string('uid', 150)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            $table->string('uid', 50)->change();
        });
    }
};

