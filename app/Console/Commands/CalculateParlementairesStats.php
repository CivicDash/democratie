<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\AmendementAN;
use App\Models\AmendementSenat;
use App\Models\ParlementaireStats;
use App\Models\ScrutinAN;
use App\Models\ScrutinSenat;
use App\Models\Senateur;
use App\Models\VoteIndividuelAN;
use App\Models\VoteSenat;
use App\Services\DisciplineGroupeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateParlementairesStats extends Command
{
    protected $signature = 'calculate:parlementaires-stats 
                            {--type=all : Type de parlementaires (deputes, senateurs, all)}
                            {--legislature=17 : Législature pour les députés}
                            {--force : Recalculer même si les stats sont récentes}';

    protected $description = 'Calcule et met à jour les statistiques pré-calculées des parlementaires';

    private DisciplineGroupeService $disciplineService;
    private int $totalScrutinsAN = 0;
    private int $totalScrutinsSenat = 0;

    public function handle(): int
    {
        $this->disciplineService = app(DisciplineGroupeService::class);
        $type = $this->option('type');
        $legislature = (int) $this->option('legislature');
        $force = $this->option('force');

        $this->info("🔄 Calcul des statistiques parlementaires...");
        $startTime = now();

        // Pré-charger le nombre total de scrutins
        $this->totalScrutinsAN = ScrutinAN::where('legislature', $legislature)->count();
        $this->totalScrutinsSenat = ScrutinSenat::count();

        $this->info("📊 Référence: {$this->totalScrutinsAN} scrutins AN (L{$legislature}), {$this->totalScrutinsSenat} scrutins Sénat");

        if ($type === 'all' || $type === 'deputes') {
            $this->calculateDeputesStats($legislature, $force);
        }

        if ($type === 'all' || $type === 'senateurs') {
            $this->calculateSenateursStats($force);
        }

        $duration = $startTime->diffInSeconds(now());
        $this->info("✅ Terminé en {$duration} secondes");

        return Command::SUCCESS;
    }

    /**
     * Calculer les stats de tous les députés
     */
    private function calculateDeputesStats(int $legislature, bool $force): void
    {
        $this->info("👥 Calcul des statistiques des députés (L{$legislature})...");

        // Récupérer tous les députés actifs
        $deputes = ActeurAN::whereHas('mandats', function ($q) use ($legislature) {
            $q->where('type_organe', 'ASSEMBLEE')
              ->whereNull('date_fin');
        })->get();

        $this->output->progressStart($deputes->count());
        $calculated = 0;
        $skipped = 0;

        foreach ($deputes as $depute) {
            // Vérifier si les stats sont récentes (sauf si --force)
            if (!$force) {
                $existing = ParlementaireStats::forDepute($depute->uid, $legislature);
                if ($existing && !$existing->isStale()) {
                    $skipped++;
                    $this->output->progressAdvance();
                    continue;
                }
            }

            try {
                $stats = $this->calculateDeputeStats($depute, $legislature);
                ParlementaireStats::updateDeputeStats($depute->uid, $legislature, $stats);
                $calculated++;
            } catch (\Exception $e) {
                $this->error("Erreur pour {$depute->uid}: {$e->getMessage()}");
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info("   → {$calculated} députés calculés, {$skipped} ignorés (récents)");
    }

    /**
     * Calculer les stats d'un député spécifique
     */
    private function calculateDeputeStats(ActeurAN $depute, int $legislature): array
    {
        // Votes
        $votesQuery = VoteIndividuelAN::where('acteur_ref', $depute->uid)
            ->whereHas('scrutin', fn($q) => $q->where('legislature', $legislature));

        $votesTotal = $votesQuery->count();
        $votesPour = (clone $votesQuery)->where('position', 'pour')->count();
        $votesContre = (clone $votesQuery)->where('position', 'contre')->count();
        $votesAbstention = (clone $votesQuery)->where('position', 'abstention')->count();

        $tauxPresence = $this->totalScrutinsAN > 0
            ? ($votesTotal / $this->totalScrutinsAN) * 100
            : 0;

        // Amendements
        $amendementsQuery = AmendementAN::where('auteur_acteur_ref', $depute->uid)
            ->where('legislature', $legislature);

        $amendementsTotal = $amendementsQuery->count();
        $amendementsAdoptes = (clone $amendementsQuery)->adoptes()->count();
        $amendementsRejetes = (clone $amendementsQuery)->rejetes()->count();
        $amendementsRetires = (clone $amendementsQuery)->retires()->count();

        $tauxAdoption = $amendementsTotal > 0
            ? ($amendementsAdoptes / $amendementsTotal) * 100
            : 0;

        // Discipline de groupe (calcul léger via cache si possible)
        $discipline = $this->disciplineService->calculateDiscipline($depute, $legislature);

        return [
            'votes_total' => $votesTotal,
            'votes_pour' => $votesPour,
            'votes_contre' => $votesContre,
            'votes_abstention' => $votesAbstention,
            'scrutins_total' => $this->totalScrutinsAN,
            'taux_presence' => round($tauxPresence, 2),
            'amendements_total' => $amendementsTotal,
            'amendements_adoptes' => $amendementsAdoptes,
            'amendements_rejetes' => $amendementsRejetes,
            'amendements_retires' => $amendementsRetires,
            'taux_adoption_amendements' => round($tauxAdoption, 2),
            'discipline_groupe' => $discipline,
            'votes_rebelles' => 0, // TODO: calculer si nécessaire
            'questions_total' => 0, // TODO: ajouter quand les questions seront importées
            'interventions_total' => 0,
        ];
    }

    /**
     * Calculer les stats de tous les sénateurs
     */
    private function calculateSenateursStats(bool $force): void
    {
        $this->info("🏛️ Calcul des statistiques des sénateurs...");

        $senateurs = Senateur::actifs()->get();

        $this->output->progressStart($senateurs->count());
        $calculated = 0;
        $skipped = 0;

        foreach ($senateurs as $senateur) {
            // Vérifier si les stats sont récentes (sauf si --force)
            if (!$force) {
                $existing = ParlementaireStats::forSenateur($senateur->matricule);
                if ($existing && !$existing->isStale()) {
                    $skipped++;
                    $this->output->progressAdvance();
                    continue;
                }
            }

            try {
                $stats = $this->calculateSenateurStats($senateur);
                ParlementaireStats::updateSenateurStats($senateur->matricule, $stats);
                $calculated++;
            } catch (\Exception $e) {
                $this->error("Erreur pour {$senateur->matricule}: {$e->getMessage()}");
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info("   → {$calculated} sénateurs calculés, {$skipped} ignorés (récents)");
    }

    /**
     * Calculer les stats d'un sénateur spécifique
     */
    private function calculateSenateurStats(Senateur $senateur): array
    {
        // Votes (use distinct scrutin count to avoid duplicates inflating the rate)
        $votesQuery = VoteSenat::where('senateur_matricule', $senateur->matricule);

        $votesTotal = $votesQuery->distinct('scrutin_id')->count('scrutin_id');
        $votesPour = (clone $votesQuery)->where('position', 'pour')->count();
        $votesContre = (clone $votesQuery)->where('position', 'contre')->count();
        $votesAbstention = (clone $votesQuery)->where('position', 'abstention')->count();

        $tauxPresence = $this->totalScrutinsSenat > 0
            ? min(100, ($votesTotal / $this->totalScrutinsSenat) * 100)
            : 0;

        // Amendements
        $amendementsQuery = AmendementSenat::where('senateur_matricule', $senateur->matricule);

        $amendementsTotal = $amendementsQuery->count();
        $amendementsAdoptes = (clone $amendementsQuery)->adoptes()->count();
        $amendementsRejetes = (clone $amendementsQuery)->rejetes()->count();
        $amendementsRetires = (clone $amendementsQuery)->retires()->count();

        $tauxAdoption = $amendementsTotal > 0
            ? ($amendementsAdoptes / $amendementsTotal) * 100
            : 0;

        return [
            'votes_total' => $votesTotal,
            'votes_pour' => $votesPour,
            'votes_contre' => $votesContre,
            'votes_abstention' => $votesAbstention,
            'scrutins_total' => $this->totalScrutinsSenat,
            'taux_presence' => round($tauxPresence, 2),
            'amendements_total' => $amendementsTotal,
            'amendements_adoptes' => $amendementsAdoptes,
            'amendements_rejetes' => $amendementsRejetes,
            'amendements_retires' => $amendementsRetires,
            'taux_adoption_amendements' => round($tauxAdoption, 2),
            'discipline_groupe' => null, // Pas calculé pour les sénateurs
            'votes_rebelles' => 0,
            'questions_total' => 0,
            'interventions_total' => 0,
        ];
    }
}
