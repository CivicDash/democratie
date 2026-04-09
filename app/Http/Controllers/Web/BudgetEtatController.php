<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BudgetAnnuel;
use App\Models\BudgetMinistere;
use App\Models\BudgetMission;
use App\Models\BudgetProgramme;
use App\Models\FranceBudgetRevenue;
use App\Models\InseeSalaire;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetEtatController extends Controller
{
    /**
     * Page principale du budget de l'État
     */
    public function index(Request $request): Response
    {
        $vue = $request->input('vue', 'missions'); // missions, ministeres, evolution

        // Années disponibles
        $anneesDisponibles = BudgetAnnuel::orderBy('annee', 'desc')
            ->pluck('annee')
            ->toArray();

        if (empty($anneesDisponibles)) {
            $anneesDisponibles = range(date('Y'), 2020);
        }

        // Année par défaut = la plus récente disponible en base
        $anneeDefaut = ! empty($anneesDisponibles) ? $anneesDisponibles[0] : date('Y');
        $annee = $request->input('annee', $anneeDefaut);

        // Données budget annuel
        $budgetAnnuel = BudgetAnnuel::where('annee', $annee)->first();

        // Missions par crédits (top 20)
        $missionsRaw = BudgetMission::where('annee', $annee)
            ->orderByDesc('credits_cp')
            ->limit(20)
            ->get();

        // Calcul du total pour les pourcentages
        $totalCreditsCP = $missionsRaw->sum(fn ($m) => (float) $m->credits_cp);

        $missions = $missionsRaw->map(fn ($m) => [
            'id' => $m->id,
            'code' => $m->code,
            'libelle' => $m->libelle,
            'credits_cp' => (float) $m->credits_cp,
            'credits_cp_md' => $m->credits_cp_md,
            'credits_cp_formate' => $m->credits_cp_formate,
            'nb_programmes' => $m->nb_programmes,
            'couleur' => BudgetMission::getCouleurMission($m->code),
            'part_pct' => $totalCreditsCP > 0
                ? round(((float) $m->credits_cp / $totalCreditsCP) * 100, 1)
                : 0,
        ]);

        // Ministères par budget
        $ministeresRaw = BudgetMinistere::where('annee', $annee)
            ->orderByDesc('budget_cp')
            ->get();

        $totalBudgetMinisteres = $ministeresRaw->sum(fn ($m) => (float) $m->budget_cp);

        $ministeres = $ministeresRaw->map(fn ($m) => [
            'id' => $m->id,
            'code' => $m->code,
            'nom' => $m->nom,
            'sigle' => $m->sigle,
            'budget_cp' => (float) $m->budget_cp,
            'budget_formate' => $m->budget_formate,
            'nb_programmes' => $m->nb_programmes,
            'couleur' => $m->couleur,
            'part_pct' => $totalBudgetMinisteres > 0
                ? round(((float) $m->budget_cp / $totalBudgetMinisteres) * 100, 1)
                : 0,
        ]);

        // Évolution historique
        $evolution = BudgetAnnuel::orderBy('annee')
            ->get()
            ->map(fn ($b) => [
                'annee' => $b->annee,
                'recettes' => round(($b->recettes_nettes ?? 0) / 1_000_000_000, 1),
                'depenses' => round(($b->depenses_nettes ?? 0) / 1_000_000_000, 1),
                'deficit' => round(($b->deficit ?? 0) / 1_000_000_000, 1),
                'dette_pib' => $b->dette_pib_pct,
                'deficit_pib' => $b->deficit_pib_pct,
            ]);

        // Stats globales
        $totalCP = BudgetMission::where('annee', $annee)->sum('credits_cp');
        $nbMissions = BudgetMission::where('annee', $annee)->count();
        $nbProgrammes = BudgetProgramme::where('annee', $annee)->count();

        // Recettes consolidées (Finances publiques totales - INSEE)
        $recettesConsolidees = FranceBudgetRevenue::where('year', $annee)
            ->orWhere('year', $annee - 1) // Fallback année précédente
            ->orderByDesc('year')
            ->first();

        $recettesParType = $recettesConsolidees ? [
            [
                'label' => 'Cotisations sociales',
                'value' => (float) $recettesConsolidees->social_contributions_billions_euros,
                'color' => '#10B981',
                'icon' => '🏥',
                'description' => 'URSSAF, retraites, chômage, maladie',
                'perimetre' => 'Sécurité sociale',
            ],
            [
                'label' => 'TVA',
                'value' => (float) $recettesConsolidees->tva_billions_euros,
                'color' => '#3B82F6',
                'icon' => '🛒',
                'description' => 'Taxe sur la valeur ajoutée',
                'perimetre' => 'État',
            ],
            [
                'label' => 'Impôt sur le revenu',
                'value' => (float) $recettesConsolidees->income_tax_billions_euros,
                'color' => '#8B5CF6',
                'icon' => '👤',
                'description' => 'IR des particuliers',
                'perimetre' => 'État',
            ],
            [
                'label' => 'Impôt sur les sociétés',
                'value' => (float) $recettesConsolidees->corporate_tax_billions_euros,
                'color' => '#F59E0B',
                'icon' => '🏢',
                'description' => 'IS des entreprises',
                'perimetre' => 'État',
            ],
            [
                'label' => 'Taxe foncière',
                'value' => (float) $recettesConsolidees->property_tax_billions_euros,
                'color' => '#EF4444',
                'icon' => '🏠',
                'description' => 'Impôts locaux sur le foncier',
                'perimetre' => 'Collectivités',
            ],
            [
                'label' => 'TICPE (carburants)',
                'value' => (float) $recettesConsolidees->fuel_tax_billions_euros,
                'color' => '#6B7280',
                'icon' => '⛽',
                'description' => 'Taxe intérieure de consommation',
                'perimetre' => 'État',
            ],
            [
                'label' => 'Autres taxes et recettes',
                'value' => (float) $recettesConsolidees->other_taxes_billions_euros,
                'color' => '#EC4899',
                'icon' => '📋',
                'description' => 'Droits de succession, douanes, etc.',
                'perimetre' => 'Divers',
            ],
        ] : [];

        // Calcul des totaux par périmètre
        $totalRecettes = $recettesConsolidees ? (float) $recettesConsolidees->total_billions_euros : 0;
        $recettesEtat = collect($recettesParType)->where('perimetre', 'État')->sum('value');
        $recettesSecu = collect($recettesParType)->where('perimetre', 'Sécurité sociale')->sum('value');
        $recettesLocales = collect($recettesParType)->where('perimetre', 'Collectivités')->sum('value');

        // Ajouter le pourcentage à chaque type de recette
        $recettesParType = collect($recettesParType)->map(function ($r) use ($totalRecettes) {
            $r['pct'] = $totalRecettes > 0 ? round(($r['value'] / $totalRecettes) * 100, 1) : 0;

            return $r;
        })->sortByDesc('value')->values()->toArray();

        return Inertia::render('BudgetEtat/Index', [
            'annee' => (int) $annee,
            'vue' => $vue,
            'anneesDisponibles' => $anneesDisponibles,
            'budgetAnnuel' => $budgetAnnuel ? [
                'recettes' => $budgetAnnuel->recettes_formate,
                'depenses' => $budgetAnnuel->depenses_formate,
                'deficit' => $budgetAnnuel->deficit_formate,
                'dette' => $budgetAnnuel->dette_formate,
                'deficit_pib' => $budgetAnnuel->deficit_pib_pct,
                'dette_pib' => $budgetAnnuel->dette_pib_pct,
                'sante_indicateur' => $budgetAnnuel->sante_indicateur,
                'dette_indicateur' => $budgetAnnuel->dette_indicateur,
            ] : null,
            'missions' => $missions,
            'ministeres' => $ministeres,
            'evolution' => $evolution,
            'stats' => [
                'total_cp' => $totalCP,
                'total_cp_formate' => $this->formatMontant($totalCP),
                'nb_missions' => $nbMissions,
                'nb_programmes' => $nbProgrammes,
                'nb_ministeres' => $ministeres->count(),
            ],
            // Données consolidées (Finances publiques totales)
            'recettesParType' => $recettesParType,
            'recettesConsolidees' => [
                'annee' => $recettesConsolidees?->year,
                'total' => $totalRecettes,
                'total_formate' => $this->formatMontant($totalRecettes * 1_000_000_000),
                'etat' => $recettesEtat,
                'etat_formate' => number_format($recettesEtat, 1, ',', ' ').' Md€',
                'securite_sociale' => $recettesSecu,
                'securite_sociale_formate' => number_format($recettesSecu, 1, ',', ' ').' Md€',
                'collectivites' => $recettesLocales,
                'collectivites_formate' => number_format($recettesLocales, 1, ',', ' ').' Md€',
            ],
            // Données URSSAF (emploi secteur privé)
            'urssafData' => $this->getUrssafData($annee),
            // Données Fonction Publique
            'fonctionPubliqueData' => $this->getFonctionPubliqueData($annee),
            // Synthèse emploi total
            'emploiTotal' => $this->getEmploiTotal($annee),
            // Données salaires INSEE (médian + moyen)
            'salairesFrance' => $this->getSalairesInsee($annee),
            // Explication des périmètres
            'perimetres' => [
                [
                    'id' => 'etat',
                    'nom' => 'Budget de l\'État',
                    'icon' => '🏛️',
                    'description' => 'Dépenses et recettes du gouvernement central (PLF)',
                    'recettes' => $budgetAnnuel?->recettes_nettes ? $this->formatMontant($budgetAnnuel->recettes_nettes) : 'N/A',
                    'depenses' => $budgetAnnuel?->depenses_nettes ? $this->formatMontant($budgetAnnuel->depenses_nettes) : 'N/A',
                    'color' => '#3B82F6',
                ],
                [
                    'id' => 'secu',
                    'nom' => 'Sécurité sociale',
                    'icon' => '🏥',
                    'description' => 'Assurance maladie, retraites, famille, chômage (PLFSS)',
                    'recettes' => number_format($recettesSecu, 0, ',', ' ').' Md€',
                    'depenses' => number_format($this->getDepensesSecu($annee), 0, ',', ' ').' Md€',
                    'deficit_secu' => $this->getDeficitSecu($annee),
                    'color' => '#10B981',
                ],
                [
                    'id' => 'collectivites',
                    'nom' => 'Collectivités locales',
                    'icon' => '🏘️',
                    'description' => 'Régions, départements, communes',
                    'recettes' => number_format($recettesLocales, 0, ',', ' ').' Md€',
                    'depenses' => '~'.number_format($recettesLocales, 0, ',', ' ').' Md€',
                    'color' => '#F59E0B',
                ],
            ],
        ]);
    }

    /**
     * Détail d'une mission budgétaire
     */
    public function showMission(Request $request, string $code): Response
    {
        $annee = $request->input('annee', date('Y'));

        $mission = BudgetMission::where('code', $code)
            ->where('annee', $annee)
            ->firstOrFail();

        $programmes = $mission->programmes()
            ->orderByDesc('credits_cp')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'libelle' => $p->libelle,
                'ministere' => $p->ministere,
                'credits_cp' => $p->credits_cp,
                'credits_cp_formate' => $p->credits_cp_formate,
                'evolution_pct' => $p->evolution_pct,
                'evolution_badge' => $p->evolution_badge,
            ]);

        return Inertia::render('BudgetEtat/Mission', [
            'mission' => [
                'id' => $mission->id,
                'code' => $mission->code,
                'libelle' => $mission->libelle,
                'annee' => $mission->annee,
                'credits_ae' => $mission->credits_ae_formate,
                'credits_cp' => $mission->credits_cp_formate,
                'nb_programmes' => $mission->nb_programmes,
                'couleur' => BudgetMission::getCouleurMission($mission->code),
            ],
            'programmes' => $programmes,
            'annee' => (int) $annee,
        ]);
    }

    /**
     * API : données pour graphiques
     */
    public function apiData(Request $request)
    {
        $annee = $request->input('annee', date('Y'));
        $type = $request->input('type', 'missions');

        if ($type === 'treemap') {
            return $this->getTreemapData($annee);
        }

        if ($type === 'evolution') {
            return BudgetAnnuel::orderBy('annee')->get();
        }

        return BudgetMission::where('annee', $annee)
            ->orderByDesc('credits_cp')
            ->get();
    }

    private function getTreemapData(int $annee): array
    {
        $missions = BudgetMission::where('annee', $annee)
            ->with('programmes')
            ->orderByDesc('credits_cp')
            ->get();

        return $missions->map(fn ($m) => [
            'name' => $m->libelle,
            'value' => round($m->credits_cp / 1_000_000_000, 2),
            'color' => BudgetMission::getCouleurMission($m->code),
            'children' => $m->programmes->map(fn ($p) => [
                'name' => $p->libelle,
                'value' => round($p->credits_cp / 1_000_000_000, 2),
            ])->toArray(),
        ])->toArray();
    }

    private function formatMontant(?float $montant): string
    {
        if ($montant === null) {
            return 'N/A';
        }

        if ($montant >= 1_000_000_000) {
            return number_format($montant / 1_000_000_000, 1, ',', ' ').' Md€';
        }

        return number_format($montant / 1_000_000, 0, ',', ' ').' M€';
    }

    /**
     * Dépenses de la Sécurité sociale par année (PLFSS)
     */
    private function getDepensesSecu(int $annee): float
    {
        // Données PLFSS - source: securite-sociale.fr
        $depenses = [
            2024 => 614.0,
            2023 => 598.0,
            2022 => 550.0,
            2021 => 525.0,
            2020 => 510.0,
            2019 => 505.0,
        ];

        return $depenses[$annee] ?? ($depenses[2024] ?? 600.0);
    }

    /**
     * Déficit de la Sécurité sociale par année
     */
    private function getDeficitSecu(int $annee): ?array
    {
        $depenses = $this->getDepensesSecu($annee);
        $recettes = FranceBudgetRevenue::where('year', $annee)->value('social_contributions_billions_euros') ?? 0;

        if ($recettes <= 0) {
            return null;
        }

        $deficit = $recettes - $depenses;

        return [
            'montant' => $deficit,
            'formate' => ($deficit >= 0 ? '+' : '').number_format($deficit, 1, ',', ' ').' Md€',
            'is_positive' => $deficit >= 0,
        ];
    }

    /**
     * Récupère les données URSSAF pour l'année (emploi & cotisations)
     * Agrège les données par grand secteur (GS1 Industrie, GS2 Construction, etc.)
     */
    private function getUrssafData(int $annee): ?array
    {
        // Vérifier si la table existe
        if (! \Schema::hasTable('urssaf_effectifs_national')) {
            return null;
        }

        // Trouver l'année de données disponible
        $anneeDisponible = \DB::table('urssaf_effectifs_national')
            ->where('annee', '<=', $annee)
            ->max('annee');

        if (! $anneeDisponible) {
            $anneeDisponible = \DB::table('urssaf_effectifs_national')->max('annee');
        }

        if (! $anneeDisponible) {
            return null;
        }

        // Agrégation par GRAND SECTEUR (secteur_libelle = GS1, GS2, etc.)
        $parGrandSecteur = \DB::table('urssaf_effectifs_national')
            ->select('secteur_libelle')
            ->selectRaw('SUM(effectif) as effectifs')
            ->selectRaw('SUM(masse_salariale) as masse_salariale')
            ->selectRaw('SUM(nombre) as nb_etablissements')
            ->selectRaw('COUNT(*) as nb_sous_secteurs')
            ->where('annee', $anneeDisponible)
            ->whereNotNull('secteur_libelle')
            ->groupBy('secteur_libelle')
            ->orderByDesc('effectifs')
            ->get();

        if ($parGrandSecteur->isEmpty()) {
            return null;
        }

        // Top secteurs formatés
        $topSecteurs = $parGrandSecteur->map(fn ($row) => [
            'secteur' => $row->secteur_libelle,
            'code' => $row->secteur_libelle,
            'effectifs' => (int) $row->effectifs,
            'effectifs_formate' => number_format($row->effectifs, 0, ',', ' '),
            'masse_salariale' => (float) $row->masse_salariale,
            'masse_salariale_md' => round($row->masse_salariale / 1_000_000_000, 1),
            'nb_etablissements' => (int) $row->nb_etablissements,
            'nb_sous_secteurs' => (int) $row->nb_sous_secteurs,
            'salaire_moyen' => $row->effectifs > 0
                ? round($row->masse_salariale / $row->effectifs / 12)
                : 0,
        ])->values();

        // Totaux
        $totalEffectifs = $parGrandSecteur->sum('effectifs');
        $totalMasseSalariale = $parGrandSecteur->sum('masse_salariale');

        // Évolution par année (agrégée)
        $evolution = \DB::table('urssaf_effectifs_national')
            ->select('annee')
            ->selectRaw('SUM(effectif) as total_effectifs')
            ->selectRaw('SUM(masse_salariale) as total_masse_salariale')
            ->groupBy('annee')
            ->orderBy('annee')
            ->get()
            ->map(fn ($row) => [
                'annee' => (int) $row->annee,
                'effectifs' => (int) $row->total_effectifs,
                'effectifs_millions' => round($row->total_effectifs / 1_000_000, 2),
                'masse_salariale_md' => round($row->total_masse_salariale / 1_000_000_000, 1),
            ]);

        return [
            'annee' => (int) $anneeDisponible,
            'source' => 'URSSAF Open Data (secteur privé)',
            'total_effectifs' => $totalEffectifs,
            'total_effectifs_formate' => number_format($totalEffectifs, 0, ',', ' '),
            'total_effectifs_millions' => round($totalEffectifs / 1_000_000, 1),
            'total_masse_salariale' => $totalMasseSalariale,
            'total_masse_salariale_formate' => number_format($totalMasseSalariale / 1_000_000_000, 1, ',', ' ').' Md€',
            'salaire_moyen_mensuel' => $totalEffectifs > 0
                ? number_format(round($totalMasseSalariale / $totalEffectifs / 12), 0, ',', ' ').' €'
                : 'N/A',
            'top_secteurs' => $topSecteurs->toArray(),
            'evolution' => $evolution->toArray(),
            'note' => 'Données secteur privé uniquement (hors fonction publique)',
        ];
    }

    /**
     * Récupère les données de la fonction publique
     */
    private function getFonctionPubliqueData(int $annee): ?array
    {
        if (! \Schema::hasTable('fonction_publique_effectifs')) {
            return null;
        }

        // Trouver l'année disponible la plus proche
        $anneeDisponible = \DB::table('fonction_publique_effectifs')
            ->where('annee', '<=', $annee)
            ->max('annee');

        if (! $anneeDisponible) {
            $anneeDisponible = \DB::table('fonction_publique_effectifs')->max('annee');
        }

        if (! $anneeDisponible) {
            return null;
        }

        $data = \DB::table('fonction_publique_effectifs')
            ->where('annee', $anneeDisponible)
            ->orderBy('effectif_total', 'desc')
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $versants = $data->map(fn ($row) => [
            'versant' => $row->versant,
            'libelle' => $row->versant_libelle,
            'effectif' => (int) $row->effectif_total,
            'effectif_formate' => number_format($row->effectif_total, 0, ',', ' '),
            'titulaires' => (int) ($row->titulaires ?? 0),
            'contractuels' => (int) ($row->contractuels ?? 0),
            'autres' => (int) ($row->autres ?? 0),
            'masse_salariale_md' => (float) ($row->masse_salariale_md ?? 0),
            'notes' => $row->notes,
        ])->values();

        $totalEffectifs = $data->sum('effectif_total');
        $totalMasseSalariale = $data->sum('masse_salariale_md');

        // Évolution par année
        $evolution = \DB::table('fonction_publique_effectifs')
            ->select('annee')
            ->selectRaw('SUM(effectif_total) as total')
            ->selectRaw('SUM(masse_salariale_md) as masse_salariale')
            ->groupBy('annee')
            ->orderBy('annee')
            ->get()
            ->map(fn ($row) => [
                'annee' => (int) $row->annee,
                'effectifs' => (int) $row->total,
                'effectifs_millions' => round($row->total / 1_000_000, 2),
                'masse_salariale_md' => (float) $row->masse_salariale,
            ]);

        return [
            'annee' => (int) $anneeDisponible,
            'source' => 'DGAFP - Rapport annuel',
            'total_effectifs' => $totalEffectifs,
            'total_effectifs_formate' => number_format($totalEffectifs, 0, ',', ' '),
            'total_effectifs_millions' => round($totalEffectifs / 1_000_000, 2),
            'total_masse_salariale_md' => $totalMasseSalariale,
            'total_masse_salariale_formate' => number_format($totalMasseSalariale, 1, ',', ' ').' Md€',
            'versants' => $versants->toArray(),
            'evolution' => $evolution->toArray(),
        ];
    }

    /**
     * Calcule le total emploi (privé + public)
     */
    private function getEmploiTotal(int $annee): ?array
    {
        $urssaf = $this->getUrssafData($annee);
        $fp = $this->getFonctionPubliqueData($annee);

        if (! $urssaf && ! $fp) {
            return null;
        }

        $effectifsPrives = $urssaf['total_effectifs'] ?? 0;
        $effectifsPublics = $fp['total_effectifs'] ?? 0;
        $total = $effectifsPrives + $effectifsPublics;

        $masseSalarialePrivee = ($urssaf['total_masse_salariale'] ?? 0) / 1_000_000_000; // Convertir en Md€
        $masseSalarialePublique = $fp['total_masse_salariale_md'] ?? 0;
        $masseSalarialeTotal = $masseSalarialePrivee + $masseSalarialePublique;

        return [
            'annee_prive' => $urssaf['annee'] ?? null,
            'annee_public' => $fp['annee'] ?? null,
            'effectifs_prives' => $effectifsPrives,
            'effectifs_prives_formate' => number_format($effectifsPrives, 0, ',', ' '),
            'effectifs_publics' => $effectifsPublics,
            'effectifs_publics_formate' => number_format($effectifsPublics, 0, ',', ' '),
            'total_effectifs' => $total,
            'total_effectifs_formate' => number_format($total, 0, ',', ' '),
            'total_effectifs_millions' => round($total / 1_000_000, 1),
            'masse_salariale_privee_md' => round($masseSalarialePrivee, 1),
            'masse_salariale_publique_md' => round($masseSalarialePublique, 1),
            'masse_salariale_total_md' => round($masseSalarialeTotal, 1),
            'part_public_pct' => $total > 0 ? round(($effectifsPublics / $total) * 100, 1) : 0,
            'part_prive_pct' => $total > 0 ? round(($effectifsPrives / $total) * 100, 1) : 0,
            'note' => 'Hors agricoles (MSA ~1,2M) et travailleurs indépendants',
        ];
    }

    /**
     * Récupère les données de salaires INSEE (médian, moyen, distribution)
     */
    private function getSalairesInsee(int $annee): ?array
    {
        // Récupérer les données pour l'année demandée ou la plus proche
        $global = InseeSalaire::where('type', 'global')
            ->where('annee', '<=', $annee)
            ->orderByDesc('annee')
            ->first();

        if (! $global) {
            // Fallback sur les dernières données disponibles
            $global = InseeSalaire::where('type', 'global')
                ->orderByDesc('annee')
                ->first();
        }

        if (! $global) {
            return null;
        }

        // Données par catégorie socio-professionnelle
        $parCategorie = InseeSalaire::where('type', 'prive')
            ->where('annee', $global->annee)
            ->whereNotNull('categorie')
            ->get()
            ->map(fn ($s) => [
                'categorie' => match ($s->categorie) {
                    'cadres' => 'Cadres',
                    'professions_intermediaires' => 'Professions intermédiaires',
                    'employes' => 'Employés',
                    'ouvriers' => 'Ouvriers',
                    default => ucfirst(str_replace('_', ' ', $s->categorie)),
                },
                'salaire_median' => $s->salaire_median,
                'salaire_median_formate' => $s->salaire_median_formate,
                'salaire_moyen' => $s->salaire_moyen,
                'salaire_moyen_formate' => $s->salaire_moyen_formate,
            ])
            ->values();

        // Données fonction publique
        $parFonctionPublique = InseeSalaire::where('type', 'public')
            ->where('annee', $global->annee)
            ->whereNotNull('categorie')
            ->get()
            ->map(fn ($s) => [
                'categorie' => match ($s->categorie) {
                    'fonction_publique_etat' => 'État (FPE)',
                    'fonction_publique_territoriale' => 'Territoriale (FPT)',
                    'fonction_publique_hospitaliere' => 'Hospitalière (FPH)',
                    default => ucfirst(str_replace('_', ' ', $s->categorie)),
                },
                'salaire_median' => $s->salaire_median,
                'salaire_median_formate' => $s->salaire_median_formate,
                'salaire_moyen' => $s->salaire_moyen,
                'salaire_moyen_formate' => $s->salaire_moyen_formate,
            ])
            ->values();

        return [
            'annee' => $global->annee,
            'source' => $global->source,
            // Salaires globaux
            'salaire_median' => $global->salaire_median,
            'salaire_median_formate' => $global->salaire_median_formate,
            'salaire_moyen' => $global->salaire_moyen,
            'salaire_moyen_formate' => $global->salaire_moyen_formate,
            'ecart_moyen_median_pct' => $global->ecart_moyen_median,
            // Distribution (déciles)
            'd1' => $global->d1,
            'd1_formate' => $global->d1 ? number_format($global->d1, 0, ',', ' ').' €' : null,
            'd5' => $global->d5, // = médiane
            'd9' => $global->d9,
            'd9_formate' => $global->d9 ? number_format($global->d9, 0, ',', ' ').' €' : null,
            'rapport_interdecile' => $global->rapport_interdecile,
            // Détail par catégorie
            'par_categorie' => $parCategorie->toArray(),
            'par_fonction_publique' => $parFonctionPublique->toArray(),
            // Notes
            'notes' => $global->notes,
            'info' => 'Le salaire médian divise les salariés en deux : 50% gagnent moins, 50% gagnent plus. Plus représentatif que la moyenne.',
        ];
    }
}
