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
    public function showPresident(): Response
    {
        // Gouvernement actuel
        $gouvernementActuel = Gouvernement::actuel();
        $presidentActuel = $gouvernementActuel?->president ?? 'Emmanuel Macron';

        // Données du président actuel
        $president = $this->getPresidentData($presidentActuel);

        // Tous les gouvernements du président actuel
        $gouvernements = Gouvernement::where('president', $presidentActuel)
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

        // Liste des présidents
        $presidents = [
            [
                'nom' => 'Emmanuel Macron',
                'mandat' => '2017 - aujourd\'hui',
                'photo' => 'https://www.elysee.fr/images/default/0001/16/e3aa06e6e24f3b0fc03eb5e9e8c3d45f2bc57b12.png',
                'actuel' => $presidentActuel === 'Emmanuel Macron',
            ],
            [
                'nom' => 'François Hollande',
                'mandat' => '2012 - 2017',
                'photo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/05/Fran%C3%A7ois_Hollande_-_Janvier_2012.jpg/220px-Fran%C3%A7ois_Hollande_-_Janvier_2012.jpg',
                'actuel' => $presidentActuel === 'François Hollande',
            ],
            [
                'nom' => 'Nicolas Sarkozy',
                'mandat' => '2007 - 2012',
                'photo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Nicolas_Sarkozy_%282%29.jpg/220px-Nicolas_Sarkozy_%282%29.jpg',
                'actuel' => $presidentActuel === 'Nicolas Sarkozy',
            ],
        ];

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
     * Données du président
     */
    private function getPresidentData(string $nom): array
    {
        $data = [
            'Emmanuel Macron' => [
                'nom' => 'Emmanuel Macron',
                'prenom' => 'Emmanuel',
                'nom_famille' => 'Macron',
                'date_naissance' => '21 décembre 1977',
                'age' => now()->diffInYears(\Carbon\Carbon::parse('1977-12-21')),
                'lieu_naissance' => 'Amiens (Somme)',
                'profession' => 'Inspecteur des finances, banquier d\'affaires',
                'parti' => 'Renaissance (ex-LREM)',
                'debut_mandat' => '14 mai 2017',
                'photo' => 'https://www.elysee.fr/images/default/0001/16/e3aa06e6e24f3b0fc03eb5e9e8c3d45f2bc57b12.png',
                'wikipedia' => 'https://fr.wikipedia.org/wiki/Emmanuel_Macron',
                'mandat_numero' => 25,
                'republique' => 'Ve République',
                'conjoint' => 'Brigitte Macron',
                'residence' => 'Palais de l\'Élysée',
            ],
        ];

        return $data[$nom] ?? [
            'nom' => $nom,
            'photo' => null,
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
