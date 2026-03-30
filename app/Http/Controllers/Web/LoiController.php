<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AmendementAN;
use App\Models\AmendementSenat;
use App\Models\CitizenLawStats;
use App\Models\CitizenLawVote;
use App\Models\EtatLoi;
use App\Models\Loi;
use App\Models\LoiStats;
use App\Models\ScrutinAN;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\TypeLoi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LoiController extends Controller
{
    /**
     * Liste des lois avec filtres et pagination
     */
    public function index(Request $request): Response
    {
        $query = Loi::query()
            ->with(['etat', 'typeLoi']);

        // Filtres
        if ($request->filled('etat')) {
            $query->where('etaloicod', $request->etat);
        }

        if ($request->filled('type')) {
            $query->where('typloicod', $request->type);
        }

        $annee = $request->input('annee', 'all');
        if ($annee && $annee !== 'all') {
            $query->whereRaw('EXTRACT(YEAR FROM COALESCE(loidatjo, date_loi)) = ?', [$annee]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('loitit', 'ILIKE', "%{$search}%")
                    ->orWhere('loiint', 'ILIKE', "%{$search}%")
                    ->orWhere('numero', 'ILIKE', "%{$search}%");
            });
        }

        // Filtre par thématique (tag)
        if ($request->filled('thematique')) {
            $tagSlug = $request->thematique;
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        $sort = $request->input('sort', 'recent');
        switch ($sort) {
            case 'recent':
                $query->orderByRaw('COALESCE(loidatjo, date_loi) DESC NULLS LAST');
                break;
            case 'ancien':
                $query->orderByRaw('COALESCE(loidatjo, date_loi) ASC NULLS LAST');
                break;
            case 'titre':
                $query->orderBy('loitit');
                break;
            default:
                $query->orderByRaw('COALESCE(loidatjo, date_loi) DESC NULLS LAST');
        }

        $lois = $query->paginate(20)->withQueryString();

        $fiveYearsAgo = now()->subYears(5);
        $lois->through(function ($loi) use ($fiveYearsAgo) {
            if (trim($loi->etaloicod ?? '') === '01') {
                $date = $loi->loidatjo ?? $loi->date_loi;
                if ($date && \Carbon\Carbon::parse($date)->lt($fiveYearsAgo)) {
                    $loi->setAttribute('est_caduc', true);
                }
            }

            return $loi;
        });

        // Statistiques
        $stats = Cache::remember('lois_stats', 3600, function () {
            return [
                'total' => Loi::count(),
                'promulguees' => Loi::promulguees()->count(),
                'en_cours' => Loi::enCours()->count(),
                'rejetees' => Loi::rejetees()->count(),
                'cette_annee' => Loi::promulguees()
                    ->whereYear('loidatjo', now()->year)
                    ->count(),
            ];
        });

        // Options de filtres
        $etats = EtatLoi::orderBy('etaloicod')->get()->map(fn ($e) => [
            'code' => trim($e->etaloicod),
            'libelle' => trim($e->etaloilib),
        ])->unique('code')->values();

        $types = TypeLoi::orderBy('typloilib')->get()->map(fn ($t) => [
            'code' => trim($t->typloicod),
            'libelle' => trim($t->typloilib ?? ''),
        ])->filter(fn ($t) => ! empty($t['libelle']));

        $annees = DB::table('senat_dosleg_loi')
            ->selectRaw('EXTRACT(YEAR FROM COALESCE(loidatjo, date_loi)) as annee')
            ->whereRaw('COALESCE(loidatjo, date_loi) IS NOT NULL')
            ->groupBy(DB::raw('EXTRACT(YEAR FROM COALESCE(loidatjo, date_loi))'))
            ->orderByDesc('annee')
            ->pluck('annee')
            ->filter()
            ->values();

        // Thématiques (tags officiels)
        $thematiques = Tag::official()
            ->validated()
            ->where('usage_count', '>', 0)
            ->orderByDesc('usage_count')
            ->get()
            ->map(fn ($t) => [
                'slug' => $t->slug,
                'nom' => $t->nom,
                'icone' => $t->icone,
                'couleur' => $t->couleur,
                'count' => $t->usage_count,
            ]);

        return Inertia::render('Legislation/Lois/Index', [
            'lois' => $lois,
            'stats' => $stats,
            'etats' => $etats,
            'types' => $types,
            'annees' => $annees,
            'thematiques' => $thematiques,
            'filters' => $request->only(['etat', 'type', 'annee', 'search', 'sort', 'thematique']),
        ]);
    }

    /**
     * Détail d'une loi avec son parcours législatif
     *
     * Optimisations appliquées:
     * - Cache 1h sur données lourdes (dossier AN, amendements, parlementaires)
     * - Lazy-loading des amendements via API séparée
     * - Limite des requêtes ILIKE coûteuses
     */
    public function show(string $loicod): Response
    {
        $loicodTrim = trim($loicod);
        $cacheKey = "loi_show_{$loicodTrim}";

        // Cache la loi avec ses relations (2 min)
        $loi = Cache::remember("{$cacheKey}_base", 120, function () use ($loicodTrim) {
            return Loi::with([
                'etat',
                'typeLoi',
                'thematiques',
                'lectures.typeLecture',
                'lectures.passages',
            ])->whereRaw('TRIM(loicod) = ?', [$loicodTrim])->firstOrFail();
        });

        // Construire le parcours législatif (cache 5 min)
        $parcours = Cache::remember("{$cacheKey}_parcours", 300, function () use ($loi) {
            return $loi->getParcours();
        });

        // Calculer les stats d'amendements depuis le parcours
        $totalAmendements = collect($parcours)->sum('nb_amendements');
        $amendementsAdoptes = collect($parcours)->sum('amendements_adoptes');

        // Statistiques pré-calculées (ou fallback)
        $loiStats = LoiStats::forLoi($loicodTrim);
        $stats = $loiStats ? $loiStats->toViewArray() : [
            'etapes_total' => count($parcours),
            'etapes_an' => collect($parcours)->where('chambre', 'A')->count(),
            'etapes_senat' => collect($parcours)->where('chambre', 'S')->count(),
            'amendements_total' => $totalAmendements,
            'amendements_adoptes' => $amendementsAdoptes,
            'taux_adoption_amendements' => $totalAmendements > 0 ? round(($amendementsAdoptes / $totalAmendements) * 100, 1) : 0,
            'scrutins_total' => 0,
            'duree_jours' => null,
            'score_engagement' => 0,
            'calculated_at' => null,
        ];

        // Calcul durée si on a des dates
        if (! $stats['duree_jours'] && count($parcours) > 0) {
            // Chercher la première date réelle dans le parcours
            $premierPassage = collect($parcours)->first(fn ($p) => ! empty($p['date']));

            if ($premierPassage && ! empty($premierPassage['date'])) {
                try {
                    $dateDebut = \Carbon\Carbon::parse($premierPassage['date']);
                    $dateFin = $loi->loidatjo ?? now();
                    $duree = $dateDebut->diffInDays($dateFin, false);
                    // S'assurer que la durée est positive
                    $stats['duree_jours'] = max(0, abs($duree));
                } catch (\Exception $e) {
                    // Fallback: utiliser l'année de session
                    if (! empty($premierPassage['session']) && $loi->loidatjo) {
                        $anneeDebut = (int) $premierPassage['session'];
                        $dateDebut = \Carbon\Carbon::create($anneeDebut, 1, 1);
                        $stats['duree_jours'] = max(0, abs($dateDebut->diffInDays($loi->loidatjo)));
                    }
                }
            }
        }

        // Lois similaires (même thématique ou type)
        $loisSimilaires = collect();
        if ($loi->thematiques->isNotEmpty()) {
            $themeCodes = $loi->thematiques->pluck('thecle');
            $loisSimilaires = Loi::with('etat')
                ->whereHas('thematiques', function ($q) use ($themeCodes) {
                    $q->whereIn('senat_dosleg_the.thecle', $themeCodes);
                })
                ->where('loicod', '!=', $loicod)
                ->promulguees()
                ->orderByDesc('loidatjo')
                ->take(5)
                ->get();
        }

        // Scrutins AN liés (recherche par numéro ou titre)
        $scrutinsLies = collect();
        $searchTerms = [];

        // Construire les termes de recherche
        if (! empty($loi->numero)) {
            $searchTerms[] = $loi->numero;
        }

        // Extraire mots clés du titre (> 5 caractères)
        $titreMots = preg_split('/\s+/', $loi->loitit ?? '');
        $motsSignificatifs = array_filter($titreMots, fn ($m) => strlen($m) > 8);
        $motsSignificatifs = array_slice($motsSignificatifs, 0, 3);

        if (! empty($searchTerms) || ! empty($motsSignificatifs)) {
            $scrutinsQuery = ScrutinAN::query()
                ->where(function ($q) use ($searchTerms, $motsSignificatifs) {
                    foreach ($searchTerms as $term) {
                        $q->orWhere('titre', 'ILIKE', "%{$term}%");
                    }
                    foreach ($motsSignificatifs as $mot) {
                        $q->orWhere('titre', 'ILIKE', "%{$mot}%");
                    }
                })
                // Priorité: scrutins solennels d'abord, puis par date
                ->orderByRaw("CASE WHEN type_vote_code = 'SPS' THEN 0 ELSE 1 END")
                ->orderByDesc('date_scrutin')
                ->limit(30);

            $scrutinsLies = $scrutinsQuery->get();
        }

        // Catégoriser les scrutins
        $scrutinsSolennels = $scrutinsLies->filter(fn ($s) => $s->type_vote_code === 'SPS');
        $scrutinsAmendements = $scrutinsLies->filter(fn ($s) => $s->type_vote_code !== 'SPS' &&
            (str_contains(strtolower($s->titre), 'amendement') || str_contains(strtolower($s->titre), 'sous-amendement'))
        );
        $scrutinsAutres = $scrutinsLies->filter(fn ($s) => $s->type_vote_code !== 'SPS' &&
            ! str_contains(strtolower($s->titre), 'amendement') &&
            ! str_contains(strtolower($s->titre), 'sous-amendement')
        );

        // Extraire les positions des groupes politiques depuis le scrutin solennel
        $groupesPositions = $this->extractGroupesPositions($scrutinsSolennels->first());

        // Dossier AN (cache 1h - données rarement mises à jour)
        $dossierAN = Cache::remember("{$cacheKey}_dossier", 3600, function () use ($loi) {
            return $this->findDossierAN($loi);
        });

        // Les amendements sont chargés via API séparée pour lazy-loading
        // On ne charge que les stats légères ici
        $amendementsLies = Cache::remember("{$cacheKey}_amendements_summary", 1800, function () use ($loi, $dossierAN) {
            return $this->findAmendementsLiesLight($loi, $dossierAN);
        });

        // Parlementaires (cache 1h)
        $parlementairesAssocies = Cache::remember("{$cacheKey}_parlementaires", 3600, function () use ($loi, $dossierAN) {
            return $this->findParlementairesAssocies($loi, $dossierAN);
        });

        // Débats citoyens liés à cette loi
        $debatsLies = Topic::forLoi(trim($loi->loicod))
            ->with(['author', 'category'])
            ->withCount('posts')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status,
                'author' => $t->author?->name,
                'posts_count' => $t->posts_count,
                'created_at' => $t->created_at->format('d/m/Y'),
            ]);

        // Statistiques de vote citoyen
        $citizenVoteStats = CitizenLawStats::getForLoi(trim($loi->loicod));
        $userVote = null;
        if (auth()->check()) {
            $userVote = CitizenLawVote::getUserVote(auth()->id(), trim($loi->loicod));
        }

        return Inertia::render('Legislation/Lois/Show', [
            'loi' => [
                'loicod' => trim($loi->loicod),
                'numero' => trim($loi->numero ?? ''),
                'titre' => trim($loi->loitit ?? ''),
                'titre_court' => $loi->titre_court,
                'intitule' => trim($loi->loiint ?? ''),
                'etat' => [
                    'code' => trim($loi->etaloicod),
                    'libelle' => $loi->etat_libelle,
                    'couleur' => $loi->etat_couleur,
                ],
                'type' => $loi->typeLoi ? [
                    'code' => trim($loi->typeLoi->typloicod),
                    'libelle' => trim($loi->typeLoi->typloilib ?? ''),
                ] : null,
                'thematiques' => $loi->thematiques->map(fn ($t) => [
                    'code' => trim($t->thecle),
                    'libelle' => $t->libelle,
                    'categorie' => $t->categorie,
                    'couleur' => $t->couleur,
                ]),
                'urgence' => $loi->urgence === 'O',
                'date_jo' => $loi->loidatjo?->format('d/m/Y'),
                'date_loi' => $loi->date_loi?->format('d/m/Y'),
                'url_jo' => $loi->url_jo,
                'url_an' => $loi->url_an,
                'chambre_origine' => $loi->chambre_origine,
                'progression' => $loi->progression,
                'est_promulguee' => $loi->est_promulguee,
            ],
            'stats' => $stats,
            'parcours' => $parcours,
            'loisSimilaires' => $loisSimilaires->map(fn ($l) => [
                'loicod' => trim($l->loicod),
                'titre' => $l->titre_court,
                'numero' => trim($l->numero ?? ''),
                'date_jo' => $l->loidatjo?->format('d/m/Y'),
                'etat_couleur' => $l->etat_couleur,
            ]),
            // Scrutins catégorisés
            'scrutinsSolennels' => $scrutinsSolennels->map(fn ($s) => [
                'uid' => $s->uid,
                'numero' => $s->numero,
                'titre' => \Str::limit($s->titre, 200),
                'date' => $s->date_scrutin?->format('d/m/Y'),
                'pour' => $s->pour_calcule,
                'contre' => $s->contre_calcule,
                'abstentions' => $s->abstentions_calcule,
                'resultat' => $s->resultat_format,
                'type' => 'solennel',
            ])->values(),
            'scrutinsAmendements' => $scrutinsAmendements->map(fn ($s) => [
                'uid' => $s->uid,
                'numero' => $s->numero,
                'titre' => \Str::limit($s->titre, 200),
                'date' => $s->date_scrutin?->format('d/m/Y'),
                'pour' => $s->pour_calcule,
                'contre' => $s->contre_calcule,
                'abstentions' => $s->abstentions_calcule,
                'resultat' => $s->resultat_format,
                'type' => 'amendement',
            ])->values(),
            'scrutinsAutres' => $scrutinsAutres->map(fn ($s) => [
                'uid' => $s->uid,
                'numero' => $s->numero,
                'titre' => \Str::limit($s->titre, 200),
                'date' => $s->date_scrutin?->format('d/m/Y'),
                'pour' => $s->pour_calcule,
                'contre' => $s->contre_calcule,
                'abstentions' => $s->abstentions_calcule,
                'resultat' => $s->resultat_format,
                'type' => 'article',
            ])->values(),
            // Conserver l'ancien format pour compatibilité
            'scrutins' => $scrutinsLies->take(10)->map(fn ($s) => [
                'uid' => $s->uid,
                'numero' => $s->numero,
                'titre' => \Str::limit($s->titre, 150),
                'date' => $s->date_scrutin?->format('d/m/Y'),
                'pour' => $s->pour_calcule,
                'contre' => $s->contre_calcule,
                'abstentions' => $s->abstentions_calcule,
                'resultat' => $s->resultat_format,
            ])->values(),
            'citizenVoteStats' => [
                'stats' => $citizenVoteStats,
                'user_vote' => $userVote,
            ],
            'groupesPositions' => $groupesPositions,
            'amendementsLies' => $amendementsLies,
            'dossierAN' => $dossierAN,
            'parlementairesAssocies' => $parlementairesAssocies,
            'debatsLies' => $debatsLies,
        ]);
    }

    /**
     * Trouver les parlementaires clés associés à une loi
     */
    private function findParlementairesAssocies(Loi $loi, ?array $dossierAN): array
    {
        $rapporteurs = [];
        $auteurs = [];

        // Si on a un dossier AN avec textes, chercher les auteurs d'amendements
        if ($dossierAN && ! empty($dossierAN['textes'])) {
            $numerosTextes = collect($dossierAN['textes'])
                ->pluck('numero')
                ->filter()
                ->map(fn ($n) => str_pad($n, 4, '0', STR_PAD_LEFT))
                ->toArray();

            if (! empty($numerosTextes)) {
                // Auteurs d'amendements avec leur fréquence
                $auteursQuery = AmendementAN::query()
                    ->where('legislature', 17)
                    ->whereNotNull('auteur_acteur_ref')
                    ->where(function ($q) use ($numerosTextes) {
                        foreach ($numerosTextes as $num) {
                            $q->orWhere('uid', 'LIKE', "%B{$num}%");
                        }
                    })
                    ->selectRaw('auteur_acteur_ref, auteur_libelle, COUNT(*) as nb_amendements')
                    ->groupBy('auteur_acteur_ref', 'auteur_libelle')
                    ->orderByDesc('nb_amendements')
                    ->limit(10)
                    ->get();

                foreach ($auteursQuery as $a) {
                    $libelle = html_entity_decode($a->auteur_libelle ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    // Détecter si c'est un rapporteur
                    if (stripos($libelle, 'rapporteur') !== false) {
                        // Extraire le nom du rapporteur
                        $rapporteurs[] = [
                            'uid' => $a->auteur_acteur_ref,
                            'libelle' => $libelle,
                            'nb_amendements' => $a->nb_amendements,
                        ];
                    } else {
                        $auteurs[] = [
                            'uid' => $a->auteur_acteur_ref,
                            'libelle' => $libelle,
                            'nb_amendements' => $a->nb_amendements,
                        ];
                    }
                }
            }
        }

        // Enrichir avec les infos des acteurs
        $rapporteursEnrichis = [];
        foreach (array_slice($rapporteurs, 0, 2) as $r) {
            $acteur = \App\Models\ActeurAN::find($r['uid']);
            if ($acteur) {
                $rapporteursEnrichis[] = [
                    'uid' => $r['uid'],
                    'nom' => $acteur->nom_complet,
                    'photo' => $acteur->photo_wikipedia_url,
                    'groupe' => $acteur->groupe_politique_actuel?->libelle_abrege,
                    'role' => 'Rapporteur',
                    'nb_amendements' => $r['nb_amendements'],
                ];
            }
        }

        $auteursEnrichis = [];
        foreach (array_slice($auteurs, 0, 6) as $a) {
            $acteur = \App\Models\ActeurAN::find($a['uid']);
            if ($acteur) {
                $auteursEnrichis[] = [
                    'uid' => $a['uid'],
                    'nom' => $acteur->nom_complet,
                    'photo' => $acteur->photo_wikipedia_url,
                    'groupe' => $acteur->groupe_politique_actuel?->libelle_abrege,
                    'nb_amendements' => $a['nb_amendements'],
                ];
            }
        }

        return [
            'rapporteurs' => $rapporteursEnrichis,
            'auteurs_principaux' => $auteursEnrichis,
            'total_auteurs' => count($rapporteurs) + count($auteurs),
        ];
    }

    /**
     * Trouver le dossier AN correspondant à une loi Sénat
     */
    private function findDossierAN(Loi $loi): ?array
    {
        // Extraire l'ID du dossier depuis l'URL AN
        $urlAN = $loi->url_an;
        if (! $urlAN) {
            return null;
        }

        // Pattern: DLR5L17N50724 ou similaire
        if (! preg_match('/(DLR\d+L\d+N\d+)/', $urlAN, $matches)) {
            return null;
        }

        $dossierRef = $matches[1];

        // Chercher le dossier
        $dossier = DB::table('dossiers_legislatifs_an')
            ->where('uid', $dossierRef)
            ->first();

        if (! $dossier) {
            return ['ref' => $dossierRef, 'existe' => false, 'textes' => []];
        }

        // Chercher les textes liés
        $textes = DB::table('textes_legislatifs_an')
            ->where('dossier_ref', $dossierRef)
            ->get()
            ->map(fn ($t) => [
                'uid' => $t->uid,
                'type' => $t->type_texte,
                'numero' => $t->numero,
                'titre' => $t->titre,
                'date_depot' => $t->date_depot,
            ])
            ->toArray();

        return [
            'ref' => $dossierRef,
            'existe' => true,
            'titre' => $dossier->titre,
            'legislature' => $dossier->legislature,
            'textes' => $textes,
        ];
    }

    /**
     * Version légère : ne charge que les stats d'amendements (pas le détail)
     * Pour le chargement initial de la page
     */
    private function findAmendementsLiesLight(Loi $loi, ?array $dossierAN): array
    {
        $totalAN = 0;
        $totalSenat = 0;
        $liaisonDirecte = false;
        $numerosTextes = [];

        // Compter les amendements AN via liaison directe
        if ($dossierAN && ! empty($dossierAN['textes'])) {
            $numerosTextes = collect($dossierAN['textes'])
                ->pluck('numero')
                ->filter()
                ->map(fn ($n) => str_pad($n, 4, '0', STR_PAD_LEFT))
                ->toArray();

            if (! empty($numerosTextes)) {
                $totalAN = AmendementAN::query()
                    ->where('legislature', 17)
                    ->where(function ($q) use ($numerosTextes) {
                        foreach ($numerosTextes as $num) {
                            $q->orWhere('uid', 'LIKE', "%B{$num}%");
                        }
                    })
                    ->count();

                $liaisonDirecte = $totalAN > 0;
            }
        }

        // Stats par sort (limité à 1000 pour perf)
        $parSort = [];
        if ($liaisonDirecte && ! empty($numerosTextes)) {
            $parSort = AmendementAN::query()
                ->where('legislature', 17)
                ->where(function ($q) use ($numerosTextes) {
                    foreach ($numerosTextes as $num) {
                        $q->orWhere('uid', 'LIKE', "%B{$num}%");
                    }
                })
                ->selectRaw('sort_libelle, COUNT(*) as count')
                ->groupBy('sort_libelle')
                ->limit(10)
                ->pluck('count', 'sort_libelle')
                ->toArray();
        }

        return [
            'amendements' => [], // Chargés via API séparée
            'total' => $totalAN + $totalSenat,
            'total_an' => $totalAN,
            'total_senat' => $totalSenat,
            'par_sort' => $parSort,
            'mots_cles' => [],
            'liaison_directe' => $liaisonDirecte,
            'numeros_textes' => $numerosTextes,
        ];
    }

    /**
     * API: Charger les amendements liés (lazy-loading paginé)
     */
    public function amendementsApi(Request $request, string $loicod)
    {
        $loicodTrim = trim($loicod);
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);

        $loi = Loi::with('thematiques')
            ->whereRaw('TRIM(loicod) = ?', [$loicodTrim])
            ->firstOrFail();

        $dossierAN = Cache::remember("loi_show_{$loicodTrim}_dossier", 3600, function () use ($loi) {
            return $this->findDossierAN($loi);
        });

        $amendements = $this->findAmendementsLiesPaginated($loi, $dossierAN, $page, $perPage);

        return response()->json($amendements);
    }

    /**
     * Version paginée des amendements pour API
     */
    private function findAmendementsLiesPaginated(Loi $loi, ?array $dossierAN, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $amendementsAN = collect();
        $liaisonDirecte = false;

        // MÉTHODE 1: Liaison directe via les numéros de textes du dossier AN
        if ($dossierAN && ! empty($dossierAN['textes'])) {
            $numerosTextes = collect($dossierAN['textes'])
                ->pluck('numero')
                ->filter()
                ->map(fn ($n) => str_pad($n, 4, '0', STR_PAD_LEFT))
                ->toArray();

            if (! empty($numerosTextes)) {
                $query = AmendementAN::query()
                    ->where('legislature', 17)
                    ->where(function ($q) use ($numerosTextes) {
                        foreach ($numerosTextes as $num) {
                            $q->orWhere('uid', 'LIKE', "%B{$num}%");
                        }
                    })
                    ->orderByDesc('date_depot');

                $total = $query->count();

                $amendementsAN = $query
                    ->offset($offset)
                    ->limit($perPage)
                    ->get()
                    ->map(function ($a) {
                        $texteRef = null;
                        if ($a->uid && preg_match('/B(\d+)P/', $a->uid, $matches)) {
                            $texteRef = $matches[1];
                        }

                        return [
                            'uid' => $a->uid,
                            'numero' => $a->numero_long,
                            'texte_ref' => $texteRef,
                            'article' => $a->article_designation_courte ?? $a->division_titre,
                            'auteur' => html_entity_decode($a->auteur_libelle ?? 'Inconnu'),
                            'sort' => $a->sort_libelle,
                            'sort_code' => $a->sort_code,
                            'date_depot' => $a->date_depot?->format('d/m/Y'),
                            'expose' => \Str::limit(strip_tags($a->expose ?? ''), 150),
                            'chambre' => 'AN',
                            'url' => $texteRef && $a->numero_long ? "https://www.assemblee-nationale.fr/dyn/17/amendements/{$texteRef}/{$a->numero_long}" : null,
                        ];
                    });

                $liaisonDirecte = true;

                return [
                    'amendements' => $amendementsAN->toArray(),
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'last_page' => ceil($total / $perPage),
                    'liaison_directe' => $liaisonDirecte,
                ];
            }
        }

        return [
            'amendements' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => 1,
            'liaison_directe' => false,
        ];
    }

    /**
     * Rechercher les amendements liés à une loi (version complète, gardée pour référence)
     */
    private function findAmendementsLies(Loi $loi, ?array $dossierAN): array
    {
        $amendementsAN = collect();
        $liaisonDirecte = false;

        // MÉTHODE 1: Liaison directe via les numéros de textes du dossier AN
        if ($dossierAN && ! empty($dossierAN['textes'])) {
            $numerosTextes = collect($dossierAN['textes'])
                ->pluck('numero')
                ->filter()
                ->map(fn ($n) => str_pad($n, 4, '0', STR_PAD_LEFT))
                ->toArray();

            if (! empty($numerosTextes)) {
                // Les UIDs d'amendements contiennent "B" + numero texte
                $amendementsAN = AmendementAN::query()
                    ->where('legislature', 17)
                    ->where(function ($q) use ($numerosTextes) {
                        foreach ($numerosTextes as $num) {
                            $q->orWhere('uid', 'LIKE', "%B{$num}%");
                        }
                    })
                    ->orderByDesc('date_depot')
                    ->limit(100)
                    ->get()
                    ->map(function ($a) {
                        // Extraire le numéro de texte depuis l'UID (format: AMANR5L17PO...B1009P...)
                        $texteRef = null;
                        if ($a->uid && preg_match('/B(\d+)P/', $a->uid, $matches)) {
                            $texteRef = $matches[1];
                        }

                        return [
                            'uid' => $a->uid,
                            'numero' => $a->numero_long,
                            'texte_ref' => $texteRef,
                            'article' => $a->article_designation_courte ?? $a->division_titre,
                            'auteur' => html_entity_decode($a->auteur_libelle ?? 'Inconnu'),
                            'sort' => $a->sort_libelle,
                            'sort_code' => $a->sort_code,
                            'date_depot' => $a->date_depot?->format('d/m/Y'),
                            'expose' => \Str::limit(strip_tags($a->expose ?? ''), 150),
                            'chambre' => 'AN',
                            'url' => $texteRef && $a->numero_long ? "https://www.assemblee-nationale.fr/dyn/17/amendements/{$texteRef}/{$a->numero_long}" : null,
                        ];
                    });

                $liaisonDirecte = $amendementsAN->count() > 0;
            }
        }

        // MÉTHODE 2: Fallback par mots-clés si pas de liaison directe ou peu de résultats
        if (! $liaisonDirecte || $amendementsAN->count() < 5) {
            $motsExclus = [
                'pour', 'dans', 'avec', 'cette', 'projet', 'proposition', 'relative',
                'relatif', 'portant', 'visant', 'autorisant', 'ratification', 'convention',
                'article', 'articles', 'loi', 'lois', 'code', 'texte', 'textes',
                'transposition', 'accords', 'nationaux', 'interprofessionnels',
                'modifiant', 'modification', 'dispositions', 'diverses',
            ];

            $titre = strtolower($loi->loitit ?? '');
            $titreMots = preg_split('/[\s,\-\'\"]+/', $titre);

            $motsSignificatifs = array_filter($titreMots, function ($m) use ($motsExclus) {
                $m = trim($m);

                return strlen($m) > 4 && ! in_array($m, $motsExclus) && ! preg_match('/^\d+$/', $m);
            });

            foreach ($loi->thematiques->pluck('libelle')->toArray() as $theme) {
                $themeMots = preg_split('/[\s,\-]+/', strtolower($theme));
                foreach ($themeMots as $m) {
                    if (strlen($m) > 4 && ! in_array($m, $motsExclus)) {
                        $motsSignificatifs[] = $m;
                    }
                }
            }

            $motsSignificatifs = array_unique(array_values($motsSignificatifs));
            $motsSignificatifs = array_slice($motsSignificatifs, 0, 6);

            if (! empty($motsSignificatifs) && ! $liaisonDirecte) {
                $amendementsAN = AmendementAN::query()
                    ->where('legislature', 17)
                    ->where(function ($q) use ($motsSignificatifs) {
                        foreach ($motsSignificatifs as $mot) {
                            $q->orWhere('dispositif', 'ILIKE', "%{$mot}%")
                                ->orWhere('expose', 'ILIKE', "%{$mot}%")
                                ->orWhere('division_titre', 'ILIKE', "%{$mot}%");
                        }
                    })
                    ->orderByDesc('date_depot')
                    ->limit(50)
                    ->get()
                    ->map(function ($a) {
                        // Extraire le numéro de texte depuis l'UID (format: AMANR5L17PO...B1009P...)
                        $texteRef = null;
                        if ($a->uid && preg_match('/B(\d+)P/', $a->uid, $matches)) {
                            $texteRef = $matches[1];
                        }

                        return [
                            'uid' => $a->uid,
                            'numero' => $a->numero_long,
                            'texte_ref' => $texteRef,
                            'article' => $a->article_designation_courte ?? $a->division_titre,
                            'auteur' => html_entity_decode($a->auteur_libelle ?? 'Inconnu'),
                            'sort' => $a->sort_libelle,
                            'sort_code' => $a->sort_code,
                            'date_depot' => $a->date_depot?->format('d/m/Y'),
                            'expose' => \Str::limit(strip_tags($a->expose ?? ''), 150),
                            'chambre' => 'AN',
                            'url' => $texteRef && $a->numero_long ? "https://www.assemblee-nationale.fr/dyn/17/amendements/{$texteRef}/{$a->numero_long}" : null,
                        ];
                    });
            }
        }

        // Chercher dans les amendements Sénat (par mots-clés uniquement)
        $motsSignificatifs = $motsSignificatifs ?? [];
        $amendementsSenat = collect();

        if (! empty($motsSignificatifs)) {
            $amendementsSenat = AmendementSenat::query()
                ->where('date_depot', '>=', now()->subMonths(12))
                ->where(function ($q) use ($motsSignificatifs) {
                    foreach ($motsSignificatifs as $mot) {
                        $q->orWhere('dispositif', 'ILIKE', "%{$mot}%")
                            ->orWhere('expose', 'ILIKE', "%{$mot}%");
                    }
                })
                ->orderByDesc('date_depot')
                ->limit(50)
                ->get()
                ->map(fn ($a) => [
                    'uid' => 'SEN-'.$a->id,
                    'numero' => $a->numero,
                    'texte_ref' => $a->texte_ref,
                    'session' => $a->session ?? '2024-2025',
                    'article' => $a->subdiv_titre ?? $a->type_amendement,
                    'auteur' => trim(($a->auteur_prenom ?? '').' '.($a->auteur_nom ?? 'Inconnu')),
                    'sort' => $a->sort_libelle_formate,
                    'sort_code' => $a->sort_code,
                    'date_depot' => $a->date_depot?->format('d/m/Y'),
                    'expose' => \Str::limit(strip_tags($a->expose ?? ''), 150),
                    'chambre' => 'Sénat',
                    'url' => $a->url ?? ($a->texte_ref ? 'https://www.senat.fr/amendements/'.($a->session ?? '2024-2025')."/{$a->texte_ref}/{$a->numero}.html" : null),
                ]);
        }

        // Fusionner et trier par date
        $amendements = $amendementsAN->concat($amendementsSenat)
            ->sortByDesc('date_depot')
            ->values();

        // Grouper par sort
        $parSort = $amendements->groupBy('sort')->map->count();

        return [
            'amendements' => $amendements->take(30)->toArray(),
            'total' => $amendements->count(),
            'total_an' => $amendementsAN->count(),
            'total_senat' => $amendementsSenat->count(),
            'par_sort' => $parSort->toArray(),
            'mots_cles' => $motsSignificatifs ?? [],
            'liaison_directe' => $liaisonDirecte,
        ];
    }

    /**
     * Extraire les positions des groupes politiques depuis un scrutin
     */
    private function extractGroupesPositions(?ScrutinAN $scrutin): array
    {
        if (! $scrutin || ! $scrutin->ventilation_votes) {
            return ['pour' => [], 'contre' => [], 'abstention' => []];
        }

        $groupes = $scrutin->ventilation_votes['organe']['groupes'] ?? [];
        $pour = [];
        $contre = [];
        $abstention = [];

        foreach ($groupes as $g) {
            $organe = $g['organe'] ?? [];
            $vote = $g['vote']['decompteVoix'] ?? [];

            $groupeInfo = [
                'nom' => $organe['libelle'] ?? 'Groupe inconnu',
                'sigle' => $organe['libelleAbrev'] ?? substr($organe['libelle'] ?? '', 0, 10),
                'pour' => $vote['pour'] ?? 0,
                'contre' => $vote['contre'] ?? 0,
                'abstentions' => $vote['abstentions'] ?? 0,
                'non_votants' => $vote['nonVotants'] ?? 0,
            ];

            // Déterminer la position dominante du groupe
            $totalVotes = $groupeInfo['pour'] + $groupeInfo['contre'] + $groupeInfo['abstentions'];
            if ($totalVotes > 0) {
                if ($groupeInfo['pour'] > $groupeInfo['contre'] && $groupeInfo['pour'] > $groupeInfo['abstentions']) {
                    $pour[] = $groupeInfo;
                } elseif ($groupeInfo['contre'] > $groupeInfo['pour'] && $groupeInfo['contre'] > $groupeInfo['abstentions']) {
                    $contre[] = $groupeInfo;
                } else {
                    $abstention[] = $groupeInfo;
                }
            }
        }

        // Trier par nombre de votes
        usort($pour, fn ($a, $b) => $b['pour'] <=> $a['pour']);
        usort($contre, fn ($a, $b) => $b['contre'] <=> $a['contre']);
        usort($abstention, fn ($a, $b) => $b['abstentions'] <=> $a['abstentions']);

        return [
            'pour' => $pour,
            'contre' => $contre,
            'abstention' => $abstention,
        ];
    }

    /**
     * Timeline JSON pour affichage dynamique
     */
    public function timeline(string $loicod)
    {
        $loi = Loi::with([
            'lectures.typeLecture',
            'lectures.passages',
        ])->whereRaw('TRIM(loicod) = ?', [trim($loicod)])->firstOrFail();

        return response()->json([
            'loicod' => $loicod,
            'titre' => $loi->titre_court,
            'etat' => $loi->etat_libelle,
            'progression' => $loi->progression,
            'parcours' => $loi->getParcours(),
        ]);
    }

    /**
     * Statistiques globales sur les lois
     */
    public function statistiques(): Response
    {
        $stats = Cache::remember('lois_statistiques_detaillees', 3600, function () {
            // Par état
            $parEtat = DB::table('senat_dosleg_loi as l')
                ->join('senat_dosleg_etaloi as e', 'l.etaloicod', '=', 'e.etaloicod')
                ->select('e.etaloilib', DB::raw('count(*) as total'))
                ->groupBy('e.etaloilib')
                ->orderByDesc('total')
                ->get();

            // Par année (promulguées)
            $parAnnee = DB::table('senat_dosleg_loi')
                ->selectRaw('EXTRACT(YEAR FROM loidatjo) as annee, count(*) as total')
                ->where('etaloicod', '04')
                ->whereNotNull('loidatjo')
                ->groupBy(DB::raw('EXTRACT(YEAR FROM loidatjo)'))
                ->orderBy('annee')
                ->get();

            // Moyenne de lectures
            $moyenneLectures = DB::table('senat_dosleg_lecture')
                ->selectRaw('AVG(nb)::numeric(10,2) as moyenne')
                ->fromSub(
                    DB::table('senat_dosleg_lecture')
                        ->select('loicod', DB::raw('count(*) as nb'))
                        ->groupBy('loicod'),
                    'sub'
                )
                ->first();

            // Répartition AN vs Sénat (première lecture)
            $chambreOrigine = DB::table('senat_dosleg_lecass as la')
                ->join('senat_dosleg_lecture as l', 'la.lecidt', '=', 'l.lecidt')
                ->where('l.typleccod', '1')
                ->where('la.ordreass', 1)
                ->select('la.codass', DB::raw('count(DISTINCT l.loicod) as total'))
                ->groupBy('la.codass')
                ->get();

            return [
                'par_etat' => $parEtat,
                'par_annee' => $parAnnee,
                'moyenne_lectures' => $moyenneLectures->moyenne ?? 0,
                'chambre_origine' => $chambreOrigine,
            ];
        });

        return Inertia::render('Legislation/Lois/Statistiques', [
            'stats' => $stats,
        ]);
    }

    /**
     * Recherche de lois
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $lois = Loi::with('etat')
            ->where(function ($q) use ($query) {
                $q->where('loitit', 'ILIKE', "%{$query}%")
                    ->orWhere('loiint', 'ILIKE', "%{$query}%")
                    ->orWhere('numero', 'ILIKE', "%{$query}%");
            })
            ->orderByDesc('loidatjo')
            ->take(10)
            ->get()
            ->map(fn ($loi) => [
                'loicod' => trim($loi->loicod),
                'titre' => $loi->titre_court,
                'numero' => trim($loi->numero ?? ''),
                'etat' => $loi->etat_libelle,
                'etat_couleur' => $loi->etat_couleur,
                'url' => route('lois.show', ['loicod' => trim($loi->loicod)]),
            ]);

        return response()->json($lois);
    }
}
