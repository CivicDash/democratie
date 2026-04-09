<?php

namespace App\Console\Commands;

use App\Models\CommunePage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCommuneStats extends Command
{
    protected $signature = 'communes:sync-stats
                            {--code-insee= : Synchronise une seule commune}';

    protected $description = 'Synchronise les compteurs des pages communes (abonnes, vues)';

    public function handle(): int
    {
        $this->info('Synchronisation des statistiques des pages communes...');

        if ($codeInsee = $this->option('code-insee')) {
            $page = CommunePage::where('code_insee', $codeInsee)->first();
            if (! $page) {
                $this->error("Commune {$codeInsee} introuvable");

                return self::FAILURE;
            }
            $this->syncPage($page);
            $this->info("  Commune {$codeInsee} synchronisee");

            return self::SUCCESS;
        }

        $updated = DB::statement("
            UPDATE commune_pages SET
                abonnes_count = COALESCE((
                    SELECT COUNT(*) FROM commune_abonnements
                    WHERE commune_abonnements.commune_code_insee = commune_pages.code_insee
                ), 0),
                updated_at = NOW()
        ");

        $stats = DB::selectOne("
            SELECT
                COUNT(*) as total_pages,
                COUNT(*) FILTER (WHERE statut = 'active') as pages_actives,
                COUNT(*) FILTER (WHERE statut = 'reclamee') as pages_reclamees,
                SUM(abonnes_count) as total_abonnes,
                SUM(vues_totales) as total_vues
            FROM commune_pages
        ");

        $this->table(
            ['Metrique', 'Valeur'],
            [
                ['Total pages', $stats->total_pages ?? 0],
                ['Pages actives', $stats->pages_actives ?? 0],
                ['Pages reclamees', $stats->pages_reclamees ?? 0],
                ['Total abonnes', number_format($stats->total_abonnes ?? 0, 0, ',', ' ')],
                ['Total vues', number_format($stats->total_vues ?? 0, 0, ',', ' ')],
            ]
        );

        return self::SUCCESS;
    }

    private function syncPage(CommunePage $page): void
    {
        $abonnes = DB::table('commune_abonnements')
            ->where('commune_code_insee', $page->code_insee)
            ->count();

        $page->update(['abonnes_count' => $abonnes]);
    }
}
