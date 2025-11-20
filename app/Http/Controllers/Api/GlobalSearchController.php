<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\ScrutinAN;
use App\Models\DossierLegislatifAN;
use App\Models\AmendementAN;
use App\Models\Topic;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalSearchController extends Controller
{
    /**
     * Recherche globale unifiée
     * 
     * GET /api/search?q=climat&types[]=deputes&types[]=scrutins&tags[]=environnement
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $types = $request->input('types', []); // Filtrer par types
        $tags = $request->input('tags', []); // Filtrer par tags
        $limit = min($request->input('limit', 5), 20); // Max 20 par type

        if (strlen($query) < 2 && empty($tags)) {
            return response()->json([
                'query' => $query,
                'results' => [],
                'total' => 0,
            ]);
        }

        $results = [];
        $total = 0;

        // 1. DÉPUTÉS
        if (empty($types) || in_array('deputes', $types)) {
            $deputes = ActeurAN::query()
                ->where(function ($q) use ($query) {
                    if (strlen($query) >= 2) {
                        $q->where('nom', 'ILIKE', "%{$query}%")
                            ->orWhere('prenom', 'ILIKE', "%{$query}%")
                            ->orWhere('profession', 'ILIKE', "%{$query}%")
                            ->orWhereRaw("CONCAT(prenom, ' ', nom) ILIKE ?", ["%{$query}%"]);
                    }
                })
                ->with(['mandatActif', 'mandatActif.organe'])
                ->limit($limit)
                ->get()
                ->map(function ($depute) {
                    return [
                        'type' => 'depute',
                        'id' => $depute->uid,
                        'title' => $depute->prenom . ' ' . $depute->nom,
                        'subtitle' => $depute->mandatActif?->organe?->libelle ?? 'Député',
                        'description' => $depute->profession,
                        'url' => route('representants.deputes.show', $depute->uid),
                        'image' => $depute->photo_url,
                        'badge' => $depute->mandatActif?->organe?->libelleAbrev,
                    ];
                });

            $results['deputes'] = $deputes;
            $total += $deputes->count();
        }

        // 2. SÉNATEURS
        if (empty($types) || in_array('senateurs', $types)) {
            $senateurs = Senateur::query()
                ->where(function ($q) use ($query) {
                    if (strlen($query) >= 2) {
                        $q->where('nom', 'ILIKE', "%{$query}%")
                            ->orWhere('prenom', 'ILIKE', "%{$query}%")
                            ->orWhere('profession', 'ILIKE', "%{$query}%")
                            ->orWhereRaw("CONCAT(prenom, ' ', nom) ILIKE ?", ["%{$query}%"]);
                    }
                })
                ->actifs()
                ->limit($limit)
                ->get()
                ->map(function ($senateur) {
                    return [
                        'type' => 'senateur',
                        'id' => $senateur->matricule,
                        'title' => $senateur->prenom . ' ' . $senateur->nom,
                        'subtitle' => 'Sénateur',
                        'description' => $senateur->profession,
                        'url' => route('representants.senateurs.show', $senateur->matricule),
                        'image' => $senateur->photo_url,
                        'badge' => $senateur->groupeParlementaireActuel?->sigle,
                    ];
                });

            $results['senateurs'] = $senateurs;
            $total += $senateurs->count();
        }

        // 3. SCRUTINS
        if (empty($types) || in_array('scrutins', $types)) {
            $scrutinsQuery = ScrutinAN::query()
                ->where(function ($q) use ($query) {
                    if (strlen($query) >= 2) {
                        $q->where('titre', 'ILIKE', "%{$query}%")
                            ->orWhere('objet', 'ILIKE', "%{$query}%");
                    }
                });

            // Filtrer par tags
            if (!empty($tags)) {
                $scrutinsQuery->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('slug', $tags);
                });
            }

            $scrutins = $scrutinsQuery
                ->orderBy('date_scrutin', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($scrutin) {
                    return [
                        'type' => 'scrutin',
                        'id' => $scrutin->uid,
                        'title' => 'Scrutin n°' . $scrutin->numero,
                        'subtitle' => $scrutin->date_scrutin?->format('d/m/Y'),
                        'description' => $scrutin->titre,
                        'url' => route('legislation.scrutins.show', $scrutin->uid),
                        'badge' => $scrutin->resultat_libelle,
                        'tags' => $scrutin->tags->pluck('slug')->toArray(),
                    ];
                });

            $results['scrutins'] = $scrutins;
            $total += $scrutins->count();
        }

        // 4. DOSSIERS LÉGISLATIFS
        if (empty($types) || in_array('dossiers', $types)) {
            $dossiersQuery = DossierLegislatifAN::query()
                ->where(function ($q) use ($query) {
                    if (strlen($query) >= 2) {
                        $q->where('titre', 'ILIKE', "%{$query}%")
                            ->orWhere('titre_court', 'ILIKE', "%{$query}%");
                    }
                });

            // Filtrer par tags
            if (!empty($tags)) {
                $dossiersQuery->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('slug', $tags);
                });
            }

            $dossiers = $dossiersQuery
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($dossier) {
                    return [
                        'type' => 'dossier',
                        'id' => $dossier->uid,
                        'title' => $dossier->titre_court ?: $dossier->titre,
                        'subtitle' => 'Législature ' . $dossier->legislature,
                        'description' => $dossier->titre,
                        'url' => route('legislation.dossiers.show', $dossier->uid),
                        'tags' => $dossier->tags->pluck('slug')->toArray(),
                    ];
                });

            $results['dossiers'] = $dossiers;
            $total += $dossiers->count();
        }

        // 5. AMENDEMENTS
        if (empty($types) || in_array('amendements', $types)) {
            $amendements = AmendementAN::query()
                ->where(function ($q) use ($query) {
                    if (strlen($query) >= 2) {
                        $q->where('numero', 'ILIKE', "%{$query}%")
                            ->orWhere('dispositif', 'ILIKE', "%{$query}%")
                            ->orWhere('expose_motifs', 'ILIKE', "%{$query}%");
                    }
                })
                ->with(['auteur', 'texte'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($amendement) {
                    return [
                        'type' => 'amendement',
                        'id' => $amendement->uid,
                        'title' => 'Amendement ' . $amendement->numero,
                        'subtitle' => $amendement->auteur ? ($amendement->auteur->prenom . ' ' . $amendement->auteur->nom) : 'Auteur inconnu',
                        'description' => substr($amendement->dispositif, 0, 150) . '...',
                        'url' => route('legislation.amendements.show', $amendement->uid),
                        'badge' => $amendement->etat_libelle,
                    ];
                });

            $results['amendements'] = $amendements;
            $total += $amendements->count();
        }

        // 6. TOPICS (DÉBATS CITOYENS)
        if (empty($types) || in_array('topics', $types)) {
            $topicsQuery = Topic::query()
                ->where(function ($q) use ($query) {
                    if (strlen($query) >= 2) {
                        $q->where('title', 'ILIKE', "%{$query}%")
                            ->orWhere('description', 'ILIKE', "%{$query}%");
                    }
                });

            // Filtrer par tags
            if (!empty($tags)) {
                $topicsQuery->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('slug', $tags);
                });
            }

            $topics = $topicsQuery
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($topic) {
                    return [
                        'type' => 'topic',
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'subtitle' => 'Par ' . ($topic->user->name ?? 'Anonyme'),
                        'description' => substr($topic->description, 0, 150) . '...',
                        'url' => route('topics.show', $topic->id),
                        'badge' => $topic->comments_count . ' commentaires',
                        'tags' => $topic->tags->pluck('slug')->toArray(),
                    ];
                });

            $results['topics'] = $topics;
            $total += $topics->count();
        }

        return response()->json([
            'query' => $query,
            'tags' => $tags,
            'results' => $results,
            'total' => $total,
        ]);
    }

    /**
     * Suggestions de recherche (autocomplete)
     * 
     * GET /api/search/suggestions?q=cli
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = [];

        // Députés populaires
        $deputes = ActeurAN::where('nom', 'ILIKE', "%{$query}%")
            ->orWhere('prenom', 'ILIKE', "%{$query}%")
            ->limit(3)
            ->get(['uid', 'nom', 'prenom'])
            ->map(fn($d) => [
                'text' => $d->prenom . ' ' . $d->nom,
                'type' => 'depute',
                'url' => route('representants.deputes.show', $d->uid),
            ]);

        // Scrutins récents
        $scrutins = ScrutinAN::where('titre', 'ILIKE', "%{$query}%")
            ->orderBy('date_scrutin', 'desc')
            ->limit(3)
            ->get(['uid', 'numero', 'titre'])
            ->map(fn($s) => [
                'text' => 'Scrutin n°' . $s->numero . ' : ' . substr($s->titre, 0, 50),
                'type' => 'scrutin',
                'url' => route('legislation.scrutins.show', $s->uid),
            ]);

        // Tags correspondants
        $tags = Tag::where('name', 'ILIKE', "%{$query}%")
            ->limit(3)
            ->get(['slug', 'name', 'icon'])
            ->map(fn($t) => [
                'text' => $t->icon . ' ' . $t->name,
                'type' => 'tag',
                'slug' => $t->slug,
            ]);

        $suggestions = array_merge(
            $deputes->toArray(),
            $scrutins->toArray(),
            $tags->toArray()
        );

        return response()->json(['suggestions' => $suggestions]);
    }
}

