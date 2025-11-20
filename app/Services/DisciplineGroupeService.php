<?php

namespace App\Services;

use App\Models\ActeurAN;
use App\Models\VoteIndividuelAN;
use Illuminate\Support\Facades\DB;

class DisciplineGroupeService
{
    /**
     * Calculer la discipline de groupe d'un député
     * 
     * @param ActeurAN $acteur
     * @param int $legislature
     * @return float Pourcentage de discipline (0-100)
     */
    public function calculateDiscipline(ActeurAN $acteur, int $legislature = 17): float
    {
        $groupeActuel = $acteur->groupe_politique_actuel;
        
        if (!$groupeActuel) {
            return 0;
        }

        // Récupérer tous les scrutins où le député a voté
        $votesDepute = VoteIndividuelAN::where('acteur_ref', $acteur->uid)
            ->whereHas('scrutin', fn($q) => $q->where('legislature', $legislature))
            ->with('scrutin')
            ->get();

        if ($votesDepute->isEmpty()) {
            return 0;
        }

        $conformes = 0;
        $total = 0;

        foreach ($votesDepute as $vote) {
            $scrutin = $vote->scrutin;
            
            // Calculer le vote majoritaire du groupe pour ce scrutin
            $voteMajoritaireGroupe = $this->getVoteMajoritaireGroupe(
                $scrutin->uid,
                $groupeActuel->uid
            );

            if ($voteMajoritaireGroupe) {
                $total++;
                if ($vote->position === $voteMajoritaireGroupe) {
                    $conformes++;
                }
            }
        }

        return $total > 0 ? round(($conformes / $total) * 100, 1) : 0;
    }

    /**
     * Obtenir le vote majoritaire d'un groupe pour un scrutin
     * 
     * @param string $scrutinUid
     * @param string $groupeUid
     * @return string|null 'pour', 'contre', ou 'abstention'
     */
    private function getVoteMajoritaireGroupe(string $scrutinUid, string $groupeUid): ?string
    {
        // Compter les votes du groupe pour ce scrutin
        $votes = VoteIndividuelAN::where('scrutin_ref', $scrutinUid)
            ->whereHas('acteur.mandats', function($q) use ($groupeUid) {
                $q->where('organe_ref', $groupeUid)
                  ->whereNull('date_fin');
            })
            ->select('position', DB::raw('count(*) as count'))
            ->groupBy('position')
            ->orderBy('count', 'desc')
            ->first();

        return $votes->position ?? null;
    }

    /**
     * Calculer les statistiques de discipline d'un groupe
     * 
     * @param string $groupeUid
     * @param int $legislature
     * @return array
     */
    public function getStatistiquesGroupe(string $groupeUid, int $legislature = 17): array
    {
        // Récupérer tous les députés du groupe
        $deputes = ActeurAN::whereHas('mandats', function($q) use ($groupeUid) {
            $q->where('organe_ref', $groupeUid)
              ->whereNull('date_fin');
        })->get();

        $disciplines = [];
        foreach ($deputes as $depute) {
            $disciplines[] = $this->calculateDiscipline($depute, $legislature);
        }

        if (empty($disciplines)) {
            return [
                'moyenne' => 0,
                'min' => 0,
                'max' => 0,
                'nb_deputes' => 0,
            ];
        }

        return [
            'moyenne' => round(array_sum($disciplines) / count($disciplines), 1),
            'min' => min($disciplines),
            'max' => max($disciplines),
            'nb_deputes' => count($disciplines),
        ];
    }

    /**
     * Obtenir les votes "rebelles" d'un député
     * (votes différents de la majorité du groupe)
     * 
     * @param ActeurAN $acteur
     * @param int $legislature
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getVotesRebelles(ActeurAN $acteur, int $legislature = 17, int $limit = 10)
    {
        $groupeActuel = $acteur->groupe_politique_actuel;
        
        if (!$groupeActuel) {
            return collect();
        }

        $votesDepute = VoteIndividuelAN::where('acteur_ref', $acteur->uid)
            ->whereHas('scrutin', fn($q) => $q->where('legislature', $legislature))
            ->with('scrutin')
            ->get();

        $votesRebelles = [];

        foreach ($votesDepute as $vote) {
            $scrutin = $vote->scrutin;
            $voteMajoritaireGroupe = $this->getVoteMajoritaireGroupe(
                $scrutin->uid,
                $groupeActuel->uid
            );

            // Si le député a voté différemment de son groupe
            if ($voteMajoritaireGroupe && $vote->position !== $voteMajoritaireGroupe) {
                $votesRebelles[] = [
                    'scrutin' => $scrutin,
                    'vote_depute' => $vote->position,
                    'vote_groupe' => $voteMajoritaireGroupe,
                    'date' => $scrutin->date_scrutin,
                ];
            }
        }

        // Trier par date décroissante et limiter
        usort($votesRebelles, fn($a, $b) => $b['date'] <=> $a['date']);
        
        return collect(array_slice($votesRebelles, 0, $limit));
    }
}


