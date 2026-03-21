<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enrichit candidats_municipaux pour l'import officiel data.gouv.fr
 * et le suivi des résultats (élu, sortant, lien maire).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidats_municipaux', function (Blueprint $table) {
            $table->string('source', 20)->default('civicdash')->after('statut');
            $table->string('sexe', 1)->nullable()->after('source');
            $table->boolean('sortant')->default(false)->after('sexe');
            $table->boolean('elu')->nullable()->after('sortant');

            $table->foreignId('maire_id')->nullable()
                  ->constrained('maires')->nullOnDelete();

            $table->index('elu');
            $table->index('sortant');
        });
    }

    public function down(): void
    {
        Schema::table('candidats_municipaux', function (Blueprint $table) {
            $table->dropForeign(['maire_id']);
            $table->dropIndex(['elu']);
            $table->dropIndex(['sortant']);
            $table->dropColumn(['source', 'sexe', 'sortant', 'elu', 'maire_id']);
        });
    }
};
