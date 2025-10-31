<?php

namespace App\Services;

use App\Models\GroupeParlementaire;
use App\Models\PropositionLoi;
use App\Models\ThematiqueLegislation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ExportService
{
    /**
     * Exporter un groupe parlementaire en PDF
     */
    public function exportGroupe(int $groupeId): \Illuminate\Http\Response
    {
        $groupe = GroupeParlementaire::with(['votesGroupes.voteLegislatif'])
            ->findOrFail($groupeId);

        // Statistiques
        $stats = [
            'total_votes' => $groupe->votesGroupes->count(),
            'votes_pour' => $groupe->votesGroupes->where('position_groupe', 'pour')->count(),
            'votes_contre' => $groupe->votesGroupes->where('position_groupe', 'contre')->count(),
            'votes_abstention' => $groupe->votesGroupes->where('position_groupe', 'abstention')->count(),
        ];

        // Thématiques favorites
        $thematiques = $groupe->getThematiquesFavorites(5);

        $pdf = Pdf::loadView('pdf.groupe', [
            'groupe' => $groupe,
            'stats' => $stats,
            'thematiques' => $thematiques,
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $filename = "groupe-{$groupe->sigle}-" . now()->format('Y-m-d') . ".pdf";

        return $pdf->download($filename);
    }

    /**
     * Exporter une thématique en PDF
     */
    public function exportThematique(string $code): \Illuminate\Http\Response
    {
        $thematique = ThematiqueLegislation::with(['propositions' => function ($query) {
            $query->orderBy('date_depot', 'desc')->limit(50);
        }])->where('code', $code)->firstOrFail();

        // Statistiques
        $stats = [
            'total_propositions' => $thematique->propositions->count(),
            'par_source' => $thematique->propositions->groupBy('source')->map->count(),
            'par_statut' => $thematique->propositions->groupBy('statut')->map->count(),
        ];

        $pdf = Pdf::loadView('pdf.thematique', [
            'thematique' => $thematique,
            'propositions' => $thematique->propositions,
            'stats' => $stats,
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $filename = "thematique-{$thematique->code}-" . now()->format('Y-m-d') . ".pdf";

        return $pdf->download($filename);
    }

    /**
     * Exporter une proposition de loi en PDF
     */
    public function exportProposition(int $propositionId): \Illuminate\Http\Response
    {
        $proposition = PropositionLoi::with([
            'thematiques',
            'votesLegislatifs.votesGroupes.groupeParlementaire',
        ])->findOrFail($propositionId);

        $pdf = Pdf::loadView('pdf.proposition', [
            'proposition' => $proposition,
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $filename = "proposition-{$proposition->id}-" . now()->format('Y-m-d') . ".pdf";

        return $pdf->download($filename);
    }

    /**
     * Exporter les statistiques globales en PDF
     */
    public function exportStatistiques(array $filters = []): \Illuminate\Http\Response
    {
        // Groupes parlementaires
        $groupes = GroupeParlementaire::where('actif', true)
            ->orderBy('nombre_membres', 'desc')
            ->get();

        // Thématiques
        $thematiques = ThematiqueLegislation::withCount('propositions')
            ->orderBy('propositions_count', 'desc')
            ->limit(10)
            ->get();

        // Propositions récentes
        $propositions = PropositionLoi::orderBy('date_depot', 'desc')
            ->limit(20)
            ->get();

        $stats = [
            'total_groupes' => $groupes->count(),
            'total_membres' => $groupes->sum('nombre_membres'),
            'total_thematiques' => ThematiqueLegislation::count(),
            'total_propositions' => PropositionLoi::count(),
        ];

        $pdf = Pdf::loadView('pdf.statistiques', [
            'groupes' => $groupes,
            'thematiques' => $thematiques,
            'propositions' => $propositions,
            'stats' => $stats,
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $filename = "statistiques-" . now()->format('Y-m-d') . ".pdf";

        return $pdf->download($filename);
    }

    /**
     * Exporter une comparaison de groupes en PDF
     */
    public function exportComparaison(array $groupeIds): \Illuminate\Http\Response
    {
        $groupes = GroupeParlementaire::with(['votesGroupes'])
            ->whereIn('id', $groupeIds)
            ->get();

        // Calculer les statistiques pour chaque groupe
        $comparaison = $groupes->map(function ($groupe) {
            return [
                'groupe' => $groupe,
                'stats' => $groupe->getStatistiquesVote(),
                'thematiques' => $groupe->getThematiquesFavorites(5),
            ];
        });

        $pdf = Pdf::loadView('pdf.comparaison', [
            'comparaison' => $comparaison,
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $filename = "comparaison-groupes-" . now()->format('Y-m-d') . ".pdf";

        return $pdf->download($filename);
    }
}

