<?php

namespace App\Console\Commands;

use App\Models\ParcoursAction;
use App\Models\ParcoursEvenement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * « Actions au pouvoir » — import MÉCANIQUE (itération 2 §1).
 * Critère public et documenté sur /methodologie : projets de loi (typloicod='pjl',
 * portés par le gouvernement) promulgués au JO (loidatjo) pendant une fonction de
 * Premier ministre du candidat. Aucune sélection éditoriale : la période et le type
 * suffisent. Tout entre en statut detecte ; explication factuelle générée
 * (le modérateur peut l'amender avant publication).
 */
class PresidentielleImportActions extends Command
{
    protected $signature = 'presidentielle:import-actions {--candidat= : slug, sinon tous}';

    protected $description = 'Importe les actions au pouvoir (lois portées sous période Premier ministre) — critère mécanique.';

    public function handle(): int
    {
        $evenements = ParcoursEvenement::where('type', 'fonction_gouvernementale')
            ->where('titre', 'like', 'Premier ministre%')
            ->whereNotNull('date_debut')->whereNotNull('date_fin')
            ->with('personnePolitique')
            ->when($this->option('candidat'), fn ($q, $slug) => $q->whereHas('personnePolitique', fn ($p) => $p->where('slug', $slug)))
            ->get();

        $total = 0;
        foreach ($evenements as $evt) {
            $lois = DB::table('senat_dosleg_loi')
                ->where('typloicod', 'pjl')
                ->whereNotNull('loidatjo')
                ->whereBetween('loidatjo', [$evt->date_debut, $evt->date_fin])
                ->orderBy('loidatjo')
                ->get(['loicod', 'loiint', 'loidatjo', 'url_jo', 'signet']);

            $n = 0;
            foreach ($lois as $loi) {
                $titre = Str::limit('Loi '.trim((string) $loi->loiint), 295, '…');
                $date = Str::before((string) $loi->loidatjo, ' ');

                $existe = ParcoursAction::withTrashed()
                    ->where('parcours_evenement_id', $evt->id)
                    ->where('type', 'loi_portee')
                    ->where('reference_id', trim((string) $loi->loicod))
                    ->exists();
                if ($existe) {
                    continue;
                }

                ParcoursAction::create([
                    'parcours_evenement_id' => $evt->id,
                    'type' => 'loi_portee',
                    'reference_type' => 'senat_dosleg_loi',
                    'reference_id' => trim((string) $loi->loicod),
                    'titre_court' => $titre,
                    'date_action' => $date ?: null,
                    'source_url' => $loi->url_jo ?: null,
                    'source_detection' => 'mecanique',
                    'critere' => 'Projet de loi promulgué au JO pendant la fonction de Premier ministre (dates de promulgation)',
                    'explication' => sprintf(
                        'Projet de loi porté par le gouvernement, promulgué le %s, pendant la période où %s était Premier ministre. Sélection mécanique par date de promulgation — aucun choix éditorial.',
                        $date, $evt->personnePolitique?->nom_complet ?? 'le candidat'
                    ),
                    'statut_validation' => 'detecte',
                    'affiche_publiquement' => false,
                ]);
                $n++;
            }

            $this->line(sprintf('  %s — %s : %d loi(s) importée(s)', $evt->personnePolitique?->nom_complet, $evt->organisation, $n));
            $total += $n;
        }

        $this->info("{$total} action(s) importée(s) en statut detecte (validation humaine avant publication).");

        return self::SUCCESS;
    }
}
