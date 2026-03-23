<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CandidatMunicipal;
use App\Models\CandidatureDocument;
use App\Models\ListeElectorale;
use App\Models\Maire;
use App\Models\ResultatListeMunicipale;
use App\Models\ResultatMunicipal;
use App\Models\StatsElectionMunicipale;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Inertia\Inertia;

class ElectionsMunicipalesController extends Controller
{
    // =========================================================================
    // PAGES PUBLIQUES
    // =========================================================================

    /**
     * Page principale des élections municipales
     */
    public function index()
    {
        $datesElection = [
            'premier_tour' => '2026-03-15',
            'second_tour' => '2026-03-22',
            'limite_depot' => '2026-02-27',
            'debut_campagne' => '2026-03-02',
        ];

        $electionsPassees = now()->greaterThan(Carbon::parse($datesElection['premier_tour']));

        // Compteurs basés sur les listes officielles (datagouv) si disponibles, sinon CivicDash
        $totalListesOfficielles = ListeElectorale::officielles()->count();
        $totalCandidatsOfficiels = CandidatMunicipal::officiels()->count();

        $stats = [
            'total_listes' => $totalListesOfficielles > 0
                ? $totalListesOfficielles
                : ListeElectorale::valide()->count(),
            'total_candidats' => $totalCandidatsOfficiels > 0
                ? $totalCandidatsOfficiels
                : CandidatMunicipal::actif()->whereHas('liste', fn($q) => $q->valide())->count(),
            'communes_couvertes' => $totalListesOfficielles > 0
                ? ListeElectorale::officielles()->distinct('commune_code_insee')->count('commune_code_insee')
                : ListeElectorale::valide()->distinct('commune_code_insee')->count('commune_code_insee'),
            'total_listes_civicdash' => ListeElectorale::valide()->civicdash()->count(),
            'source' => $totalListesOfficielles > 0 ? 'officielles' : 'civicdash',
        ];

        // Résultats T1 (stats nationales pré-calculées)
        $statsResultatsT1 = null;
        $topCommunesT1 = [];
        if ($electionsPassees) {
            $statsNationales = StatsElectionMunicipale::national()->first();
            $statsResultatsT1 = $statsNationales?->data;

            $topCommunesT1 = ResultatMunicipal::with(['listes' => fn($q) => $q->where('elu', true)])
                ->where('tour', 1)
                ->whereHas('ville', fn($q) => $q->where('population', '>=', 30000))
                ->orderByDesc('taux_participation')
                ->limit(5)
                ->get()
                ->map(fn($r) => [
                    'code_commune' => $r->code_commune,
                    'nom_commune' => $r->nom_commune,
                    'code_departement' => $r->code_departement,
                    'taux_participation' => $r->taux_participation,
                    'statut_commune' => $r->statut_commune,
                    'statut_libelle' => $r->statut_libelle,
                    'liste_gagnante' => $r->listes->first()?->only([
                        'nom_liste', 'nuance_politique', 'tete_de_liste_nom',
                        'tete_de_liste_prenom', 'pourcentage_exprimes',
                    ]),
                ]);
        }

        // Dernières listes validées
        $dernieresListes = ListeElectorale::valide()
            ->with(['candidats' => fn($q) => $q->teteDeListe()])
            ->latest('validated_at')
            ->limit(6)
            ->get()
            ->map(fn($liste) => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'departement_code' => $liste->departement_code,
                'tete_de_liste' => $liste->candidats->first()?->nom_complet,
                'nuance_politique' => $liste->nuance_politique,
                'couleur' => $liste->couleur_principale,
                'logo_url' => $liste->logo_url,
            ]);

        $etapesCandidature = $this->getEtapesCandidature();

