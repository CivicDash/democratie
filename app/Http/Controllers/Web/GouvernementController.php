<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\PersonnePolitique;
use App\Models\PosteMinisteriel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GouvernementController extends Controller
{
    /**
     * Page principale du gouvernement avec sélection par présidence
     */
    public function index(Request $request): Response
    {
        // Gouvernements groupés par président
        $tousGouvernements = Gouvernement::orderByDesc('date_debut')
            ->get()
            ->groupBy('president')
            ->map(function ($gouvernements, $president) {
                return [
                    'president' => $president,
                    'periode' => $this->getPeriodePresident($president),
                    'gouvernements' => $gouvernements->map(fn($g) => [
                        'id' => $g->id,
                        'numero' => $g->numero,
                        'nom' => $g->nom,
                        'nom_complet' => $g->nom_complet,
                        'suffixe' => $g->suffixe,
                        'premier_ministre' => $g->premier_ministre,
                        'date_debut' => $g->date_debut?->format('d/m/Y'),
                        'date_fin' => $g->date_fin?->format('d/m/Y'),
                        'duree' => $g->duree,
                        'actif' => $g->actif,
                    ])->values(),
                ];
            })->values();

        // Gouvernement sélectionné (actuel par défaut)
        $gouvernementId = $request->get('gouvernement');
        
        if ($gouvernementId) {
            $gouvernement = Gouvernement::find($gouvernementId);
        } else {
            $gouvernement = Gouvernement::actuel();
        }

        if (!$gouvernement) {
            return Inertia::render('Gouvernement/Index', [
                'gouvernement' => null,
                'postesParType' => [],
                'stats' => null,
                'gouvernementsParPresident' => $tousGouvernements,
            ]);
        }

        // Charger les postes avec les personnes et ministères
        $gouvernement->load(['postes' => function ($q) {
            $q->with(['personne', 'ministere'])
              ->orderBy('ordre')
              ->orderByRaw("CASE type_fonction 
                  WHEN 'premier_ministre' THEN 1 
                  WHEN 'ministre_etat' THEN 2
                  WHEN 'ministre' THEN 3 
                  WHEN 'ministre_delegue' THEN 4 
                  WHEN 'secretaire_etat' THEN 5 
                  ELSE 6 END")
              ->orderBy('date_debut');
        }]);

        // Grouper par type de fonction pour la vue
        $postesParType = [
            'premier_ministre' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'premier_ministre')),
            'ministre_etat' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'ministre_etat')),
            'ministre' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'ministre')),
            'ministre_delegue' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'ministre_delegue')),
            'secretaire_etat' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'secretaire_etat')),
        ];

        // Statistiques
        $stats = [
            'nb_ministres' => $gouvernement->postes->where('type_fonction', 'ministre')->count(),
            'nb_ministres_delegues' => $gouvernement->postes->where('type_fonction', 'ministre_delegue')->count(),
            'nb_secretaires_etat' => $gouvernement->postes->where('type_fonction', 'secretaire_etat')->count(),
            'total' => $gouvernement->postes->count(),
            'duree' => $gouvernement->duree,
            'partis' => $gouvernement->postes
                ->map(fn($p) => $p->personne?->parti_politique)
                ->filter()
                ->countBy()
                ->sortDesc()
                ->take(5)
                ->toArray(),
        ];

        return Inertia::render('Gouvernement/Index', [
            'gouvernement' => [
                'id' => $gouvernement->id,
                'numero' => $gouvernement->numero,
                'nom' => $gouvernement->nom,
                'nom_complet' => $gouvernement->nom_complet,
                'suffixe' => $gouvernement->suffixe,
                'premier_ministre' => $gouvernement->premier_ministre,
                'president' => $gouvernement->president,
                'date_debut' => $gouvernement->date_debut?->format('d/m/Y'),
                'date_debut_iso' => $gouvernement->date_debut?->toISOString(),
                'date_fin' => $gouvernement->date_fin?->format('d/m/Y'),
                'duree' => $gouvernement->duree,
                'actif' => $gouvernement->actif,
                'legislature' => $gouvernement->legislature,
            ],
            'postesParType' => $postesParType,
            'stats' => $stats,
            'gouvernementsParPresident' => $tousGouvernements,
        ]);
    }

    /**
     * Fiche d'une personne politique (ministre)
     */
    public function showPersonne(string $slug): Response
    {
        $personne = PersonnePolitique::where('slug', $slug)
            ->with(['postes' => function ($q) {
                $q->with(['gouvernement', 'ministere'])
                  ->orderByDesc('date_debut');
            }])
            ->firstOrFail();

        // Calculer les statistiques
        $nbPostes = $personne->postes->count();
        $nbGouvernements = $personne->postes->pluck('gouvernement_id')->unique()->count();
        $dureeTotale = $this->calculerDureeTotale($personne->postes);

        // Formatage de l'historique des postes
        $historique = $personne->postes->map(function ($poste) {
            return [
                'id' => $poste->id,
                'fonction' => $poste->fonction,
                'type_fonction' => $poste->type_fonction,
                'type_fonction_libelle' => $poste->type_fonction_libelle,
                'ministere' => $poste->ministere?->nom,
                'ministere_sigle' => $poste->ministere?->sigle,
                'gouvernement' => $poste->gouvernement?->nom_complet,
                'gouvernement_id' => $poste->gouvernement?->id,
                'gouvernement_numero' => $poste->gouvernement?->numero,
                'premier_ministre' => $poste->gouvernement?->premier_ministre,
                'date_debut' => $poste->date_debut?->format('d/m/Y'),
                'date_fin' => $poste->date_fin?->format('d/m/Y'),
                'duree' => $poste->duree_fonction,
                'actif' => $poste->actif,
            ];
        });

        return Inertia::render('Gouvernement/Personne', [
            'personne' => [
                'id' => $personne->id,
                'slug' => $personne->slug,
                'civilite' => $personne->civilite,
                'prenom' => $personne->prenom,
                'nom' => $personne->nom,
                'nom_complet' => $personne->nom_complet,
                'photo' => $personne->photo,
                'age' => $personne->age,
                'date_naissance' => $personne->date_naissance?->format('d/m/Y'),
                'profession' => $personne->profession,
                'parti_politique' => $personne->parti_politique,
                'wikipedia_url' => $personne->wikipedia_url,
                'wikipedia_extract' => $personne->wikipedia_extract,
            ],
            'historique' => $historique,
            'stats' => [
                'nb_postes' => $nbPostes,
                'nb_gouvernements' => $nbGouvernements,
                'duree_totale' => $dureeTotale,
                'est_actif' => $personne->postes->where('actif', true)->isNotEmpty(),
                'poste_actuel' => $personne->postes->where('actif', true)->first()?->fonction,
            ],
        ]);
    }

    /**
     * Page du Président de la République
     */
    public function showPresident(?string $slug = null): Response
    {
        // Liste complète des présidents
        $tousPresidents = $this->getAllPresidents();
        
        // Trouver le président demandé
        if ($slug) {
            $president = collect($tousPresidents)->firstWhere('slug', $slug);
            if (!$president) {
                abort(404, 'Président non trouvé');
            }
        } else {
            // Par défaut : président actuel
            $gouvernementActuel = Gouvernement::actuel();
            $presidentActuel = $gouvernementActuel?->president ?? 'Emmanuel Macron';
            $president = $this->getPresidentData($presidentActuel);
        }

        // Tous les gouvernements de ce président
        $gouvernements = Gouvernement::where('president', $president['nom'])
            ->orderByDesc('date_debut')
            ->withCount('postes')
            ->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'numero' => $g->numero,
                'nom' => $g->nom,
                'nom_complet' => $g->nom_complet,
                'premier_ministre' => $g->premier_ministre,
                'date_debut' => $g->date_debut?->format('d/m/Y'),
                'date_fin' => $g->date_fin?->format('d/m/Y'),
                'duree' => $g->duree,
                'actif' => $g->actif,
                'nb_postes' => $g->postes_count,
            ]);

        // Gouvernement actuel (pour l'affichage)
        $gouvernementActuel = Gouvernement::actuel();

        // Liste des présidents pour la sidebar
        $presidents = collect($tousPresidents)->map(fn($p) => [
            'nom' => $p['nom'],
            'slug' => $p['slug'],
            'mandat' => $p['mandat'] ?? ($p['debut_mandat'] . ' - ' . ($p['fin_mandat'] ?? "aujourd'hui")),
            'photo' => $p['photo'],
            'actuel' => $p['actuel'] ?? false,
        ])->toArray();

        return Inertia::render('Gouvernement/President', [
            'president' => $president,
            'gouvernements' => $gouvernements,
            'gouvernementActuel' => $gouvernementActuel ? [
                'id' => $gouvernementActuel->id,
                'nom' => $gouvernementActuel->nom_complet,
                'premier_ministre' => $gouvernementActuel->premier_ministre,
                'date_debut' => $gouvernementActuel->date_debut?->format('d/m/Y'),
            ] : null,
            'presidents' => $presidents,
        ]);
    }

    /**
     * Liste complète de tous les présidents de la Ve République
     */
    private function getAllPresidents(): array
    {
        return [
            $this->getPresidentData('Emmanuel Macron'),
            $this->getPresidentData('François Hollande'),
            $this->getPresidentData('Nicolas Sarkozy'),
            $this->getPresidentData('Jacques Chirac'),
            $this->getPresidentData('François Mitterrand'),
            $this->getPresidentData('Valéry Giscard d\'Estaing'),
            $this->getPresidentData('Georges Pompidou'),
            $this->getPresidentData('Charles de Gaulle'),
        ];
    }

    /**
     * Données complètes d'un président
     */
    private function getPresidentData(string $nom): array
    {
        $data = [
            'Emmanuel Macron' => [
                'nom' => 'Emmanuel Macron',
                'slug' => 'emmanuel-macron',
                'prenom' => 'Emmanuel',
                'nom_famille' => 'Macron',
                'date_naissance' => '21 décembre 1977',
                'age' => \Carbon\Carbon::parse('1977-12-21')->age,
                'lieu_naissance' => 'Amiens (Somme)',
                'profession' => 'Inspecteur des finances, banquier d\'affaires',
                'parti' => 'Renaissance (ex-LREM)',
                'debut_mandat' => '14 mai 2017',
                'photo' => '/images/portraits_presidents/emmanuel_macron_2017.avif',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/Emmanuel_Macron',
                'mandat_numero' => 25,
                'republique' => 'Ve République',
                'conjoint' => 'Brigitte Macron',
                'residence' => 'Palais de l\'Élysée',
                'actuel' => true,
                'bio' => 'Emmanuel Macron est le 25e président de la République française depuis le 14 mai 2017. Ancien ministre de l\'Économie sous François Hollande, il fonde le mouvement En Marche ! en 2016 et remporte l\'élection présidentielle de 2017 face à Marine Le Pen, puis est réélu en 2022.',
            ],
            'François Hollande' => [
                'nom' => 'François Hollande',
                'slug' => 'francois-hollande',
                'prenom' => 'François',
                'nom_famille' => 'Hollande',
                'date_naissance' => '12 août 1954',
                'age' => \Carbon\Carbon::parse('1954-08-12')->age,
                'lieu_naissance' => 'Rouen (Seine-Maritime)',
                'profession' => 'Magistrat à la Cour des comptes',
                'parti' => 'Parti socialiste',
                'debut_mandat' => '15 mai 2012',
                'fin_mandat' => '14 mai 2017',
                'photo' => '/images/portraits_presidents/françois_hollande_2012.avif',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/François_Hollande',
                'mandat_numero' => 24,
                'republique' => 'Ve République',
                'residence' => 'Palais de l\'Élysée',
                'actuel' => false,
                'bio' => 'François Hollande est le 24e président de la République française du 15 mai 2012 au 14 mai 2017. Premier secrétaire du Parti socialiste de 1997 à 2008, il remporte l\'élection présidentielle de 2012 face à Nicolas Sarkozy. Il renonce à se représenter en 2017.',
            ],
            'Nicolas Sarkozy' => [
                'nom' => 'Nicolas Sarkozy',
                'slug' => 'nicolas-sarkozy',
                'prenom' => 'Nicolas',
                'nom_famille' => 'Sarkozy',
                'date_naissance' => '28 janvier 1955',
                'age' => \Carbon\Carbon::parse('1955-01-28')->age,
                'lieu_naissance' => 'Paris (17e)',
                'profession' => 'Avocat',
                'parti' => 'UMP / Les Républicains',
                'debut_mandat' => '16 mai 2007',
                'fin_mandat' => '15 mai 2012',
                'photo' => '/images/portraits_presidents/nicolas_sarkozy_2007.avif',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/Nicolas_Sarkozy',
                'mandat_numero' => 23,
                'republique' => 'Ve République',
                'conjoint' => 'Carla Bruni-Sarkozy',
                'residence' => 'Palais de l\'Élysée',
                'actuel' => false,
                'bio' => 'Nicolas Sarkozy est le 23e président de la République française du 16 mai 2007 au 15 mai 2012. Ancien ministre de l\'Intérieur et président de l\'UMP, il est battu par François Hollande lors de l\'élection présidentielle de 2012.',
            ],
            'Jacques Chirac' => [
                'nom' => 'Jacques Chirac',
                'slug' => 'jacques-chirac',
                'prenom' => 'Jacques',
                'nom_famille' => 'Chirac',
                'date_naissance' => '29 novembre 1932',
                'date_deces' => '26 septembre 2019',
                'lieu_naissance' => 'Paris (5e)',
                'profession' => 'Haut fonctionnaire',
                'parti' => 'RPR / UMP',
                'debut_mandat' => '17 mai 1995',
                'fin_mandat' => '16 mai 2007',
                'photo' => '/images/portraits_presidents/jacques_chirac_1995.avif',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/Jacques_Chirac',
                'mandat_numero' => 22,
                'republique' => 'Ve République',
                'conjoint' => 'Bernadette Chirac',
                'residence' => 'Palais de l\'Élysée',
                'actuel' => false,
                'bio' => 'Jacques Chirac est le 22e président de la République française du 17 mai 1995 au 16 mai 2007. Deux fois Premier ministre et maire de Paris pendant 18 ans, il effectue deux mandats présidentiels. Il décède le 26 septembre 2019.',
            ],
            'François Mitterrand' => [
                'nom' => 'François Mitterrand',
                'slug' => 'francois-mitterrand',
                'prenom' => 'François',
                'nom_famille' => 'Mitterrand',
                'date_naissance' => '26 octobre 1916',
                'date_deces' => '8 janvier 1996',
                'lieu_naissance' => 'Jarnac (Charente)',
                'profession' => 'Avocat, homme politique',
                'parti' => 'Parti socialiste',
                'debut_mandat' => '21 mai 1981',
                'fin_mandat' => '17 mai 1995',
                'photo' => '/images/portraits_presidents/françois_mitterand_1981.avif',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/François_Mitterrand',
                'mandat_numero' => 21,
                'republique' => 'Ve République',
                'conjoint' => 'Danielle Mitterrand',
                'residence' => 'Palais de l\'Élysée',
                'actuel' => false,
                'bio' => 'François Mitterrand est le 21e président de la République française du 21 mai 1981 au 17 mai 1995. Premier président socialiste de la Ve République, il effectue deux septennats consécutifs, un record sous la Ve République.',
            ],
            'Valéry Giscard d\'Estaing' => [
                'nom' => 'Valéry Giscard d\'Estaing',
                'slug' => 'valery-giscard-destaing',
                'prenom' => 'Valéry',
                'nom_famille' => 'Giscard d\'Estaing',
                'date_naissance' => '2 février 1926',
                'date_deces' => '2 décembre 2020',
                'lieu_naissance' => 'Coblence (Allemagne)',
                'profession' => 'Inspecteur des finances',
                'parti' => 'UDF',
                'debut_mandat' => '27 mai 1974',
                'fin_mandat' => '21 mai 1981',
                'photo' => '/images/portraits_presidents/valery_giscard_destaing_1974.avif',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/Valéry_Giscard_d%27Estaing',
                'mandat_numero' => 20,
                'republique' => 'Ve République',
                'conjoint' => 'Anne-Aymone Giscard d\'Estaing',
                'residence' => 'Palais de l\'Élysée',
                'actuel' => false,
                'bio' => 'Valéry Giscard d\'Estaing est le 20e président de la République française du 27 mai 1974 au 21 mai 1981. Plus jeune président élu à 48 ans, il modernise la société française (majorité à 18 ans, IVG). Il décède le 2 décembre 2020.',
            ],
            'Georges Pompidou' => [
                'nom' => 'Georges Pompidou',
                'slug' => 'georges-pompidou',
                'prenom' => 'Georges',
                'nom_famille' => 'Pompidou',
                'date_naissance' => '5 juillet 1911',
                'date_deces' => '2 avril 1974',
                'lieu_naissance' => 'Montboudif (Cantal)',
                'profession' => 'Agrégé de lettres, banquier',
                'parti' => 'UDR',
                'debut_mandat' => '20 juin 1969',
                'fin_mandat' => '2 avril 1974',
                'photo' => '/images/portraits_presidents/georges_pompidou_1969.avif',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/Georges_Pompidou',
                'mandat_numero' => 19,
                'republique' => 'Ve République',
                'conjoint' => 'Claude Pompidou',
                'residence' => 'Palais de l\'Élysée',
                'actuel' => false,
                'bio' => 'Georges Pompidou est le 19e président de la République française du 20 juin 1969 au 2 avril 1974. Ancien Premier ministre du général de Gaulle, il succède à ce dernier après sa démission. Il décède en fonction le 2 avril 1974.',
            ],
            'Charles de Gaulle' => [
                'nom' => 'Charles de Gaulle',
                'slug' => 'charles-de-gaulle',
                'prenom' => 'Charles',
                'nom_famille' => 'de Gaulle',
                'date_naissance' => '22 novembre 1890',
                'date_deces' => '9 novembre 1970',
                'lieu_naissance' => 'Lille (Nord)',
                'profession' => 'Général, homme d\'État',
                'parti' => 'UNR / UDR',
                'debut_mandat' => '8 janvier 1959',
                'fin_mandat' => '28 avril 1969',
                'photo' => '/images/portraits_presidents/charles_de_gaulle_1959.avif',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/Charles_de_Gaulle',
                'mandat_numero' => 18,
                'republique' => 'Ve République',
                'conjoint' => 'Yvonne de Gaulle',
                'residence' => 'Palais de l\'Élysée',
                'actuel' => false,
                'bio' => 'Charles de Gaulle est le fondateur de la Ve République et son premier président du 8 janvier 1959 au 28 avril 1969. Chef de la France libre pendant la Seconde Guerre mondiale, il instaure la Ve République en 1958. Il démissionne en 1969 après l\'échec d\'un référendum.',
            ],
        ];

        return $data[$nom] ?? [
            'nom' => $nom,
            'slug' => \Illuminate\Support\Str::slug($nom),
            'photo' => null,
            'actuel' => false,
        ];
    }

    /**
     * Historique des gouvernements
     */
    public function historique(): Response
    {
        $gouvernements = Gouvernement::orderByDesc('date_debut')
            ->withCount('postes')
            ->get()
            ->groupBy('president')
            ->map(function ($gouvernements, $president) {
                return [
                    'president' => $president,
                    'periode' => $this->getPeriodePresident($president),
                    'gouvernements' => $gouvernements->map(fn($g) => [
                        'id' => $g->id,
                        'numero' => $g->numero,
                        'nom' => $g->nom,
                        'nom_complet' => $g->nom_complet,
                        'premier_ministre' => $g->premier_ministre,
                        'date_debut' => $g->date_debut?->format('d/m/Y'),
                        'date_fin' => $g->date_fin?->format('d/m/Y'),
                        'duree' => $g->duree,
                        'actif' => $g->actif,
                        'nb_postes' => $g->postes_count,
                    ])->values(),
                ];
            })->values();

        return Inertia::render('Gouvernement/Historique', [
            'gouvernementsParPresident' => $gouvernements,
        ]);
    }

    /**
     * Format les postes pour l'affichage
     */
    private function formatPostes($postes): array
    {
        return $postes->map(function ($poste) {
            return [
                'id' => $poste->id,
                'fonction' => $poste->fonction,
                'type_fonction' => $poste->type_fonction,
                'type_fonction_libelle' => $poste->type_fonction_libelle,
                'duree_fonction' => $poste->duree_fonction,
                'date_debut' => $poste->date_debut?->format('d/m/Y'),
                'date_fin' => $poste->date_fin?->format('d/m/Y'),
                'actif' => $poste->actif,
                'ministere' => $poste->ministere ? [
                    'id' => $poste->ministere->id,
                    'nom' => $poste->ministere->nom,
                    'sigle' => $poste->ministere->sigle,
                    'couleur' => $poste->ministere->couleur,
                ] : null,
                'personne' => $poste->personne ? [
                    'id' => $poste->personne->id,
                    'slug' => $poste->personne->slug,
                    'nom_complet' => $poste->personne->nom_complet,
                    'photo' => $poste->personne->photo,
                    'parti_politique' => $poste->personne->parti_politique,
                    'nb_postes' => $poste->personne->postes()->count(),
                ] : null,
            ];
        })->values()->toArray();
    }

    /**
     * Calcule la durée totale des postes
     */
    private function calculerDureeTotale($postes): string
    {
        $totalJours = 0;
        
        foreach ($postes as $poste) {
            $debut = $poste->date_debut;
            $fin = $poste->date_fin ?? now();
            $totalJours += $debut->diffInDays($fin);
        }

        if ($totalJours >= 365) {
            $annees = floor($totalJours / 365);
            $mois = floor(($totalJours % 365) / 30);
            return $annees . ' an' . ($annees > 1 ? 's' : '') . ($mois > 0 ? " et {$mois} mois" : '');
        }
        
        if ($totalJours >= 30) {
            return floor($totalJours / 30) . ' mois';
        }
        
        return $totalJours . ' jour' . ($totalJours > 1 ? 's' : '');
    }

    /**
     * Récupère la période d'un président
     */
    private function getPeriodePresident(string $president): string
    {
        $periodes = [
            'Emmanuel Macron' => '2017 - présent',
            'François Hollande' => '2012 - 2017',
            'Nicolas Sarkozy' => '2007 - 2012',
            'Jacques Chirac' => '1995 - 2007',
            'François Mitterrand' => '1981 - 1995',
        ];

        return $periodes[$president] ?? '';
    }
}
