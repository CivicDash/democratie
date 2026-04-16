<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les clés primaires et index manquants aux tables Sénat.
 *
 * Contexte : les tables importées depuis les APIs du Sénat (Oracle)
 * n'avaient aucune contrainte d'unicité, ce qui a permis des imports
 * dupliqués (~20-30x). Après déduplication (mars 2026), cette migration
 * formalise les contraintes pour prévenir les futurs doublons.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────
        // Tables de référence (petites, clés simples)
        // ──────────────────────────────────────
        $this->addPkIfMissing('senat_senateurs_sen', 'senmat');
        $this->addPkIfMissing('senat_senateurs_qua', 'quacod');
        $this->addPkIfMissing('senat_senateurs_typman', 'typmancod');
        $this->addPkIfMissing('senat_senateurs_dpt', 'dptnum');
        $this->addPkIfMissing('senat_senateurs_grppol', 'grppolcod');
        $this->addPkIfMissing('senat_senateurs_com', 'orgcod');
        $this->addPkIfMissing('senat_senateurs_scr', 'scrid');
        $this->addPkIfMissing('senat_ameli_sor', 'id');

        // ──────────────────────────────────────
        // Tables principales sénateurs
        // ──────────────────────────────────────
        $this->addPkIfMissing('senat_senateurs_elusen', 'eluid');
        $this->addPkIfMissing('senat_senateurs_memgrpsen', 'memgrpsenid');
        $this->addPkIfMissing('senat_senateurs_memcom', 'memcomid');
        $this->addPkIfMissing('senat_senateurs_memcomsea', 'memcomseaid');
        $this->addPkIfMissing('senat_senateurs_votes', 'votesid');
        $this->addPkIfMissing('senat_senateurs_activite', 'actid');
        $this->addPkIfMissing('senat_senateurs_activite_participant', 'actparid');
        $this->addPkIfMissing('senat_senateurs_sysevt', 'sysevtid');
        $this->addPkIfMissing('senat_senateurs_rne_mandat', 'id');
        $this->addPkIfMissing('senat_senateurs_activite_senateur_params', 'actsenparid');

        // ──────────────────────────────────────
        // Tables AMELI (amendements)
        // ──────────────────────────────────────
        $this->addPkIfMissing('senat_ameli_amd', 'id');
        $this->addPkIfMissing('senat_ameli_sub', 'id');
        $this->addCompositePkIfMissing('senat_ameli_amdsen', ['amdid', 'senid', 'rng']);

        // ──────────────────────────────────────
        // Tables Débats
        // ──────────────────────────────────────
        $this->addPkIfMissing('senat_debats_intpjl', 'intpjlcle');
        $this->addPkIfMissing('senat_debats_secdis', 'secdiscle');
        $this->addPkIfMissing('senat_debats_intdivers', 'intdiverscle');
        $this->addPkIfMissing('senat_debats_secdivers', 'secdiverscle');

        // ──────────────────────────────────────
        // Tables Dossiers législatifs
        // ──────────────────────────────────────
        $this->addPkIfMissing('senat_dosleg_texte', 'texcod');
        $this->addPkIfMissing('senat_dosleg_rap', 'rapcod');
        $this->addPkIfMissing('senat_dosleg_loi', 'loicod');
        $this->addCompositePkIfMissing('senat_dosleg_votsen', ['sesann', 'scrnum', 'senmat']);

        // Audit
        $this->addCompositePkIfMissing('senat_senateurs_activite_audit', ['actid', 'rev']);

        // ──────────────────────────────────────
        // Index sur les clés étrangères fréquentes
        // ──────────────────────────────────────
        $indexes = [
            ['senat_senateurs_elusen',               'senmat',    'idx_elusen_senmat'],
            ['senat_senateurs_elusen',               'dptnum',    'idx_elusen_dptnum'],
            ['senat_senateurs_memgrpsen',             'senmat',    'idx_memgrpsen_senmat'],
            ['senat_senateurs_memgrpsen',             'orgcod',    'idx_memgrpsen_orgcod'],
            ['senat_senateurs_memcom',                'senmat',    'idx_memcom_senmat'],
            ['senat_senateurs_memcom',                'orgcod',    'idx_memcom_orgcod'],
            ['senat_senateurs_votes',                 'senmat',    'idx_votes_senmat'],
            ['senat_senateurs_votes',                 'scrid',     'idx_votes_scrid'],
            ['senat_senateurs_activite_participant',  'senmat',    'idx_actpart_senmat'],
            ['senat_senateurs_activite_participant',  'actid',     'idx_actpart_actid'],
            ['senat_debats_intpjl',                   'autcod',    'idx_intpjl_autcod'],
            ['senat_debats_intpjl',                   'secdiscle', 'idx_intpjl_secdiscle'],
            ['senat_debats_intdivers',                'autcod',    'idx_intdivers_autcod'],
            ['senat_ameli_amdsen',                    'amdid',     'idx_amdsen_amdid'],
        ];

        foreach ($indexes as [$table, $column, $name]) {
            $this->addIndexIfMissing($table, $column, $name);
        }
    }

    public function down(): void
    {
        // Les PKs et index peuvent être supprimés manuellement si nécessaire.
        // Ne pas les supprimer automatiquement pour éviter la perte d'intégrité.
    }

    private function addPkIfMissing(string $table, string $column): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $hasPk = DB::selectOne('
            SELECT EXISTS (
                SELECT 1 FROM pg_index i
                WHERE i.indrelid = ?::regclass AND i.indisprimary
            ) as has_pk
        ', [$table]);

        if (! $hasPk->has_pk) {
            DB::statement("ALTER TABLE {$table} ADD PRIMARY KEY ({$column})");
        }
    }

    private function addCompositePkIfMissing(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $hasPk = DB::selectOne('
            SELECT EXISTS (
                SELECT 1 FROM pg_index i
                WHERE i.indrelid = ?::regclass AND i.indisprimary
            ) as has_pk
        ', [$table]);

        if (! $hasPk->has_pk) {
            $cols = implode(', ', $columns);
            DB::statement("ALTER TABLE {$table} ADD PRIMARY KEY ({$cols})");
        }
    }

    private function addIndexIfMissing(string $table, string $column, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = DB::selectOne('
            SELECT EXISTS (
                SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?
            ) as idx_exists
        ', [$table, $name]);

        if (! $exists->idx_exists) {
            DB::statement("CREATE INDEX {$name} ON {$table} ({$column})");
        }
    }
};