        return Inertia::render('Elections/Municipales/Index', [
            'stats' => $stats,
            'stats_resultats_t1' => $statsResultatsT1,
            'top_communes_t1' => $topCommunesT1,
            'elections_passees' => $electionsPassees,
            'dernieres_listes' => $dernieresListes,
            'dates_election' => $datesElection,
            'etapes_candidature' => $etapesCandidature,
        ]);
    }

    /**
     * Recherche de listes par commune
     */
    public function recherche(Request $request)
    {
        $query = $request->input('q', '');
        $departement = $request->input('departement');
        
        $listesQuery = ListeElectorale::valide()
            ->with(['candidats' => fn($q) => $q->teteDeListe()]);

        if ($query) {
            $listesQuery->where(function($q) use ($query) {
                $q->where('commune_nom', 'ilike', "%{$query}%")
                  ->orWhere('nom_liste', 'ilike', "%{$query}%");
            });
        }

        if ($departement) {
            $listesQuery->where('departement_code', $departement);
        }

        $listes = $listesQuery
            ->orderBy('commune_nom')
            ->paginate(20)
            ->through(fn($liste) => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'commune_code_insee' => $liste->commune_code_insee,
                'departement_code' => $liste->departement_code,
                'tete_de_liste' => $liste->candidats->first()?->nom_complet,
                'nuance_politique' => $liste->nuance_politique,
                'couleur' => $liste->couleur_principale,
                'nombre_candidats' => $liste->nombre_candidats,
            ]);

        return Inertia::render('Elections/Municipales/Recherche', [
            'listes' => $listes,
            'filters' => [
                'q' => $query,
                'departement' => $departement,
            ],
        ]);
    }

    /**
     * Afficher une liste électorale
     */
    public function showListe(string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->valide()
            ->with([
                'candidats' => fn($q) => $q->actif()->orderBy('position'),
                'documents' => fn($q) => $q->valide()->where('type', 'programme_pdf'),
            ])
            ->firstOrFail();

        // Autres listes dans la même commune
        $autresListes = ListeElectorale::valide()
            ->where('commune_code_insee', $liste->commune_code_insee)
            ->where('id', '!=', $liste->id)
            ->with(['candidats' => fn($q) => $q->teteDeListe()])
            ->get()
            ->map(fn($l) => [
                'uuid' => $l->uuid,
                'nom_liste' => $l->nom_liste,
                'tete_de_liste' => $l->candidats->first()?->nom_complet,
                'nuance_politique' => $l->nuance_politique,
                'couleur' => $l->couleur_principale,
            ]);

        return Inertia::render('Elections/Municipales/ShowListe', [
            'liste' => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'commune_code_insee' => $liste->commune_code_insee,
                'departement_code' => $liste->departement_code,
                'nuance_politique' => $liste->nuance_politique,
                'parti_principal' => $liste->parti_principal,
                'slogan' => $liste->slogan,
                'description' => $liste->description,
                'couleur' => $liste->couleur_principale,
                'logo_url' => $liste->logo_url,
                'programme_pdf_url' => $liste->programme_pdf_url,
                'resume_programme' => $liste->resume_programme,
                'reseaux_sociaux' => $liste->reseaux_sociaux,
                'site_web' => $liste->site_web,
                'email_contact' => $liste->email_contact,
                'candidats' => $liste->candidats->map(fn($c) => [
                    'uuid' => $c->uuid,
                    'nom_complet' => $c->nom_complet,
                    'position' => $c->position,
                    'est_tete_de_liste' => $c->est_tete_de_liste,
                    'fonction_visee' => $c->fonction_label,
                    'profession' => $c->profession,
                    'photo_url' => $c->photo_url,
                    'initiales' => $c->initiales,
                    'biographie' => $c->biographie,
                    'reseaux_sociaux' => $c->reseaux_sociaux,
                ]),
            ],
            'autres_listes' => $autresListes,
        ]);
    }

    /**
     * Afficher un candidat
     */
    public function showCandidat(string $uuid)
    {
        $candidat = CandidatMunicipal::where('uuid', $uuid)
            ->actif()
            ->whereHas('liste', fn($q) => $q->valide())
            ->with(['liste'])
            ->firstOrFail();

        return Inertia::render('Elections/Municipales/ShowCandidat', [
            'candidat' => [
                'uuid' => $candidat->uuid,
                'civilite' => $candidat->civilite,
                'nom' => $candidat->nom,
                'prenom' => $candidat->prenom,
                'nom_complet' => $candidat->nom_complet,
                'age' => $candidat->age,
                'profession' => $candidat->profession,
                'biographie' => $candidat->biographie,
                'parcours' => $candidat->parcours,
                'engagements' => $candidat->engagements,
                'photo_url' => $candidat->photo_url,
                'initiales' => $candidat->initiales,
                'position' => $candidat->position,
                'est_tete_de_liste' => $candidat->est_tete_de_liste,
                'fonction_visee' => $candidat->fonction_label,
                'reseaux_sociaux' => $candidat->reseaux_sociaux,
            ],
            'liste' => [
                'uuid' => $candidat->liste->uuid,
                'nom_liste' => $candidat->liste->nom_liste,
                'commune_nom' => $candidat->liste->commune_nom,
                'couleur' => $candidat->liste->couleur_principale,
            ],
        ]);
    }

    // =========================================================================
    // TUTORIEL CANDIDATURE
    // =========================================================================

    /**
     * Page tutoriel candidature
     */
    public function tutoriel()
    {
        return Inertia::render('Elections/Municipales/Tutoriel', [
            'etapes' => $this->getEtapesCandidature(),
            'documents_requis' => $this->getDocumentsRequis(),
            'conditions_eligibilite' => $this->getConditionsEligibilite(),
            'dates_cles' => [
                'limite_depot' => '27 février 2026',
                'premier_tour' => '15 mars 2026',
                'second_tour' => '22 mars 2026',
            ],
        ]);
    }

    // =========================================================================
    // RÉSULTATS ÉLECTORAUX
    // =========================================================================

    public function resultats(Request $request)
    {
        $statsNationales = StatsElectionMunicipale::national()->first();

        $statsData = $statsNationales?->data;

        if (!$statsData) {
            $totalCommunes = ResultatMunicipal::where('tour', 1)->count();
            $eluesT1 = ResultatMunicipal::where('tour', 1)->where('statut_commune', 'elu_t1')->count();
            $secondTour = ResultatMunicipal::where('tour', 1)->where('statut_commune', 'second_tour')->count();
            $tauxMoyen = ResultatMunicipal::where('tour', 1)->avg('taux_participation');

            if ($totalCommunes > 0) {
                $statsData = [
                    'communes' => [
                        'total' => $totalCommunes,
                        'elues_t1' => $eluesT1,
                        'second_tour' => $secondTour,
                    ],
                    'participation' => [
                        't1' => ['taux' => $tauxMoyen ? round($tauxMoyen, 2) : null],
                    ],
                ];
            }
        }

        $topCommunes = ResultatMunicipal::with('listes')
            ->where('tour', 1)
            ->whereHas('ville', fn($q) => $q->where('population', '>=', 30000))
            ->orderByDesc('taux_participation')
            ->limit(20)
            ->get();

        if ($topCommunes->isEmpty()) {
            $topCommunes = ResultatMunicipal::with('listes')
                ->where('tour', 1)
                ->orderByDesc('votants')
                ->limit(20)
                ->get();
        }

        $topCommunesMapped = $topCommunes->map(function ($r) {
            $gagnante = $r->listes->where('elu', true)->first();
            return [
                'code_commune' => $r->code_commune,
                'nom_commune' => $r->nom_commune,
                'code_departement' => $r->code_departement,
                'taux_participation' => $r->taux_participation !== null ? (float) $r->taux_participation : null,
                'statut_commune' => $r->statut_commune,
                'statut_libelle' => $r->statut_libelle,
                'liste_gagnante' => $gagnante ? [
                    'nom_liste' => $gagnante->nom_liste,
                    'nuance_politique' => $gagnante->nuance_politique,
                    'tete_de_liste_nom' => $gagnante->tete_de_liste_nom,
                    'tete_de_liste_prenom' => $gagnante->tete_de_liste_prenom,
                    'pourcentage_exprimes' => $gagnante->pourcentage_exprimes !== null ? (float) $gagnante->pourcentage_exprimes : null,
                ] : null,
            ];
        });

        $departements = StatsElectionMunicipale::where('scope', 'departement')
            ->where('annee', 2026)
            ->get()
            ->keyBy('scope_code')
            ->map(fn($s) => $s->data);

        return Inertia::render('Elections/Municipales/Resultats', [
            'stats_nationales' => $statsData,
            'top_communes' => $topCommunesMapped,
            'stats_departements' => $departements,
        ]);
    }

    public function resultatCommune(string $codeInsee)
    {
        $ville = Ville::where('code_insee', $codeInsee)->first();

        if ($ville) {
            return redirect()->route('villes.show', $ville->slug);
        }

        abort(404, 'Commune introuvable.');
    }

    public function resultatDepartement(string $codeDept)
    {
        $stats = StatsElectionMunicipale::departement($codeDept)->first();

        $resultats = ResultatMunicipal::where('code_departement', $codeDept)
            ->where('tour', 1)
            ->with(['listes' => fn($q) => $q->where('elu', true)])
            ->orderBy('nom_commune')
            ->get()
            ->map(fn($r) => [
                'code_commune' => $r->code_commune,
                'nom_commune' => $r->nom_commune,
                'taux_participation' => $r->taux_participation,
                'statut_commune' => $r->statut_commune,
                'statut_libelle' => $r->statut_libelle,
                'liste_gagnante' => $r->listes->first()?->only([
                    'nom_liste', 'nuance_politique', 'tete_de_liste_nom',
                    'tete_de_liste_prenom', 'pourcentage_exprimes',
                ]),
            ]);

        return Inertia::render('Elections/Municipales/ResultatDepartement', [
            'code_departement' => $codeDept,
            'stats' => $stats?->data,
            'resultats' => $resultats,
        ]);
    }

    public function transitionMaires()
    {
        $stats = StatsElectionMunicipale::national()->first();

        $grandesVilles = Maire::where('mandature', '2026-2032')
            ->where('en_exercice', true)
            ->where('population_commune', '>=', 10000)
            ->with('predecesseur')
            ->orderByDesc('population_commune')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'nom_complet' => $m->nom_complet,
                'commune' => $m->nom_commune,
                'code_commune' => $m->code_commune,
                'population' => $m->population_commune,
                'nuance' => $m->nuance_politique,
                'nuance_libelle' => $m->nuance_libelle,
                'reelu' => $m->reelu,
                'score' => $m->score_election_pct,
                'tour' => $m->tour_election,
                'photo' => $m->photo,
                'url' => route('elus.public-profile', ['type' => 'maire', 'ref' => $m->id]),
                'predecesseur' => $m->predecesseur ? [
                    'id' => $m->predecesseur->id,
                    'nom_complet' => $m->predecesseur->nom_complet,
                    'nuance' => $m->predecesseur->nuance_politique,
                    'nuance_libelle' => $m->predecesseur->nuance_libelle,
                    'photo' => $m->predecesseur->photo,
                    'url' => route('elus.public-profile', ['type' => 'maire', 'ref' => $m->predecesseur->id]),
                ] : null,
            ]);

        return Inertia::render('Elections/Municipales/TransitionMaires', [
            'stats' => $stats?->data,
            'grandes_villes' => $grandesVilles,
        ]);
    }

    // =========================================================================
    // API JSON (pour composants dynamiques)
    // =========================================================================

    public function apiResultatsCommune(string $code, int $tour)
    {
        $resultat = ResultatMunicipal::where('code_commune', $code)
            ->where('tour', $tour)
            ->with('listes')
            ->firstOrFail();

        return response()->json($resultat);
    }

    public function apiStatsNuances()
    {
        $stats = StatsElectionMunicipale::national()->first();
        return response()->json($stats?->data['nuances'] ?? []);
    }

    public function apiTransitionMaire(string $codeInsee)
    {
        $ancien = Maire::where('code_commune', $codeInsee)
            ->where('mandature', '2020-2026')->first();
        $nouveau = Maire::where('code_commune', $codeInsee)
            ->where('mandature', '2026-2032')
            ->where('en_exercice', true)->first();

        return response()->json([
            'ancien' => $ancien?->toApiArray(),
            'nouveau' => $nouveau?->toApiArray(),
            'reelu' => $nouveau?->reelu,
        ]);
    }

    // =========================================================================
    // ESPACE CANDIDAT
    // =========================================================================

    /**
     * Dashboard candidat
     */
    public function espaceCandidatIndex()
    {
        $user = Auth::user();

        // Listes créées par l'utilisateur
        $mesListes = ListeElectorale::where('created_by', $user->id)
            ->with(['candidats', 'documents'])
            ->latest()
            ->get()
            ->map(fn($liste) => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'statut' => $liste->statut,
                'statut_formate' => $liste->statut_formate,
                'statut_couleur' => $liste->statut_couleur,
                'nombre_candidats' => $liste->candidats->count(),
                'documents_valides' => $liste->documents->where('statut_verification', 'valide')->count(),
                'documents_en_attente' => $liste->documents->where('statut_verification', 'en_attente')->count(),
                'peut_etre_modifiee' => $liste->peut_etre_modifiee,
                'created_at' => $liste->created_at->format('d/m/Y'),
            ]);

        // Candidature personnelle (si le user est candidat quelque part)
        $maCandidature = CandidatMunicipal::where('user_id', $user->id)
            ->with(['liste'])
            ->first();

        return Inertia::render('Elections/Municipales/EspaceCandidat/Index', [
            'mes_listes' => $mesListes,
            'ma_candidature' => $maCandidature ? [
                'uuid' => $maCandidature->uuid,
                'nom_complet' => $maCandidature->nom_complet,
                'position' => $maCandidature->position,
                'liste_nom' => $maCandidature->liste->nom_liste,
                'liste_uuid' => $maCandidature->liste->uuid,
                'commune_nom' => $maCandidature->liste->commune_nom,
            ] : null,
        ]);
    }

    /**
     * Formulaire création liste
     */
    public function createListe()
    {
        return Inertia::render('Elections/Municipales/EspaceCandidat/CreateListe', [
            'nuances_politiques' => $this->getNuancesPolitiques(),
        ]);
    }

    /**
     * Enregistrer une nouvelle liste
     */
    public function storeListe(Request $request)
    {
        $validated = $request->validate([
            'commune_code_insee' => 'required|string|size:5',
            'commune_nom' => 'required|string|max:255',
            'departement_code' => 'required|string|max:3',
            'nom_liste' => 'required|string|max:255',
            'nuance_politique' => 'nullable|string|max:50',
            'parti_principal' => 'nullable|string|max:100',
            'slogan' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'couleur_principale' => 'nullable|string|max:7',
            'email_contact' => 'nullable|email|max:255',
            'telephone_contact' => 'nullable|string|max:20',
            'site_web' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'resume_programme' => 'nullable|string|max:500',
        ]);

        $liste = ListeElectorale::create([
            ...$validated,
            'created_by' => Auth::id(),
            'statut' => 'brouillon',
        ]);

        // Log création
        $liste->moderationLogs()->create([
            'action' => 'creation',
            'nouveau_statut' => 'brouillon',
            'moderator_id' => Auth::id(),
        ]);

        return redirect()
            ->route('elections.municipales.espace-candidat.edit-liste', $liste->uuid)
            ->with('success', 'Liste créée avec succès !');
    }

    /**
     * Formulaire édition liste
     */
    public function editListe(string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->with(['candidats', 'documents', 'moderationLogs.moderator'])
            ->firstOrFail();

        if (!$liste->peut_etre_modifiee) {
            return redirect()
                ->route('elections.municipales.espace-candidat.index')
                ->with('error', 'Cette liste ne peut plus être modifiée.');
        }

        return Inertia::render('Elections/Municipales/EspaceCandidat/EditListe', [
            'liste' => [
                'uuid' => $liste->uuid,
                'commune_code_insee' => $liste->commune_code_insee,
                'commune_nom' => $liste->commune_nom,
                'departement_code' => $liste->departement_code,
                'nom_liste' => $liste->nom_liste,
                'nuance_politique' => $liste->nuance_politique,
                'parti_principal' => $liste->parti_principal,
                'slogan' => $liste->slogan,
                'description' => $liste->description,
                'couleur_principale' => $liste->couleur_principale,
                'email_contact' => $liste->email_contact,
                'telephone_contact' => $liste->telephone_contact,
                'site_web' => $liste->site_web,
                'facebook_url' => $liste->facebook_url,
                'twitter_url' => $liste->twitter_url,
                'instagram_url' => $liste->instagram_url,
                'youtube_url' => $liste->youtube_url,
                'tiktok_url' => $liste->tiktok_url,
                'resume_programme' => $liste->resume_programme,
                'logo_url' => $liste->logo_url,
                'programme_pdf_url' => $liste->programme_pdf_url,
                'statut' => $liste->statut,
                'statut_formate' => $liste->statut_formate,
                'motif_rejet' => $liste->motif_rejet,
            ],
            'candidats' => $liste->candidats->map(fn($c) => [
                'uuid' => $c->uuid,
                'nom' => $c->nom,
                'prenom' => $c->prenom,
                'position' => $c->position,
                'est_tete_de_liste' => $c->est_tete_de_liste,
                'photo_url' => $c->photo_url,
                'initiales' => $c->initiales,
            ]),
            'documents' => $liste->documents->map(fn($d) => [
                'uuid' => $d->uuid,
                'type' => $d->type,
                'type_formate' => $d->type_formate,
                'nom_fichier' => $d->nom_fichier,
                'taille_formatee' => $d->taille_formatee,
                'statut' => $d->statut_verification,
                'statut_formate' => $d->statut_formate,
                'statut_couleur' => $d->statut_couleur,
                'commentaire' => $d->commentaire_verification,
            ]),
            'historique' => $liste->moderationLogs->map(fn($log) => [
                'action' => $log->action_formatee,
                'icone' => $log->action_icone,
                'couleur' => $log->action_couleur,
                'commentaire' => $log->commentaire,
                'date' => $log->created_at->format('d/m/Y H:i'),
                'moderateur' => $log->moderator?->name,
            ]),
            'nuances_politiques' => $this->getNuancesPolitiques(),
        ]);
    }

    /**
     * Mettre à jour une liste
     */
    public function updateListe(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        if (!$liste->peut_etre_modifiee) {
            return back()->with('error', 'Cette liste ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'nom_liste' => 'required|string|max:255',
            'nuance_politique' => 'nullable|string|max:50',
            'parti_principal' => 'nullable|string|max:100',
            'slogan' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'couleur_principale' => 'nullable|string|max:7',
            'email_contact' => 'nullable|email|max:255',
            'telephone_contact' => 'nullable|string|max:20',
            'site_web' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'resume_programme' => 'nullable|string|max:500',
        ]);

        $liste->update($validated);

        return back()->with('success', 'Liste mise à jour !');
    }

    /**
     * Upload logo liste
     */
    public function uploadLogo(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Supprimer ancien logo
        if ($liste->logo_path) {
            Storage::disk('public')->delete($liste->logo_path);
        }

        $path = $request->file('logo')->store(
            "elections/municipales/{$liste->uuid}/logo",
            'public'
        );

        $liste->update(['logo_path' => $path]);

        return back()->with('success', 'Logo mis à jour !');
    }

    /**
     * Upload programme PDF
     */
    public function uploadProgramme(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $request->validate([
            'programme' => 'required|file|mimes:pdf|max:10240', // 10 Mo max
        ]);

        // Supprimer ancien programme
        if ($liste->programme_pdf_path) {
            Storage::disk('public')->delete($liste->programme_pdf_path);
        }

        $path = $request->file('programme')->store(
            "elections/municipales/{$liste->uuid}/programme",
            'public'
        );

        $liste->update(['programme_pdf_path' => $path]);

        return back()->with('success', 'Programme mis à jour !');
    }

    /**
     * Upload document justificatif
     */
    public function uploadDocument(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'type' => ['required', Rule::in([
                'recepisse_prefecture',
                'piece_identite',
                'attestation_eligibilite',
                'declaration_candidature',
                'photo_officielle',
                'autre',
            ])],
            'document' => 'required|file|mimes:pdf,jpeg,png,webp|max:5120', // 5 Mo max
            'description' => 'nullable|string|max:255',
            'numero_reference' => 'nullable|string|max:100',
            'date_document' => 'nullable|date',
        ]);

        $file = $request->file('document');
        $path = $file->store(
            "elections/municipales/{$liste->uuid}/documents",
            'public'
        );

        $liste->documents()->create([
            'type' => $validated['type'],
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin_fichier' => $path,
            'mime_type' => $file->getMimeType(),
            'taille_octets' => $file->getSize(),
            'description' => $validated['description'] ?? null,
            'numero_reference' => $validated['numero_reference'] ?? null,
            'date_document' => $validated['date_document'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Document ajouté !');
    }

    /**
     * Ajouter un candidat à la liste
     */
    public function storeCandidat(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        if (!$liste->peut_etre_modifiee) {
            return back()->with('error', 'La liste ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'civilite' => 'nullable|in:M.,Mme',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'nom_usage' => 'nullable|string|max:100',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'est_tete_de_liste' => 'boolean',
            'fonction_visee' => 'nullable|string|max:100',
            'biographie' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
        ]);

        // Calcul de la position
        $maxPosition = $liste->candidats()->max('position') ?? 0;
        
        // Si c'est tête de liste, on doit d'abord retirer le flag des autres
        if ($request->boolean('est_tete_de_liste')) {
            $liste->candidats()->update(['est_tete_de_liste' => false]);
            $validated['position'] = 1;
            
            // Décaler les autres
            $liste->candidats()->where('position', '>=', 1)->increment('position');
        } else {
            $validated['position'] = $maxPosition + 1;
        }

        $candidat = $liste->candidats()->create($validated);

        return back()->with('success', "Candidat {$candidat->nom_complet} ajouté !");
    }

    /**
     * Soumettre la liste pour validation
     */
    public function soumettreListe(string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        // Vérifications
        if ($liste->candidats()->count() === 0) {
            return back()->with('error', 'Ajoutez au moins un candidat avant de soumettre.');
        }

        if (!$liste->candidats()->where('est_tete_de_liste', true)->exists()) {
            return back()->with('error', 'Désignez une tête de liste avant de soumettre.');
        }

        // Vérifier qu'il y a un récépissé de préfecture
        $hasRecepisse = $liste->documents()
            ->where('type', 'recepisse_prefecture')
            ->exists();

        if (!$hasRecepisse) {
            return back()->with('error', 'Ajoutez le récépissé de dépôt en préfecture avant de soumettre.');
        }

        if (!$liste->soumettre()) {
            return back()->with('error', 'Impossible de soumettre cette liste.');
        }

        return back()->with('success', 'Liste soumise pour validation ! Vous serez notifié par email.');
    }

    // =========================================================================
    // MODÉRATION
    // =========================================================================

    /**
     * Liste des candidatures à modérer
     */
    public function moderationIndex()
    {
        $listesEnAttente = ListeElectorale::whereIn('statut', ['en_attente', 'en_verification'])
            ->with(['candidats', 'documents', 'createur'])
            ->orderBy('created_at')
            ->paginate(20)
            ->through(fn($liste) => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'departement_code' => $liste->departement_code,
                'statut' => $liste->statut,
                'statut_formate' => $liste->statut_formate,
                'nombre_candidats' => $liste->candidats->count(),
                'nombre_documents' => $liste->documents->count(),
                'documents_en_attente' => $liste->documents->where('statut_verification', 'en_attente')->count(),
                'createur' => $liste->createur?->name,
                'created_at' => $liste->created_at->format('d/m/Y H:i'),
            ]);

        $stats = [
            'en_attente' => ListeElectorale::where('statut', 'en_attente')->count(),
            'en_verification' => ListeElectorale::where('statut', 'en_verification')->count(),
            'valides_aujourdhui' => ListeElectorale::where('statut', 'valide')
                ->whereDate('validated_at', today())
                ->count(),
        ];

        return Inertia::render('Elections/Municipales/Moderation/Index', [
            'listes' => $listesEnAttente,
            'stats' => $stats,
        ]);
    }

    /**
     * Voir une liste pour modération
     */
    public function moderationShow(string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->with([
                'candidats',
                'documents.uploader',
                'createur',
                'moderationLogs.moderator',
            ])
            ->firstOrFail();

        return Inertia::render('Elections/Municipales/Moderation/Show', [
            'liste' => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'commune_code_insee' => $liste->commune_code_insee,
                'departement_code' => $liste->departement_code,
                'nuance_politique' => $liste->nuance_politique,
                'parti_principal' => $liste->parti_principal,
                'slogan' => $liste->slogan,
                'description' => $liste->description,
                'couleur_principale' => $liste->couleur_principale,
                'email_contact' => $liste->email_contact,
                'telephone_contact' => $liste->telephone_contact,
                'site_web' => $liste->site_web,
                'reseaux_sociaux' => $liste->reseaux_sociaux,
                'logo_url' => $liste->logo_url,
                'programme_pdf_url' => $liste->programme_pdf_url,
                'statut' => $liste->statut,
                'statut_formate' => $liste->statut_formate,
                'motif_rejet' => $liste->motif_rejet,
                'created_at' => $liste->created_at->format('d/m/Y H:i'),
            ],
            'createur' => $liste->createur ? [
                'id' => $liste->createur->id,
                'name' => $liste->createur->name,
                'email' => $liste->createur->email,
            ] : null,
            'candidats' => $liste->candidats->map(fn($c) => [
                'uuid' => $c->uuid,
                'nom_complet' => $c->nom_complet,
                'civilite' => $c->civilite,
                'date_naissance' => $c->date_naissance?->format('d/m/Y'),
                'age' => $c->age,
                'profession' => $c->profession,
                'position' => $c->position,
                'est_tete_de_liste' => $c->est_tete_de_liste,
                'fonction_visee' => $c->fonction_label,
                'photo_url' => $c->photo_url,
                'initiales' => $c->initiales,
                'est_eligible' => $c->est_eligible,
            ]),
            'documents' => $liste->documents->map(fn($d) => [
                'uuid' => $d->uuid,
                'type' => $d->type,
                'type_formate' => $d->type_formate,
                'nom_fichier' => $d->nom_fichier,
                'url' => $d->url,
                'taille_formatee' => $d->taille_formatee,
                'est_image' => $d->est_image,
                'est_pdf' => $d->est_pdf,
                'statut' => $d->statut_verification,
                'statut_formate' => $d->statut_formate,
                'statut_couleur' => $d->statut_couleur,
                'commentaire' => $d->commentaire_verification,
                'numero_reference' => $d->numero_reference,
                'date_document' => $d->date_document?->format('d/m/Y'),
                'uploader' => $d->uploader?->name,
                'uploaded_at' => $d->created_at->format('d/m/Y H:i'),
            ]),
            'historique' => $liste->moderationLogs()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($log) => [
                    'action' => $log->action_formatee,
                    'icone' => $log->action_icone,
                    'couleur' => $log->action_couleur,
                    'ancien_statut' => $log->ancien_statut,
                    'nouveau_statut' => $log->nouveau_statut,
                    'commentaire' => $log->commentaire,
                    'date' => $log->created_at->format('d/m/Y H:i'),
                    'moderateur' => $log->moderator?->name,
                ]),
        ]);
    }

    /**
     * Valider un document
     */
    public function validerDocument(Request $request, string $uuid)
    {
        $document = CandidatureDocument::where('uuid', $uuid)->firstOrFail();

        $document->valider(Auth::user(), $request->input('commentaire'));

        return back()->with('success', 'Document validé !');
    }

    /**
     * Invalider un document
     */
    public function invaliderDocument(Request $request, string $uuid)
    {
        $document = CandidatureDocument::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'raison' => 'required|string|max:500',
        ]);

        $document->invalider(Auth::user(), $validated['raison']);

        return back()->with('success', 'Document marqué comme invalide.');
    }

    /**
     * Valider une liste
     */
    public function validerListe(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)->firstOrFail();

        $liste->valider(Auth::user(), $request->input('commentaire'));

        // TODO: Envoyer email de confirmation

        return redirect()
            ->route('elections.municipales.moderation.index')
            ->with('success', "Liste \"{$liste->nom_liste}\" validée !");
    }

    /**
     * Rejeter une liste
     */
    public function rejeterListe(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'motif' => 'required|string|max:1000',
        ]);

        $liste->rejeter(Auth::user(), $validated['motif']);

        // TODO: Envoyer email avec motif de rejet

        return redirect()
            ->route('elections.municipales.moderation.index')
            ->with('success', 'Liste rejetée.');
    }

    /**
     * Demander des documents complémentaires
     */
    public function demanderDocuments(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'commentaire' => 'required|string|max:1000',
        ]);

        $liste->demanderDocuments(Auth::user(), $validated['commentaire']);

        // TODO: Envoyer email avec demande

        return back()->with('success', 'Demande de documents envoyée.');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function getEtapesCandidature(): array
    {
        return [
            [
                'numero' => 1,
                'titre' => 'Vérifier votre éligibilité',
                'description' => 'Vous devez être citoyen français ou de l\'Union européenne, avoir 18 ans révolus, être inscrit sur les listes électorales ou justifier devoir y être inscrit.',
                'icone' => '✅',
                'duree' => '5 min',
            ],
            [
                'numero' => 2,
                'titre' => 'Constituer votre liste',
                'description' => 'Réunissez vos colistiers. Le nombre de candidats dépend de la taille de votre commune (de 11 à 69 candidats). La liste doit respecter la parité homme/femme.',
                'icone' => '👥',
                'duree' => 'Variable',
            ],
            [
                'numero' => 3,
                'titre' => 'Remplir les formulaires officiels',
                'description' => 'Téléchargez et remplissez le Cerfa n°14996*03 (déclaration de candidature) pour chaque candidat de la liste.',
                'icone' => '📝',
                'duree' => '2-3h',
                'lien' => 'https://www.service-public.fr/particuliers/vosdroits/R52044',
            ],
            [
                'numero' => 4,
                'titre' => 'Déposer la candidature en préfecture',
                'description' => 'Déposez l\'ensemble des documents en préfecture ou sous-préfecture. Vous recevrez un récépissé de dépôt.',
                'icone' => '🏛️',
                'duree' => '1-2h',
                'date_limite' => '27 février 2026 à 18h',
            ],
            [
                'numero' => 5,
                'titre' => 'Créer votre profil sur CivicDash',
                'description' => 'Inscrivez votre liste sur CivicDash pour gagner en visibilité. Uploadez votre récépissé de dépôt pour validation.',
                'icone' => '🚀',
                'duree' => '30 min',
            ],
            [
                'numero' => 6,
                'titre' => 'Faire campagne',
                'description' => 'La campagne officielle débute le 2 mars 2026. Partagez votre programme, rencontrez les électeurs, participez aux débats.',
                'icone' => '📢',
                'periode' => '2-14 mars 2026',
            ],
        ];
    }

    private function getDocumentsRequis(): array
    {
        return [
            [
                'nom' => 'Déclaration de candidature (Cerfa n°14996*03)',
                'description' => 'Un formulaire par candidat, signé',
                'obligatoire' => true,
            ],
            [
                'nom' => 'Pièce d\'identité',
                'description' => 'CNI ou passeport en cours de validité',
                'obligatoire' => true,
            ],
            [
                'nom' => 'Attestation d\'inscription sur liste électorale',
                'description' => 'Ou justificatif d\'éligibilité à l\'inscription',
                'obligatoire' => true,
            ],
            [
                'nom' => 'Récépissé de dépôt en préfecture',
                'description' => 'Délivré lors du dépôt officiel',
                'obligatoire' => true,
                'pour_civicdash' => true,
            ],
        ];
    }

    private function getConditionsEligibilite(): array
    {
        return [
            'Avoir la nationalité française ou être citoyen de l\'Union européenne',
            'Avoir 18 ans révolus au jour du premier tour (15 mars 2026)',
            'Être inscrit sur les listes électorales de la commune ou justifier devoir y être inscrit',
            'Ne pas être privé du droit de vote ou d\'éligibilité par décision de justice',
            'Ne pas être placé sous tutelle ou curatelle',
        ];
    }

    public static function nuancesPolitiques(): array
    {
        return [
            ['code' => 'EXG', 'label' => 'Extrême gauche'],
            ['code' => 'COM', 'label' => 'Parti communiste'],
            ['code' => 'FI', 'label' => 'La France Insoumise'],
            ['code' => 'SOC', 'label' => 'Parti socialiste'],
            ['code' => 'ECO', 'label' => 'Écologistes'],
            ['code' => 'DVG', 'label' => 'Divers gauche'],
            ['code' => 'RDG', 'label' => 'Radicaux de gauche'],
            ['code' => 'MDM', 'label' => 'Modem'],
            ['code' => 'REN', 'label' => 'Renaissance'],
            ['code' => 'HOR', 'label' => 'Horizons'],
            ['code' => 'UDI', 'label' => 'UDI'],
            ['code' => 'LR', 'label' => 'Les Républicains'],
            ['code' => 'DVD', 'label' => 'Divers droite'],
            ['code' => 'RN', 'label' => 'Rassemblement National'],
            ['code' => 'REC', 'label' => 'Reconquête'],
            ['code' => 'EXD', 'label' => 'Extrême droite'],
            ['code' => 'REG', 'label' => 'Régionaliste'],
            ['code' => 'DIV', 'label' => 'Divers'],
            ['code' => 'SE', 'label' => 'Sans étiquette'],
        ];
    }
}
