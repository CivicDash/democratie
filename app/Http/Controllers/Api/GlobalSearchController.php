<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Loi;
use App\Models\Maire;
use App\Models\Senateur;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Recherche globale intelligente avec suggestions contextuelles
 */
class GlobalSearchController extends Controller
{
    /**
     * Recherche rapide pour suggestions (autocomplete)
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('q');
        
        if (!$query || strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $query = trim($query);
        $results = [];
        $limit = 3; // Limite par catégorie

        // Recherche parallèle dans toutes les catégories
        $results = array_merge(
            $this->searchDeputes($query, $limit),
            $this->searchSenateurs($query, $limit),
            $this->searchLois($query, $limit),
            $this->searchIdees($query, $limit),
            $this->searchMaires($query, $limit),
        );

        // Trier par pertinence (score de matching)
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        // Limiter à 10 résultats max
        $results = array_slice($results, 0, 10);

        return response()->json([
            'query' => $query,
            'results' => $results,
            'categories' => $this->getCategoryCounts($query),
        ]);
    }

    /**
     * Recherche complète avec pagination
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'category' => ['nullable', 'in:all,deputes,senateurs,lois,idees,maires'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = trim($validated['q']);
        $category = $validated['category'] ?? 'all';
        $limit = $validated['limit'] ?? 20;
        $page = $validated['page'] ?? 1;

        $results = [];

        if ($category === 'all' || $category === 'deputes') {
            $results['deputes'] = $this->searchDeputes($query, $category === 'all' ? 5 : $limit, true);
        }
        if ($category === 'all' || $category === 'senateurs') {
            $results['senateurs'] = $this->searchSenateurs($query, $category === 'all' ? 5 : $limit, true);
        }
        if ($category === 'all' || $category === 'lois') {
            $results['lois'] = $this->searchLois($query, $category === 'all' ? 5 : $limit, true);
        }
        if ($category === 'all' || $category === 'idees') {
            $results['idees'] = $this->searchIdees($query, $category === 'all' ? 5 : $limit, true);
        }
        if ($category === 'all' || $category === 'maires') {
            $results['maires'] = $this->searchMaires($query, $category === 'all' ? 5 : $limit, true);
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'category' => $category,
            'results' => $results,
            'total' => array_sum(array_map(fn($r) => count($r), $results)),
        ]);
    }

    // ========================================================================
    // SEARCH METHODS
    // ========================================================================

    private function searchDeputes(string $query, int $limit, bool $detailed = false): array
    {
        $deputes = ActeurAN::deputes()
            ->where(function ($q) use ($query) {
                $q->where('nom', 'ilike', "%{$query}%")
                  ->orWhere('prenom', 'ilike', "%{$query}%")
                  ->orWhereRaw("CONCAT(prenom, ' ', nom) ILIKE ?", ["%{$query}%"]);
            })
            ->limit($limit)
            ->get();

        return $deputes->map(function ($d) use ($query, $detailed) {
            $nomComplet = trim("{$d->prenom} {$d->nom}");
            $score = $this->calculateScore($nomComplet, $query);
            
            $result = [
                'id' => $d->uid,
                'type' => 'depute',
                'category' => 'Député',
                'icon' => '🏛️',
                'title' => $nomComplet,
                'subtitle' => $d->groupe_politique_actuel?->libelle_abrege ?? 'Assemblée nationale',
                'url' => route('representants.deputes.show', $d->uid),
                'photo_url' => $d->photo_url,
                'score' => $score,
            ];

            if ($detailed) {
                $result['groupe'] = $d->groupe_politique_actuel?->libelle ?? null;
                $result['circonscription'] = $d->circonscriptions->first()?->libelle ?? null;
            }

            return $result;
        })->toArray();
    }

    private function searchSenateurs(string $query, int $limit, bool $detailed = false): array
    {
        $senateurs = Senateur::actifs()
            ->where(function ($q) use ($query) {
                $q->where('nom_usuel', 'ilike', "%{$query}%")
                  ->orWhere('prenom_usuel', 'ilike', "%{$query}%")
                  ->orWhereRaw("CONCAT(prenom_usuel, ' ', nom_usuel) ILIKE ?", ["%{$query}%"])
                  ->orWhere('circonscription', 'ilike', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return $senateurs->map(function ($s) use ($query, $detailed) {
            $nomComplet = trim("{$s->prenom_usuel} {$s->nom_usuel}");
            $score = $this->calculateScore($nomComplet, $query);
            
            $result = [
                'id' => $s->matricule,
                'type' => 'senateur',
                'category' => 'Sénateur',
                'icon' => '🏰',
                'title' => $nomComplet,
                'subtitle' => $s->groupe_politique ?? $s->circonscription ?? 'Sénat',
                'url' => route('representants.senateurs.show', $s->matricule),
                'photo_url' => $s->photo_url,
                'score' => $score,
            ];

            if ($detailed) {
                $result['groupe'] = $s->groupe_politique;
                $result['circonscription'] = $s->circonscription;
            }

            return $result;
        })->toArray();
    }

    private function searchLois(string $query, int $limit, bool $detailed = false): array
    {
        $lois = Loi::with('etat')
            ->where(function ($q) use ($query) {
                $q->where('loitit', 'ilike', "%{$query}%")
                  ->orWhere('loiint', 'ilike', "%{$query}%")
                  ->orWhere('loinumjo', 'ilike', "%{$query}%");
            })
            ->orderByDesc('loidatjo')
            ->limit($limit)
            ->get();

        return $lois->map(function ($l) use ($query, $detailed) {
            $titre = $l->loitit ?: $l->loiint;
            $score = $this->calculateScore($titre ?? '', $query);
            
            $etat = $l->etat?->etaloilib ?? 'En cours';
            $etatIcon = match (trim($l->etaloicod ?? '')) {
                '04' => '✅',
                '03' => '❌',
                '01' => '🔄',
                '05' => '⏰',
                default => '📜',
            };
            
            $result = [
                'id' => trim($l->loicod),
                'type' => 'loi',
                'category' => 'Loi',
                'icon' => $etatIcon,
                'title' => strlen($titre) > 80 ? substr($titre, 0, 80) . '...' : $titre,
                'subtitle' => trim($etat),
                'url' => route('lois.show', trim($l->loicod)),
                'photo_url' => null,
                'score' => $score,
            ];

            if ($detailed) {
                $result['date'] = $l->loidatjo?->format('d/m/Y');
                $result['numero'] = $l->loinumjo;
            }

            return $result;
        })->toArray();
    }

    private function searchIdees(string $query, int $limit, bool $detailed = false): array
    {
        $topics = Topic::published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'ilike', "%{$query}%")
                  ->orWhere('description', 'ilike', "%{$query}%");
            })
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        $ideaIcons = [
            'proposal' => '💡',
            'question' => '❓',
            'debate' => '💬',
            'petition' => '📜',
            'interpellation' => '📣',
        ];

        return $topics->map(function ($t) use ($query, $ideaIcons, $detailed) {
            $score = $this->calculateScore($t->title, $query);
            
            $result = [
                'id' => $t->id,
                'type' => 'idee',
                'category' => 'Idée citoyenne',
                'icon' => $ideaIcons[$t->idea_type] ?? '💡',
                'title' => strlen($t->title) > 60 ? substr($t->title, 0, 60) . '...' : $t->title,
                'subtitle' => $t->author?->name ?? 'Citoyen',
                'url' => route('participation.ideas.show', $t->slug ?: $t->id),
                'photo_url' => null,
                'score' => $score,
            ];

            if ($detailed) {
                $result['votes'] = $t->votes_pour - $t->votes_contre;
                $result['idea_type'] = $t->idea_type;
            }

            return $result;
        })->toArray();
    }

    private function searchMaires(string $query, int $limit, bool $detailed = false): array
    {
        // Rechercher uniquement si le terme ressemble à un nom ou une commune
        $maires = Maire::where(function ($q) use ($query) {
                $q->where('nom', 'ilike', "%{$query}%")
                  ->orWhere('prenom', 'ilike', "%{$query}%")
                  ->orWhere('nom_commune', 'ilike', "%{$query}%")
                  ->orWhereRaw("CONCAT(prenom, ' ', nom) ILIKE ?", ["%{$query}%"]);
            })
            ->limit($limit)
            ->get();

        return $maires->map(function ($m) use ($query, $detailed) {
            $nomComplet = $m->nom_complet ?? trim("{$m->prenom} {$m->nom}");
            $score = $this->calculateScore($nomComplet, $query);
            // Boost si la commune match
            if (stripos($m->nom_commune, $query) !== false) {
                $score += 20;
            }
            
            $result = [
                'id' => $m->id,
                'type' => 'maire',
                'category' => 'Maire',
                'icon' => '🏘️',
                'title' => $nomComplet,
                'subtitle' => $m->nom_commune ?? 'Maire',
                'url' => "#", // Pas de page dédiée pour les maires
                'photo_url' => $m->photo_url,
                'score' => $score,
            ];

            if ($detailed) {
                $result['commune'] = $m->nom_commune;
                $result['departement'] = $m->nom_departement;
            }

            return $result;
        })->toArray();
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    private function calculateScore(string $text, string $query): int
    {
        $text = strtolower(trim($text));
        $query = strtolower(trim($query));
        
        // Match exact au début = score max
        if (str_starts_with($text, $query)) {
            return 100;
        }
        
        // Match exact quelque part
        if (str_contains($text, $query)) {
            return 80;
        }
        
        // Match partiel (mots)
        $words = explode(' ', $query);
        $matchedWords = 0;
        foreach ($words as $word) {
            if (strlen($word) >= 2 && str_contains($text, $word)) {
                $matchedWords++;
            }
        }
        
        if ($matchedWords > 0) {
            return 50 + ($matchedWords * 10);
        }
        
        return 30;
    }

    private function getCategoryCounts(string $query): array
    {
        // Retourne un comptage approximatif par catégorie
        // En production, on pourrait utiliser des requêtes COUNT optimisées
        return [
            ['key' => 'deputes', 'label' => 'Députés', 'icon' => '🏛️'],
            ['key' => 'senateurs', 'label' => 'Sénateurs', 'icon' => '🏰'],
            ['key' => 'lois', 'label' => 'Lois', 'icon' => '📜'],
            ['key' => 'idees', 'label' => 'Idées', 'icon' => '💡'],
            ['key' => 'maires', 'label' => 'Maires', 'icon' => '🏘️'],
        ];
    }
}
