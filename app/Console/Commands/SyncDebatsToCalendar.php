<?php

namespace App\Console\Commands;

use App\Models\EvenementLegislatif;
use App\Models\SenatDebat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDebatsToCalendar extends Command
{
    protected $signature = 'sync:debats-calendar 
                            {--fresh : Supprimer et recréer tous les événements débats}
                            {--since= : Importer uniquement depuis cette date (YYYY-MM-DD)}';

    protected $description = 'Synchronise les comptes-rendus de séance du Sénat avec le calendrier';

    public function handle(): int
    {
        $this->info('🗓️ Synchronisation des débats Sénat vers le calendrier...');

        if ($this->option('fresh')) {
            $deleted = EvenementLegislatif::where('source', 'senat')
                ->where('type', 'seance')
                ->where('uid', 'like', 'senat-debat-%')
                ->delete();
            $this->warn("🗑️ {$deleted} événements débats supprimés");
        }

        $query = SenatDebat::query()->orderBy('date_seance');

        if ($this->option('since')) {
            $query->where('date_seance', '>=', $this->option('since'));
        }

        $debats = $query->get();
        $this->info("📊 {$debats->count()} débats à synchroniser");

        $bar = $this->output->createProgressBar($debats->count());
        $bar->start();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($debats as $debat) {
            $uid = 'senat-debat-'.$debat->date_seance->format('Y-m-d');

            // Vérifier si existe déjà
            $existing = EvenementLegislatif::where('uid', $uid)->first();

            // Construire le titre
            $titre = 'Séance publique du Sénat';
            if ($debat->numero) {
                $titre .= " (n°{$debat->numero})";
            }
            if ($debat->est_congres) {
                $titre = '🏛️ Congrès du Parlement';
            }
            if ($debat->libelle_special) {
                $titre = $debat->libelle_special;
            }

            // Construire la description avec les sujets principaux
            $description = $this->buildDescription($debat);

            // URL interne vers la page des débats
            $urlInterne = '/debats/senat/'.$debat->date_seance->format('Y-m-d');

            $data = [
                'source' => 'senat',
                'type' => 'seance',
                'titre' => $titre,
                'description' => $description,
                'lieu' => 'Palais du Luxembourg, Paris',
                'date_debut' => $debat->date_seance->setTime(14, 30), // Heure habituelle des séances
                'date_fin' => $debat->date_seance->setTime(23, 59),
                'journee_entiere' => false,
                'instance_code' => 'SENAT',
                'instance_nom' => 'Sénat - Séance publique',
                'url_source' => $debat->url_compte_rendu,
                'url_dossier' => $urlInterne,
                'couleur' => '#DC143C',
                'icone' => '🔴',
                'statut' => 'confirme',
            ];

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                EvenementLegislatif::create(array_merge(['uid' => $uid], $data));
                $created++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Synchronisation terminée');
        $this->table(
            ['Action', 'Nombre'],
            [
                ['Créés', $created],
                ['Mis à jour', $updated],
                ['Ignorés', $skipped],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Construire la description avec les principaux sujets de la séance
     */
    protected function buildDescription(SenatDebat $debat): string
    {
        $parts = [];

        // Récupérer les principales sections (sujets) de la séance
        $sections = DB::table('senat_sections_discussion')
            ->where('date_seance', $debat->date_seance)
            ->whereNull('parent_id')
            ->orderBy('ordre')
            ->limit(10)
            ->pluck('objet')
            ->filter()
            ->values();

        if ($sections->isNotEmpty()) {
            $parts[] = '📋 Ordre du jour :';
            foreach ($sections as $index => $objet) {
                $numero = $index + 1;
                $objetCourt = strlen($objet) > 100 ? substr($objet, 0, 100).'...' : $objet;
                $parts[] = "  {$numero}. {$objetCourt}";
            }
        }

        // Statistiques de la séance
        $stats = DB::table('senat_interventions_legislatives as i')
            ->join('senat_sections_discussion as s', 'i.section_id', '=', 's.id')
            ->where('s.date_seance', $debat->date_seance)
            ->selectRaw('COUNT(*) as nb_interventions, COUNT(DISTINCT i.auteur_code) as nb_orateurs')
            ->first();

        if ($stats && $stats->nb_interventions > 0) {
            $parts[] = '';
            $parts[] = "📊 {$stats->nb_interventions} interventions par {$stats->nb_orateurs} orateurs";
        }

        // Lien vers le compte rendu
        if ($debat->url_compte_rendu) {
            $parts[] = '';
            $parts[] = '🔗 Compte rendu intégral disponible';
        }

        return implode("\n", $parts);
    }
}
