<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enrichit listes_electorales pour supporter l'import data.gouv.fr
 * et les résultats électoraux (T1/T2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listes_electorales', function (Blueprint $table) {
            $table->string('source', 20)->default('civicdash')->after('statut');
            $table->integer('numero_panneau')->nullable()->after('source');
            $table->string('libelle_abrege', 50)->nullable()->after('numero_panneau');
            $table->string('libelle_etendu')->nullable()->after('libelle_abrege');
            $table->tinyInteger('tour')->default(1)->after('departement_code');

            $table->unsignedBigInteger('liste_t1_id')->nullable()->after('libelle_etendu');
            $table->foreign('liste_t1_id')
                ->references('id')->on('listes_electorales')->nullOnDelete();

            $table->unsignedBigInteger('liste_civicdash_id')->nullable()->after('liste_t1_id');

            $table->index(['commune_code_insee', 'tour']);
            $table->index('source');
        });

        DB::statement('ALTER TABLE listes_electorales DROP CONSTRAINT IF EXISTS listes_electorales_statut_check');
        DB::statement("ALTER TABLE listes_electorales ADD CONSTRAINT listes_electorales_statut_check CHECK (statut::text = ANY(ARRAY['brouillon','en_attente','documents_requis','en_verification','valide','rejete','suspendu','officiel']::text[]))");
    }

    public function down(): void
    {
        Schema::table('listes_electorales', function (Blueprint $table) {
            $table->dropForeign(['liste_t1_id']);
            $table->dropIndex(['commune_code_insee', 'tour']);
            $table->dropIndex(['source']);
            $table->dropColumn([
                'source', 'numero_panneau', 'libelle_abrege', 'libelle_etendu',
                'tour', 'liste_t1_id', 'liste_civicdash_id',
            ]);
        });

        DB::statement('ALTER TABLE listes_electorales DROP CONSTRAINT IF EXISTS listes_electorales_statut_check');
        DB::statement("ALTER TABLE listes_electorales ADD CONSTRAINT listes_electorales_statut_check CHECK (statut::text = ANY(ARRAY['brouillon','en_attente','documents_requis','en_verification','valide','rejete','suspendu']::text[]))");
    }
};
