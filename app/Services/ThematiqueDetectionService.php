<?php

namespace App\Services;

use App\Models\PropositionLoi;
use App\Models\ThematiqueLegislation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service de détection et classification automatique des thématiques
 * pour les propositions de loi.
 * 
 * Utilise une approche par mots-clés avec scoring de pertinence.
 */
class ThematiqueDetectionService
{
    /**
     * Score minimum pour qu'une thématique soit considérée comme pertinente
     */
    private const SCORE_MINIMUM = 15;

    /**
     * Score minimum pour qu'une thématique soit considérée comme principale
     */
    private const SCORE_PRINCIPAL = 40;

    /**
     * Détecte automatiquement les thématiques d'une proposition de loi
     * 
     * @param PropositionLoi $proposition
     * @param bool $principal Si true, retourne uniquement la thématique principale
     * @param bool $attach Si true, attache les thématiques détectées à la proposition
     * @return Collection Collection de thématiques avec leur score
     */
    public function detecter(PropositionLoi $proposition, bool $principal = false, bool $attach = true): Collection
    {
        // Construire le texte à analyser
        $texte = $this->construireTexteAnalyse($proposition);

        // Récupérer toutes les thématiques
        $thematiques = ThematiqueLegislation::principales()->get();

        // Calculer le score pour chaque thématique
        $scores = collect();
        
        foreach ($thematiques as $thematique) {
            $score = $thematique->calculerScore($texte);
            
            if ($score >= self::SCORE_MINIMUM) {
                $scores->push([
                    'thematique' => $thematique,
                    'score' => $score,
                    'est_principal' => $score >= self::SCORE_PRINCIPAL,
                ]);
            }
        }

        // Trier par score décroissant
        $scores = $scores->sortByDesc('score');

        // Si on demande uniquement la principale
        if ($principal) {
            $scores = $scores->take(1);
        }

        // Si aucune thématique détectée, essayer une détection moins stricte
        if ($scores->isEmpty()) {
            Log::warning("Aucune thématique détectée pour proposition {$proposition->id}: {$proposition->titre}");
            
            // Fallback: prendre la thématique avec le meilleur score même si < SCORE_MINIMUM
            $fallback = null;
            $bestScore = 0;
            
            foreach ($thematiques as $thematique) {
                $score = $thematique->calculerScore($texte);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $fallback = $thematique;
                }
            }
            
            if ($fallback && $bestScore > 0) {
                $scores->push([
                    'thematique' => $fallback,
                    'score' => $bestScore,
                    'est_principal' => true,
                ]);
            }
        }

        // Attacher les thématiques à la proposition si demandé
        if ($attach && $scores->isNotEmpty()) {
            $this->attacherThematiques($proposition, $scores);
        }

