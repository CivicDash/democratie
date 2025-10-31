<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropositionLoi;
use App\Models\LegalReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegalContextController extends Controller
{
    /**
     * Obtenir le contexte juridique d'une proposition
     */
    public function show(int $propositionId): JsonResponse
    {
        $proposition = PropositionLoi::findOrFail($propositionId);

        $references = LegalReference::with(['jurisprudences' => function ($query) {
                $query->orderByRelevance()->limit(5);
            }])
            ->forProposition($propositionId)
            ->synced()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'proposition' => [
                    'id' => $proposition->id,
                    'titre' => $proposition->titre,
                    'numero' => $proposition->numero,
                ],
                'has_legal_context' => $references->isNotEmpty(),
                'references_count' => $references->count(),
                'references' => $references->map(function ($ref) {
                    return [
                        'id' => $ref->id,
                        'reference' => $ref->reference_text,
                        'code_name' => $ref->code_name,
                        'article_type' => $ref->article_type,
                        'type_label' => $ref->type_label,
                        'article_current_text' => $ref->article_current_text,
                        'article_proposed_text' => $ref->article_proposed_text,
                        'context_description' => $ref->context_description,
                        'legifrance_url' => $ref->legifrance_url,
                        'jurisprudence_count' => $ref->jurisprudence_count,
                        'jurisprudences' => $ref->jurisprudences->map(function ($juri) {
                            return [
                                'id' => $juri->id,
                                'jurisdiction' => $juri->jurisdiction,
                                'jurisdiction_label' => $juri->jurisdiction_label,
                                'date_decision' => $juri->date_decision->format('Y-m-d'),
                                'title' => $juri->title,
                                'summary' => $juri->summary,
                                'relevance_score' => $juri->relevance_score,
                                'legifrance_url' => $juri->legifrance_url,
                                'decision_type' => $juri->decision_type,
                                'decision_type_label' => $juri->decision_type_label,
                            ];
                        }),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Obtenir le détail d'une référence juridique
     */
    public function showReference(int $referenceId): JsonResponse
    {
        $reference = LegalReference::with(['jurisprudences' => function ($query) {
                $query->orderByRelevance();
            }, 'propositionLoi'])
            ->findOrFail($referenceId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $reference->id,
                'reference' => $reference->reference_text,
                'code_name' => $reference->code_name,
                'article_type' => $reference->article_type,
                'type_label' => $reference->type_label,
                'article_current_text' => $reference->article_current_text,
                'article_proposed_text' => $reference->article_proposed_text,
                'context_description' => $reference->context_description,
                'matched_text' => $reference->matched_text,
                'legifrance_url' => $reference->legifrance_url,
                'is_range' => $reference->is_range,
                'range_start' => $reference->range_start,
                'range_end' => $reference->range_end,
                'proposition' => [
                    'id' => $reference->propositionLoi->id,
                    'titre' => $reference->propositionLoi->titre,
                    'numero' => $reference->propositionLoi->numero,
                ],
                'jurisprudences' => $reference->jurisprudences->map(function ($juri) {
                    return [
                        'id' => $juri->id,
                        'jurisdiction' => $juri->jurisdiction,
                        'jurisdiction_label' => $juri->jurisdiction_label,
                        'date_decision' => $juri->date_decision->format('Y-m-d'),
                        'title' => $juri->title,
                        'summary' => $juri->summary,
                        'relevance_score' => $juri->relevance_score,
                        'legifrance_url' => $juri->legifrance_url,
                        'decision_type' => $juri->decision_type,
                        'decision_type_label' => $juri->decision_type_label,
                        'themes' => $juri->themes,
                        'keywords' => $juri->keywords,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Statistiques du contexte juridique
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_references' => LegalReference::synced()->count(),
            'total_jurisprudences' => \DB::table('jurisprudence_links')->count(),
            'propositions_enriched' => PropositionLoi::whereHas('legalReferences', function ($query) {
                $query->synced();
            })->count(),
            'by_code' => LegalReference::synced()
                ->select('code_name', \DB::raw('count(*) as count'))
                ->groupBy('code_name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'by_article_type' => LegalReference::synced()
                ->select('article_type', \DB::raw('count(*) as count'))
                ->groupBy('article_type')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Déclencher une synchronisation manuelle
     */
    public function sync(int $propositionId): JsonResponse
    {
        $proposition = PropositionLoi::findOrFail($propositionId);

        try {
            \Artisan::call('legifrance:sync', ['proposition_id' => $propositionId]);
            
            $references = LegalReference::forProposition($propositionId)->synced()->count();

            return response()->json([
                'success' => true,
                'message' => 'Synchronisation lancée avec succès',
                'data' => [
                    'references_synced' => $references,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
