<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le porteur d'une source : qui parle dans ce document, au nom de qui.
 *
 * Mêmes colonnes sur les prises de parole (`ingestion_documents`) et sur les
 * référentiels de programme (`programme_documents`). Tout l'existant reste « candidat ».
 * Pour tout autre porteur, la preuve d'endossement (URL, date, note) est exigée avant
 * publication ; les documents d'un parti ou d'un groupe parlementaire ne paraissent que
 * dans « Autour du candidat ».
 */
return new class extends Migration
{
    private const TABLES = ['ingestion_documents', 'programme_documents'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('porteur_type', 30)->default('candidat');
                $t->string('porteur_libelle', 255)->nullable();
                $t->string('endossement_url', 1000)->nullable();
                $t->date('endossement_date')->nullable();
                $t->text('endossement_note')->nullable();
                $t->index('porteur_type');
            });

            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_porteur_type_connu
                CHECK (porteur_type IN ('candidat', 'equipe_campagne', 'parti', 'groupe_parlementaire'))");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_porteur_type_connu");
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['porteur_type']);
                $t->dropColumn(['porteur_type', 'porteur_libelle', 'endossement_url', 'endossement_date', 'endossement_note']);
            });
        }
    }
};
