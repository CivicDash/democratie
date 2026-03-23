<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ElusGlobalStats;
use App\Models\Gouvernement;
use App\Models\GroupeParlementaire;
use App\Models\Loi;
use App\Models\OrganeAN;
use App\Models\ScrutinAN;
use App\Services\GroupeParlementaireService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DemocratieController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Democratie/Index');
    }

    public function parcoursLoi(): Response
    {
        $exemples = Cache::remember('democratie.lois_exemples', 3600, function () {
            $promulguee = Loi::avecParcours()->promulguees()->recentes()->first();
            $enCours = Loi::avecParcours()->enCours()->recentes()->first();
            $rejetee = Loi::avecParcours()->rejetees()->recentes()->first();

            return collect([$promulguee, $enCours, $rejetee])
                ->filter()
                ->map(fn (Loi $loi) => [
                    'loicod' => $loi->loicod_clean,
                    'titre' => $loi->titre_court,
                    'etat' => $loi->etat?->etaloilib ?? 'Inconnu',
                    'etat_code' => $loi->etaloicod,
                    'progression' => $loi->progression,
                    'parcours' => $loi->getParcours(),
                    'url' => $loi->url,
                ])
                ->values();
        });

        return Inertia::render('Democratie/ParcoursLoi', [
            'exemples' => $exemples,
        ]);
    }

    public function elections(): Response
    {
        return Inertia::render('Democratie/Elections');
    }

    /**
     * Ordre politique gauche→droite pour le placement en hémicycle.
     */
    private const ORDRE_HEMICYCLE_AN = [
        'LFI-NFP' => 1,
        'GDR'     => 2,
        'ECOLO'   => 3,
        'SOC'     => 4,
        'RE'      => 5,
        'DEM'     => 6,
        'HOR'     => 7,
        'LIOT'    => 8,
        'LR'      => 9,
        'RN'      => 10,
    ];

    private const ORDRE_HEMICYCLE_SENAT = [
        'CRCE' => 1,
        'SER'  => 2,
        'GEST' => 3,
        'RDSE' => 4,
        'RDPI' => 5,
        'UC'   => 6,
        'INDEP' => 7,
        'LR'   => 8,
    ];

    public function representants(): Response
    {
        $groupesAN = GroupeParlementaire::actif()
            ->assemblee()
            ->get(['id', 'nom', 'sigle', 'couleur_hex', 'nombre_membres', 'position_politique'])
            ->sortBy(fn ($g) => self::ORDRE_HEMICYCLE_AN[$g->sigle] ?? 50)
            ->values();

        $groupesSenat = GroupeParlementaire::actif()
            ->senat()
            ->get(['id', 'nom', 'sigle', 'couleur_hex', 'nombre_membres', 'position_politique'])
            ->sortBy(fn ($g) => self::ORDRE_HEMICYCLE_SENAT[$g->sigle] ?? 50)
            ->values();

        $stats = ElusGlobalStats::getAllForComparison();

        return Inertia::render('Democratie/Representants', [
            'groupesAN' => $groupesAN,
            'groupesSenat' => $groupesSenat,
            'stats' => $stats,
        ]);
    }

    public function votes(): Response
    {
        $dernierScrutin = Cache::remember('democratie.dernier_scrutin', 1800, function () {
            $scrutin = ScrutinAN::where('type_vote_code', 'SPS')
                ->orderByDesc('date_scrutin')
                ->orderByDesc('numero')
                ->first();

            if (! $scrutin) {
                $scrutin = ScrutinAN::orderByDesc('date_scrutin')
                    ->orderByDesc('numero')
                    ->first();
            }

            if (! $scrutin) {
                return null;
            }

            $ventilationGroupes = [];
            $ventilation = $scrutin->ventilation_votes;
            $couleurService = app(GroupeParlementaireService::class);

            if ($ventilation && isset($ventilation['organe']['groupes']['groupe'])) {
                $organeRefs = collect($ventilation['organe']['groupes']['groupe'])
                    ->pluck('organeRef')
                    ->filter()
                    ->unique()
                    ->values();

                $organes = OrganeAN::whereIn('uid', $organeRefs)
                    ->get()
                    ->keyBy('uid');

                foreach ($ventilation['organe']['groupes']['groupe'] as $g) {
                    $decompte = $g['vote']['decompteVoix'] ?? [];
                    $organeRef = $g['organeRef'] ?? null;
                    $organe = $organes->get($organeRef);
                    $sigle = $organe?->libelle_abrege ?? '?';

                    $ventilationGroupes[] = [
                        'organe_ref' => $organeRef,
                        'nom' => $organe?->libelle ?? $organeRef,
                        'sigle' => $sigle,
                        'couleur' => $couleurService->getCouleurGroupe($sigle),
                        'pour' => (int) ($decompte['pour'] ?? 0),
                        'contre' => (int) ($decompte['contre'] ?? 0),
                        'abstentions' => (int) ($decompte['abstentions'] ?? 0),
                        'non_votants' => (int) ($decompte['nonVotants'] ?? 0),
                    ];
                }
            }

            return [
                'uid' => $scrutin->uid,
                'numero' => $scrutin->numero,
                'titre' => $scrutin->titre,
                'date' => $scrutin->date_scrutin?->format('d/m/Y'),
                'type' => $scrutin->type_vote_libelle,
                'resultat' => $scrutin->resultat_format,
                'est_adopte' => $scrutin->est_adopte,
                'pour' => $scrutin->pour_calcule,
                'contre' => $scrutin->contre_calcule,
                'abstentions' => $scrutin->abstentions_calcule,
                'nombre_votants' => $scrutin->nombre_votants,
                'taux_participation' => $scrutin->taux_participation,
                'ventilation_groupes' => $ventilationGroupes,
            ];
        });

        $groupes = GroupeParlementaire::actif()
            ->assemblee()
            ->orderByDesc('nombre_membres')
            ->get(['id', 'uid', 'nom', 'sigle', 'couleur_hex', 'nombre_membres']);

        return Inertia::render('Democratie/Votes', [
            'dernierScrutin' => $dernierScrutin,
            'groupes' => $groupes,
        ]);
    }

    public function gouvernement(): Response
    {
        $gouvernement = Gouvernement::actuel();

        $postesParType = [];
        $stats = null;

        if ($gouvernement) {
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

            $postesParType = [
                'premier_ministre' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'premier_ministre')),
                'ministre' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'ministre')),
                'ministre_delegue' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'ministre_delegue')),
                'secretaire_etat' => $this->formatPostes($gouvernement->postes->where('type_fonction', 'secretaire_etat')),
            ];

            $stats = [
                'total' => $gouvernement->postes->count(),
                'nb_ministres' => $gouvernement->postes->where('type_fonction', 'ministre')->count(),
                'nb_ministres_delegues' => $gouvernement->postes->where('type_fonction', 'ministre_delegue')->count(),
                'nb_secretaires_etat' => $gouvernement->postes->where('type_fonction', 'secretaire_etat')->count(),
            ];
        }

        return Inertia::render('Democratie/Gouvernement', [
            'gouvernement' => $gouvernement ? [
                'nom' => $gouvernement->nom,
                'premier_ministre' => $gouvernement->premier_ministre,
                'president' => $gouvernement->president,
                'date_debut' => $gouvernement->date_debut?->format('d/m/Y'),
                'actif' => $gouvernement->actif,
            ] : null,
            'postesParType' => $postesParType,
            'stats' => $stats,
        ]);
    }

    private function formatPostes($postes): array
    {
        return $postes->map(fn ($p) => [
            'id' => $p->id,
            'nom' => $p->personne?->prenom . ' ' . $p->personne?->nom,
            'fonction' => $p->fonction ?? $p->type_fonction_libelle ?? $p->type_fonction,
            'ministere' => $p->ministere?->nom,
            'photo_url' => $p->personne?->photo_url,
            'slug' => $p->personne?->slug,
        ])->values()->toArray();
    }

    public function conseilConstitutionnel(): Response
    {
        return Inertia::render('Democratie/ConseilConstitutionnel');
    }
}
