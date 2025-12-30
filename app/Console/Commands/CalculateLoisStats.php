<?php

namespace App\Console\Commands;

use App\Models\Loi;
use App\Models\LoiStats;
use App\Models\ScrutinAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateLoisStats extends Command
{
    protected $signature = 'calculate:lois-stats 
                            {--limit= : Limiter à N lois (pour tests)}
                            {--force : Recalculer même si les stats sont récentes}';

    protected $description = 'Calcule et met à jour les statistiques pré-calculées des lois';

    public function handle(): int
    {
        $this->info("🔄 Calcul des statistiques des lois...");
        $startTime = now();

        $query = Loi::query();

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $lois = $query->get();
        $force = $this->option('force');

        $this->output->progressStart($lois->count());
        $calculated = 0;
        $skipped = 0;

        foreach ($lois as $loi) {
            // Vérifier si les stats sont récentes (sauf si --force)
            if (!$force) {
                $existing = LoiStats::forLoi($loi->loicod);
                if ($existing && !$existing->isStale()) {
                    $skipped++;
                    $this->output->progressAdvance();
                    continue;
                }
            }

            try {
                $stats = $this->calculateLoiStats($loi);
                LoiStats::updateLoiStats($loi->loicod, $stats);
                $calculated++;
            } catch (\Exception $e) {
                $this->error("Erreur pour {$loi->loicod}: {$e->getMessage()}");
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $duration = $startTime->diffInSeconds(now());
        $this->info("✅ {$calculated} lois calculées, {$skipped} ignorées (récentes) en {$duration}s");

        return Command::SUCCESS;
    }

    /**
     * Calculer les stats d'une loi spécifique
     */
    private function calculateLoiStats(Loi $loi): array
    {
        // Charger le parcours via la méthode existante
        $parcours = $loi->getParcours();
        $etapesTotal = count($parcours);
        
        // Compter les étapes par chambre
        $etapesAN = 0;
        $etapesSenat = 0;
        $amendementsTotal = 0;
        $amendementsAdoptes = 0;
        $datePremiereEtape = null;
        $dateDerniereEtape = null;
        
        foreach ($parcours as $etape) {
            $chambre = strtolower($etape['chambre'] ?? '');
            if (str_contains($chambre, 'assemblée') || $chambre === 'an') {
                $etapesAN++;
            } elseif (str_contains($chambre, 'sénat') || $chambre === 'senat') {
                $etapesSenat++;
            }
            
            // Amendements
            $amendementsTotal += $etape['nb_amendements'] ?? 0;
            $amendementsAdoptes += $etape['amendements_adoptes'] ?? 0;
            
            // Dates
            if (isset($etape['date'])) {
                $date = $etape['date'];
                if (!$datePremiereEtape || $date < $datePremiereEtape) {
                    $datePremiereEtape = $date;
                }
                if (!$dateDerniereEtape || $date > $dateDerniereEtape) {
                    $dateDerniereEtape = $date;
                }
            }
        }

        // Fallback sur date_depot si pas de dates
        if (!$datePremiereEtape) {
            $datePremiereEtape = $loi->datloi;
        }
        
        // Calcul de durée
        $dureeJours = null;
        if ($datePremiereEtape) {
            $debut = $datePremiereEtape instanceof \Carbon\Carbon 
                ? $datePremiereEtape 
                : \Carbon\Carbon::parse($datePremiereEtape);
            $fin = $dateDerniereEtape 
                ? ($dateDerniereEtape instanceof \Carbon\Carbon ? $dateDerniereEtape : \Carbon\Carbon::parse($dateDerniereEtape))
                : now();
            $dureeJours = $debut->diffInDays($fin);
        }

        $tauxAdoption = $amendementsTotal > 0 
            ? ($amendementsAdoptes / $amendementsTotal) * 100 
            : 0;

        // Scrutins liés (recherche par dossier_ref ou numéro de loi)
        $scrutinsTotal = 0;
        $scrutinsAdoptes = 0;
        $scrutinsRejetes = 0;

        try {
            // Recherche via dossier_legislatif_uid
            if ($loi->dossier_ref) {
                $scrutins = ScrutinAN::where('dossier_legislatif_uid', $loi->dossier_ref)->get();
                $scrutinsTotal = $scrutins->count();
                $scrutinsAdoptes = $scrutins->filter(fn($s) => 
                    $s->resultat_code === 'adopté' || str_contains(strtolower($s->resultat_libelle ?? ''), 'adopt')
                )->count();
                $scrutinsRejetes = $scrutins->filter(fn($s) => 
                    $s->resultat_code === 'rejeté' || str_contains(strtolower($s->resultat_libelle ?? ''), 'rejet')
                )->count();
            }

            // Recherche par numéro de loi dans le titre du scrutin si pas de résultats
            if ($scrutinsTotal === 0 && $loi->numcod) {
                $scrutins = ScrutinAN::where('titre', 'ILIKE', "%{$loi->numcod}%")->get();
                $scrutinsTotal = $scrutins->count();
                $scrutinsAdoptes = $scrutins->filter(fn($s) => 
                    str_contains(strtolower($s->resultat_libelle ?? $s->resultat_code ?? ''), 'adopt')
                )->count();
                $scrutinsRejetes = $scrutins->filter(fn($s) => 
                    str_contains(strtolower($s->resultat_libelle ?? $s->resultat_code ?? ''), 'rejet')
                )->count();
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de scrutins
        }

        // Score d'engagement (mesure pondérée de l'activité)
        $scoreEngagement = 
            ($amendementsTotal * 1) +           // Chaque amendement = 1 point
            ($amendementsAdoptes * 2) +          // Bonus pour amendements adoptés
            ($scrutinsTotal * 10) +              // Chaque scrutin = 10 points
            ($etapesTotal * 5);                  // Chaque étape = 5 points

        return [
            'etapes_total' => $etapesTotal,
            'etapes_an' => $etapesAN,
            'etapes_senat' => $etapesSenat,
            'amendements_total' => $amendementsTotal,
            'amendements_adoptes' => $amendementsAdoptes,
            'amendements_rejetes' => 0, // Non disponible dans les étapes
            'amendements_retires' => 0, // Non disponible dans les étapes
            'taux_adoption_amendements' => round($tauxAdoption, 2),
            'scrutins_total' => $scrutinsTotal,
            'scrutins_adoptes' => $scrutinsAdoptes,
            'scrutins_rejetes' => $scrutinsRejetes,
            'duree_jours' => $dureeJours,
            'date_premiere_etape' => $datePremiereEtape,
            'date_derniere_etape' => $dateDerniereEtape,
            'score_engagement' => $scoreEngagement,
        ];
    }
}
