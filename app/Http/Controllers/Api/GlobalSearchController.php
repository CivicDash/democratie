<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\CommuneArticle;
use App\Models\CommuneEvenement;
use App\Models\CommunePage;
use App\Models\Loi;
use App\Models\Maire;
use App\Models\PersonnePolitique;
use App\Models\Senateur;
use App\Models\Topic;
use App\Models\Ville;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        if (! $query || strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $query = trim($query);
        $results = [];
        $limit = 3; // Limite par catégorie

        // Recherche parallèle dans toutes les catégories
        $results = array_merge(
            $this->searchDeputes($query, $limit),
            $this->searchSenateurs($query, $limit),
            $this->searchMinistres($query, $limit),
            $this->searchPresidents($query, $limit),
            $this->searchLois($query, $limit),
            $this->searchIdees($query, $limit),
            $this->searchMaires($query, $limit),
            $this->searchVilles($query, $limit),
            $this->searchCommuneArticles($query, $limit),
            $this->searchCommuneEvenements($query, $limit),
        );

        // Trier par pertinence (score de matching)
        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

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
            'category' => ['nullable', 'in:all,deputes,senateurs,ministres,presidents,lois,idees,maires,villes,commune_articles,commune_evenements'],
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
        if ($category === 'all' || $category === 'ministres') {
            $results['ministres'] = $this->searchMinistres($query, $category === 'all' ? 5 : $limit, true);
        }
        if ($category === 'all' || $category === 'presidents') {
            $results['presidents'] = $this->searchPresidents($query, $category === 'all' ? 3 : $limit, true);
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
        if ($category === 'all' || $category === 'villes') {
            $results['villes'] = $this->searchVilles($query, $category === 'all' ? 5 : $limit, true);
        }
        if ($category === 'all' || $category === 'commune_articles') {
            $results['commune_articles'] = $this->searchCommuneArticles($query, $category === 'all' ? 3 : $limit, true);
        }
        if ($category === 'all' || $category === 'commune_evenements') {
            $results['commune_evenements'] = $this->searchCommuneEvenements($query, $category === 'all' ? 3 : $limit, true);
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'category' => $category,
            'results' => $results,
            'total' => array_sum(array_map(fn ($r) => count($r), $results)),
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
                'title' => strlen($titre) > 80 ? substr($titre, 0, 80).'...' : $titre,
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
                'title' => strlen($t->title) > 60 ? substr($t->title, 0, 60).'...' : $t->title,
                'subtitle' => $t->author?->display_name ?? 'Citoyen',
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

            // Récupérer la ville associée pour l'URL
            $villeUrl = '#';
            if ($m->ville_id) {
                $ville = Ville::find($m->ville_id);
                if ($ville) {
                    $villeUrl = $ville->url;
                }
            } elseif ($m->code_commune) {
                $ville = Ville::where('code_insee', $m->code_commune)->first();
                if ($ville) {
                    $villeUrl = $ville->url;
                }
            }

            $result = [
                'id' => $m->id,
                'type' => 'maire',
                'category' => 'Maire',
                'icon' => '👔',
                'title' => $nomComplet,
                'subtitle' => $m->nom_commune ?? 'Maire',
                'url' => $villeUrl,
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

    private function searchVilles(string $query, int $limit, bool $detailed = false): array
    {
        // Recherche par nom de ville ou code postal
        $isPostalCode = preg_match('/^\d{2,5}$/', $query);

        $villes = Ville::where('arrondissement_municipal', false)
            ->where(function ($q) use ($query, $isPostalCode) {
                $q->where('nom', 'ilike', "%{$query}%");

                if ($isPostalCode) {
                    $q->orWhere('code_postal_principal', 'like', "{$query}%")
                        ->orWhere('code_insee', 'like', "{$query}%");
                }
            })
            ->whereNotNull('population')
            ->orderByDesc('population')
            ->limit($limit)
            ->with('maireActuel:id,nom,prenom,civilite')
            ->get();

        return $villes->map(function ($v) use ($query, $detailed, $isPostalCode) {
            $score = $this->calculateScore($v->nom, $query);

            // Boost si code postal match
            if ($isPostalCode && str_starts_with($v->code_postal_principal ?? '', $query)) {
                $score += 30;
            }

            // Boost grandes villes
            if ($v->population >= 100000) {
                $score += 15;
            } elseif ($v->population >= 50000) {
                $score += 10;
            }

            $result = [
                'id' => $v->id,
                'type' => 'ville',
                'category' => 'Ville',
                'icon' => '🏘️',
                'title' => $v->nom,
                'subtitle' => $v->code_postal_principal.' • '.($v->departement_nom ?? $v->departement_code),
                'url' => $v->url,
                'photo_url' => null,
                'score' => $score,
            ];

            if ($detailed) {
                $result['code_postal'] = $v->code_postal_principal;
                $result['code_insee'] = $v->code_insee;
                $result['population'] = $v->population;
                $result['population_formate'] = $v->population_formate;
                $result['departement'] = $v->departement_nom;
                $result['region'] = $v->region_nom;
                $result['maire'] = $v->maireActuel
                    ? trim($v->maireActuel->prenom.' '.$v->maireActuel->nom)
                    : null;
            }

            return $result;
        })->toArray();
    }

    private function searchMinistres(string $query, int $limit, bool $detailed = false): array
    {
        $ministres = PersonnePolitique::whereHas('postes')
            ->where(function ($q) use ($query) {
                $q->where('nom', 'ilike', "%{$query}%")
                    ->orWhere('prenom', 'ilike', "%{$query}%")
                    ->orWhereRaw("CONCAT(prenom, ' ', nom) ILIKE ?", ["%{$query}%"]);
            })
            ->with(['postes' => function ($q) {
                $q->with('gouvernement')->orderByDesc('date_debut')->limit(1);
            }])
            ->limit($limit)
            ->get();

        return $ministres->map(function ($m) use ($query, $detailed) {
            $nomComplet = trim("{$m->prenom} {$m->nom}");
            $score = $this->calculateScore($nomComplet, $query);

            $posteActuel = $m->postes->first();
            $fonction = $posteActuel?->fonction ?? 'Ancien ministre';

            // Générer le slug pour l'URL
            $slug = Str::slug($nomComplet);

            $result = [
                'id' => $m->id,
                'type' => 'ministre',
                'category' => 'Ministre',
                'icon' => '👔',
                'title' => $nomComplet,
                'subtitle' => Str::limit($fonction, 50),
                'url' => route('gouvernement.personne', $slug),
                'photo_url' => $m->photo_url ?? $m->photo_wikipedia_url,
                'score' => $score,
            ];

            if ($detailed) {
                $result['fonction'] = $fonction;
                $result['gouvernement'] = $posteActuel?->gouvernement?->nom;
                $result['parti'] = $m->parti_politique;
            }

            return $result;
        })->toArray();
    }

    private function searchPresidents(string $query, int $limit, bool $detailed = false): array
    {
        // Liste statique des présidents de la Ve République
        $presidents = [
            [
                'nom' => 'Emmanuel Macron',
                'slug' => 'emmanuel-macron',
                'mandat' => '2017 - présent',
                'photo' => '/images/portraits_presidents/Emmanuel_Macron.avif',
                'actif' => true,
            ],
            [
                'nom' => 'François Hollande',
                'slug' => 'francois-hollande',
                'mandat' => '2012 - 2017',
                'photo' => '/images/portraits_presidents/François_Hollande.avif',
                'actif' => false,
            ],
            [
                'nom' => 'Nicolas Sarkozy',
                'slug' => 'nicolas-sarkozy',
                'mandat' => '2007 - 2012',
                'photo' => '/images/portraits_presidents/Nicolas_Sarkozy.avif',
                'actif' => false,
            ],
            [
                'nom' => 'Jacques Chirac',
                'slug' => 'jacques-chirac',
                'mandat' => '1995 - 2007',
                'photo' => '/images/portraits_presidents/Jacques_Chirac.avif',
                'actif' => false,
            ],
            [
                'nom' => 'François Mitterrand',
                'slug' => 'francois-mitterrand',
                'mandat' => '1981 - 1995',
                'photo' => '/images/portraits_presidents/François_Mitterrand.avif',
                'actif' => false,
            ],
            [
                'nom' => 'Valéry Giscard d\'Estaing',
                'slug' => 'valery-giscard-destaing',
                'mandat' => '1974 - 1981',
                'photo' => '/images/portraits_presidents/Valéry_Giscard_d\'Estaing.avif',
                'actif' => false,
            ],
            [
                'nom' => 'Georges Pompidou',
                'slug' => 'georges-pompidou',
                'mandat' => '1969 - 1974',
                'photo' => '/images/portraits_presidents/Georges_Pompidou.avif',
                'actif' => false,
            ],
            [
                'nom' => 'Charles de Gaulle',
                'slug' => 'charles-de-gaulle',
                'mandat' => '1959 - 1969',
                'photo' => '/images/portraits_presidents/Charles_de_Gaulle.avif',
                'actif' => false,
            ],
        ];

        $results = [];
        $queryLower = strtolower($query);

        foreach ($presidents as $president) {
            $nomLower = strtolower($president['nom']);

            if (str_contains($nomLower, $queryLower)) {
                $score = $this->calculateScore($president['nom'], $query);
                // Boost pour le président actuel
                if ($president['actif']) {
                    $score += 20;
                }

                $result = [
                    'id' => $president['slug'],
                    'type' => 'president',
                    'category' => 'Président',
                    'icon' => '🇫🇷',
                    'title' => $president['nom'],
                    'subtitle' => $president['actif'] ? 'Président en exercice' : $president['mandat'],
                    'url' => route('gouvernement.president.show', $president['slug']),
                    'photo_url' => $president['photo'],
                    'score' => $score,
                ];

                if ($detailed) {
                    $result['mandat'] = $president['mandat'];
                    $result['actif'] = $president['actif'];
                }

                $results[] = $result;
            }
        }

        // Trier par score et limiter
        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $limit);
    }

    private function searchCommuneArticles(string $query, int $limit, bool $detailed = false): array
    {
        $articles = CommuneArticle::publies()
            ->with('communePage.ville:id,nom,code_insee,slug')
            ->where(function ($q) use ($query) {
                $q->where('titre', 'ilike', "%{$query}%")
                    ->orWhere('extrait', 'ilike', "%{$query}%");
            })
            ->orderByDesc('publie_at')
            ->limit($limit)
            ->get();

        return $articles->map(function ($a) use ($query, $detailed) {
            $score = $this->calculateScore($a->titre, $query);
            $communeNom = $a->communePage?->ville?->nom ?? '';
            $codeInsee = $a->communePage?->code_insee ?? '';

            $result = [
                'id' => $a->id,
                'type' => 'commune_article',
                'category' => 'Actualite commune',
                'icon' => '📰',
                'title' => Str::limit($a->titre, 60),
                'subtitle' => $communeNom,
                'url' => url("/commune-hub/{$codeInsee}/actualites/{$a->slug}"),
                'photo_url' => $a->image_url,
                'score' => $score,
            ];

            if ($detailed) {
                $result['extrait'] = $a->extrait_auto;
                $result['categorie'] = $a->categorie_labell;
                $result['commune'] = $communeNom;
                $result['date'] = $a->publie_at?->format('d/m/Y');
            }

            return $result;
        })->toArray();
    }

    private function searchCommuneEvenements(string $query, int $limit, bool $detailed = false): array
    {
        $evenements = CommuneEvenement::publies()
            ->with('communePage.ville:id,nom,code_insee,slug')
            ->where(function ($q) use ($query) {
                $q->where('titre', 'ilike', "%{$query}%")
                    ->orWhere('lieu_nom', 'ilike', "%{$query}%");
            })
            ->orderBy('date_debut')
            ->limit($limit)
            ->get();

        return $evenements->map(function ($e) use ($query, $detailed) {
            $score = $this->calculateScore($e->titre, $query);
            $communeNom = $e->communePage?->ville?->nom ?? '';
            $codeInsee = $e->communePage?->code_insee ?? '';

            $result = [
                'id' => $e->id,
                'type' => 'commune_evenement',
                'category' => 'Evenement commune',
                'icon' => '📅',
                'title' => Str::limit($e->titre, 60),
                'subtitle' => "{$communeNom} - {$e->date_debut->format('d/m/Y')}",
                'url' => url("/commune-hub/{$codeInsee}/evenements/{$e->slug}"),
                'photo_url' => $e->image_url,
                'score' => $score,
            ];

            if ($detailed) {
                $result['lieu'] = $e->lieu_nom;
                $result['commune'] = $communeNom;
                $result['date'] = $e->date_debut->format('d/m/Y H:i');
                $result['categorie'] = $e->categorie_label;
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
            ['key' => 'ministres', 'label' => 'Ministres', 'icon' => '👔'],
            ['key' => 'presidents', 'label' => 'Présidents', 'icon' => '🇫🇷'],
            ['key' => 'lois', 'label' => 'Lois', 'icon' => '📜'],
            ['key' => 'idees', 'label' => 'Idées', 'icon' => '💡'],
            ['key' => 'maires', 'label' => 'Maires', 'icon' => '👔'],
            ['key' => 'villes', 'label' => 'Villes', 'icon' => '🏘️'],
            ['key' => 'commune_articles', 'label' => 'Actus communes', 'icon' => '📰'],
            ['key' => 'commune_evenements', 'label' => 'Evenements communes', 'icon' => '📅'],
        ];
    }
}
