<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SenatDebat;
use App\Models\SenatSectionDiscussion;
use App\Models\SenatInterventionLegislative;
use App\Models\Senateur;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DebatSenatController extends Controller
{
    /**
     * Liste des séances de débat
     */
    public function index(Request $request)
    {
        $query = SenatDebat::query()
            ->orderBy('date_seance', 'desc');

        // Filtre par année
        if ($request->filled('annee')) {
            $query->whereYear('date_seance', $request->annee);
        }

        // Filtre par mois
        if ($request->filled('mois')) {
            $query->whereMonth('date_seance', $request->mois);
        }

        $debats = $query->paginate(20)->withQueryString();

        // Stats rapides
        $stats = [
            'total_seances' => SenatDebat::count(),
            'total_sections' => DB::table('senat_sections_discussion')->count(),
            'total_interventions' => DB::table('senat_interventions_legislatives')->count(),
            'annees_disponibles' => SenatDebat::selectRaw('EXTRACT(YEAR FROM date_seance) as annee')
                ->distinct()
                ->orderByDesc('annee')
                ->pluck('annee')
                ->toArray(),
        ];

        return Inertia::render('Debats/Senat/Index', [
            'debats' => $debats,
            'stats' => $stats,
            'filtres' => $request->only(['annee', 'mois']),
        ]);
    }

    /**
     * Détail d'une séance de débat
     */
    public function show(string $dateSeance)
    {
        $debat = SenatDebat::where('date_seance', $dateSeance)->firstOrFail();

        // Récupérer les sections principales (sans parent)
        $sectionsLegislatives = SenatSectionDiscussion::where('date_seance', $debat->date_seance)
            ->whereNull('parent_id')
            ->with(['typeSection', 'enfants.typeSection'])
            ->orderBy('ordre')
            ->get()
            ->map(fn($s) => $this->formatSection($s));

        $sectionsDiverses = DB::table('senat_sections_diverses')
            ->where('date_seance', $debat->date_seance)
            ->whereNull('parent_id')
            ->orderBy('ordre')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'type' => $s->type_section,
                'objet' => $s->objet,
                'url' => $s->url ? 'https://www.senat.fr/seances/' . ltrim($s->url, '/') : null,
            ]);

        // Top intervenants de la séance
        $topIntervenants = DB::table('senat_interventions_legislatives as i')
            ->join('senat_sections_discussion as s', 'i.section_id', '=', 's.id')
            ->where('s.date_seance', $debat->date_seance)
            ->select('i.auteur_code', DB::raw('COUNT(*) as nb_interventions'))
            ->groupBy('i.auteur_code')
            ->orderByDesc('nb_interventions')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                // Chercher dans senat_dosleg_auteur (table de référence des auteurs)
                $auteur = DB::table('senat_dosleg_auteur')
                    ->where('autcod', trim($row->auteur_code))
                    ->first();
                
                // Essayer de trouver le sénateur correspondant pour la photo
                $senateur = null;
                if ($auteur) {
                    $senateur = Senateur::where('nom', 'ILIKE', $auteur->nomuse)
                        ->where('prenom', 'ILIKE', $auteur->prenom)
                        ->first();
                }
                
                return [
                    'code' => $row->auteur_code,
                    'nb_interventions' => $row->nb_interventions,
                    'auteur' => $auteur ? [
                        'nom' => ucfirst(strtolower($auteur->nomuse)),
                        'prenom' => ucfirst(strtolower($auteur->prenom)),
                        'photo_url' => $senateur?->photo_url ?? $senateur?->photo_wikipedia_url,
                        'matricule' => $senateur?->matricule,
                    ] : null,
                ];
            });

        return Inertia::render('Debats/Senat/Show', [
            'debat' => [
                'date_seance' => $debat->date_seance->toIso8601String(),
                'date_formatee' => $debat->date_formatee,
                'numero' => $debat->numero,
                'url_compte_rendu' => $debat->url_compte_rendu,
                'libelle_special' => $debat->libelle_special,
                'est_congres' => $debat->est_congres,
            ],
            'sectionsLegislatives' => $sectionsLegislatives,
            'sectionsDiverses' => $sectionsDiverses,
            'topIntervenants' => $topIntervenants,
        ]);
    }

    /**
     * Détail d'une section avec toutes les interventions
     */
    public function section(int $sectionId)
    {
        $section = SenatSectionDiscussion::with(['typeSection', 'debat'])
            ->findOrFail($sectionId);

        // Récupérer les interventions avec infos auteur
        $interventions = SenatInterventionLegislative::where('section_id', $sectionId)
            ->orderBy('ordre')
            ->get()
            ->map(function ($i) {
                // Chercher dans senat_dosleg_auteur
                $auteur = DB::table('senat_dosleg_auteur')
                    ->where('autcod', trim($i->auteur_code))
                    ->first();
                
                // Essayer de trouver le sénateur pour la photo
                $senateur = null;
                if ($auteur) {
                    $senateur = Senateur::where('nom', 'ILIKE', $auteur->nomuse)
                        ->where('prenom', 'ILIKE', $auteur->prenom)
                        ->first();
                }
                
                return [
                    'id' => $i->id,
                    'analyse' => $i->analyse,
                    'fonction' => $i->fonction,
                    'url' => $i->url_complet,
                    'auteur' => $auteur ? [
                        'nom' => ucfirst(strtolower($auteur->nomuse)),
                        'prenom' => ucfirst(strtolower($auteur->prenom)),
                        'photo_url' => $senateur?->photo_url ?? $senateur?->photo_wikipedia_url,
                        'matricule' => $senateur?->matricule,
                    ] : [
                        'nom' => 'Intervenant',
                        'prenom' => $i->auteur_code,
                    ],
                ];
            });

        // Sections enfants
        $enfants = SenatSectionDiscussion::where('parent_id', $sectionId)
            ->with('typeSection')
            ->orderBy('ordre')
            ->get()
            ->map(fn($s) => $this->formatSection($s, false));

        return Inertia::render('Debats/Senat/Section', [
            'section' => [
                'id' => $section->id,
                'type' => $section->type_section,
                'type_libelle' => $section->typeSection?->libelle ?? $section->typeSection?->libelle_format ?? $section->type_section,
                'numero' => $section->numero,
                'objet' => $section->objet,
                'url' => $section->url_complet,
                'lecture_id' => $section->lecture_id,
            ],
            'debat' => [
                'date_seance' => $section->debat->date_seance->toIso8601String(),
                'date_formatee' => $section->debat->date_formatee,
            ],
            'interventions' => $interventions,
            'enfants' => $enfants,
        ]);
    }

    /**
     * Interventions d'un sénateur en séance
     */
    public function parSenateur(string $matricule, Request $request)
    {
        $senateur = Senateur::where('matricule', $matricule)->firstOrFail();

        // Trouver le code auteur correspondant dans senat_dosleg_auteur (via nom/prénom)
        $auteur = DB::table('senat_dosleg_auteur')
            ->where('nomuse', 'ILIKE', $senateur->nom)
            ->where('prenom', 'ILIKE', $senateur->prenom)
            ->first();

        $auteurCode = $auteur?->autcod;

        if (!$auteurCode) {
            // Pas de correspondance trouvée - retourner une page vide
            return Inertia::render('Debats/Senat/ParSenateur', [
                'senateur' => [
                    'id' => $senateur->matricule,
                    'matricule' => $senateur->matricule,
                    'nom' => $senateur->nom,
                    'prenom' => $senateur->prenom,
                    'photo_url' => $senateur->photo_url ?? $senateur->photo_wikipedia_url,
                    'groupe' => $senateur->groupe_politique,
                ],
                'interventions' => [],
                'stats' => ['total' => 0, 'par_annee' => []],
                'filtres' => $request->only(['annee']),
            ]);
        }

        $query = DB::table('senat_interventions_legislatives as i')
            ->join('senat_sections_discussion as s', 'i.section_id', '=', 's.id')
            ->where('i.auteur_code', $auteurCode)
            ->select([
                'i.id',
                'i.analyse',
                'i.fonction',
                'i.url',
                's.date_seance',
                's.objet as section_objet',
                's.type_section',
            ])
            ->orderByDesc('s.date_seance');

        // Filtre par année
        if ($request->filled('annee')) {
            $query->whereYear('s.date_seance', $request->annee);
        }

        $interventions = $query->paginate(30)->withQueryString();

        // Stats
        $stats = [
            'total' => DB::table('senat_interventions_legislatives')
                ->where('auteur_code', $auteurCode)
                ->count(),
            'par_annee' => DB::table('senat_interventions_legislatives as i')
                ->join('senat_sections_discussion as s', 'i.section_id', '=', 's.id')
                ->where('i.auteur_code', $auteurCode)
                ->selectRaw('EXTRACT(YEAR FROM s.date_seance) as annee, COUNT(*) as nb')
                ->groupBy('annee')
                ->orderByDesc('annee')
                ->limit(10)
                ->get(),
        ];

        return Inertia::render('Debats/Senat/ParSenateur', [
            'senateur' => [
                'id' => $senateur->matricule,
                'matricule' => $senateur->matricule,
                'nom' => $senateur->nom,
                'prenom' => $senateur->prenom,
                'photo_url' => $senateur->photo_url ?? $senateur->photo_wikipedia_url,
                'groupe' => $senateur->groupe_politique,
            ],
            'interventions' => $interventions,
            'stats' => $stats,
            'filtres' => $request->only(['annee']),
        ]);
    }

    /**
     * Formater une section pour l'affichage
     */
    private function formatSection($section, $withChildren = true): array
    {
        $data = [
            'id' => $section->id,
            'type' => $section->type_section,
            'type_libelle' => $section->typeSection?->libelle ?? $section->typeSection?->libelle_format ?? $section->type_section,
            'numero' => $section->numero,
            'objet' => $section->objet,
            'url' => $section->url_complet,
            'nb_interventions' => DB::table('senat_interventions_legislatives')
                ->where('section_id', $section->id)
                ->count(),
        ];

        if ($withChildren && $section->enfants) {
            $data['enfants'] = $section->enfants->map(fn($e) => $this->formatSection($e, false))->toArray();
        }

        return $data;
    }
}