        return $scores;
    }

    /**
     * Détecte et attache les thématiques pour plusieurs propositions en batch
     * 
     * @param Collection $propositions
     * @return array Statistiques du traitement
     */
    public function detecterBatch(Collection $propositions): array
    {
        $stats = [
            'total' => $propositions->count(),
            'avec_thematique' => 0,
            'sans_thematique' => 0,
            'erreurs' => 0,
        ];

        foreach ($propositions as $proposition) {
            try {
                $thematiques = $this->detecter($proposition, false, true);
                
                if ($thematiques->isNotEmpty()) {
                    $stats['avec_thematique']++;
                } else {
                    $stats['sans_thematique']++;
                }
            } catch (\Exception $e) {
                $stats['erreurs']++;
                Log::error("Erreur détection thématique pour proposition {$proposition->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Recalcule les thématiques d'une proposition
     * (supprime les anciennes et détecte de nouvelles)
     * 
     * @param PropositionLoi $proposition
     * @return Collection
     */
    public function recalculer(PropositionLoi $proposition): Collection
    {
        // Supprimer les thématiques auto-détectées
        $proposition->thematiques()
            ->wherePivot('tagged_by', 'auto')
            ->detach();

        // Redétecter
        return $this->detecter($proposition, false, true);
    }

    /**
     * Attache manuellement une thématique à une proposition
     * 
     * @param PropositionLoi $proposition
     * @param ThematiqueLegislation $thematique
     * @param bool $estPrincipal
     * @param int|null $userId
     * @return void
     */
    public function attacherManuellement(
        PropositionLoi $proposition,
        ThematiqueLegislation $thematique,
        bool $estPrincipal = false,
        ?int $userId = null
    ): void {
        // Si on marque comme principal, désactiver les autres principales
        if ($estPrincipal) {
            $proposition->thematiques()
                ->updateExistingPivot(
                    $proposition->thematiques()->pluck('thematiques_legislation.id'),
                    ['est_principal' => false]
                );
        }

        // Attacher la thématique
        $proposition->thematiques()->syncWithoutDetaching([
            $thematique->id => [
                'est_principal' => $estPrincipal,
                'confiance' => 100,
                'tags_keywords' => null,
                'tagged_by' => $userId ? "user_{$userId}" : 'manual',
            ],
        ]);

        // Incrémenter le compteur
        $thematique->incrementNbPropositions();
    }

    /**
     * Construit le texte à analyser à partir d'une proposition
     * 
     * @param PropositionLoi $proposition
     * @return string
     */
    private function construireTexteAnalyse(PropositionLoi $proposition): string
    {
        $parties = [];

        // Titre (poids 3x)
        if ($proposition->titre) {
            $parties[] = str_repeat($proposition->titre . ' ', 3);
        }

        // Résumé (poids 2x)
        if ($proposition->resume) {
            $parties[] = str_repeat($proposition->resume . ' ', 2);
        }

        // Texte intégral (poids 1x, limité à 5000 caractères pour perf)
        if ($proposition->texte_integral) {
            $parties[] = mb_substr($proposition->texte_integral, 0, 5000);
        }

        // Thème (si existe déjà, poids 2x)
        if ($proposition->theme) {
            $parties[] = str_repeat($proposition->theme . ' ', 2);
        }

        return implode(' ', $parties);
    }

    /**
     * Attache les thématiques détectées à la proposition
     * 
     * @param PropositionLoi $proposition
     * @param Collection $scores
     * @return void
     */
    private function attacherThematiques(PropositionLoi $proposition, Collection $scores): void
    {
        $attachments = [];

        foreach ($scores as $item) {
            $thematique = $item['thematique'];
            $score = $item['score'];
            $estPrincipal = $item['est_principal'];

            $attachments[$thematique->id] = [
                'est_principal' => $estPrincipal,
                'confiance' => $score,
                'tags_keywords' => json_encode($this->extraireMotsCles($proposition, $thematique)),
                'tagged_by' => 'auto',
            ];
        }

        // Sync les thématiques (remplace les anciennes auto-détectées)
        $existingManual = $proposition->thematiques()
            ->wherePivot('tagged_by', '!=', 'auto')
            ->pluck('thematiques_legislation.id')
            ->toArray();

        $proposition->thematiques()->sync(array_merge(
            $attachments,
            array_fill_keys($existingManual, []) // Garder les manuelles
        ));

        // Incrémenter les compteurs
        foreach ($scores as $item) {
            $item['thematique']->recalculerNbPropositions();
        }
    }

    /**
     * Extrait les mots-clés trouvés dans le texte pour une thématique
     * 
     * @param PropositionLoi $proposition
     * @param ThematiqueLegislation $thematique
     * @return array
     */
    private function extraireMotsCles(PropositionLoi $proposition, ThematiqueLegislation $thematique): array
    {
        $texte = mb_strtolower($this->construireTexteAnalyse($proposition));
        $motsClesTrouves = [];

        foreach ($thematique->mots_cles_combines as $motCle) {
            $motCle = mb_strtolower($motCle);
            if (str_contains($texte, $motCle)) {
                $motsClesTrouves[] = $motCle;
            }
        }

        return $motsClesTrouves;
    }

    /**
     * Obtient les statistiques de détection pour le dashboard admin
     * 
     * @return array
     */
    public function getStatistiques(): array
    {
        $total = PropositionLoi::count();
        $avecThematique = PropositionLoi::has('thematiques')->count();
        $autoDetectees = PropositionLoi::whereHas('thematiques', function ($query) {
            $query->where('tagged_by', 'auto');
        })->count();

        return [
            'total_propositions' => $total,
            'avec_thematique' => $avecThematique,
            'sans_thematique' => $total - $avecThematique,
            'auto_detectees' => $autoDetectees,
            'manuelles' => $avecThematique - $autoDetectees,
            'taux_couverture' => $total > 0 ? round(($avecThematique / $total) * 100, 2) : 0,
        ];
    }
}

