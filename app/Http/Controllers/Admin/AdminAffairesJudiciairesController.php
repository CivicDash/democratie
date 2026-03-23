<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AjouterSourceRequest;
use App\Http\Requests\ValiderAffaireRequest;
use App\Models\AffaireJudiciaire;
use App\Models\AffaireSource;
use App\Models\StatsAffaireJudiciaire;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAffairesJudiciairesController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', 'detecte');
        $search = $request->input('search');

        $query = AffaireJudiciaire::where('statut_validation', $tab)
            ->with(['sources', 'acteurAN', 'senateur', 'personnePolitique']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ILIKE', "%{$search}%")
                  ->orWhere('prenom', 'ILIKE', "%{$search}%")
                  ->orWhere('titre', 'ILIKE', "%{$search}%");
            });
        }

        $affaires = $query->orderByDesc('detection_confidence')
            ->orderByDesc('detecte_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'detecte' => AffaireJudiciaire::enAttente()->count(),
            'en_review' => AffaireJudiciaire::enReview()->count(),
            'a_completer' => AffaireJudiciaire::aCompleter()->count(),
            'valide' => AffaireJudiciaire::where('statut_validation', 'valide')->count(),
            'rejete' => AffaireJudiciaire::where('statut_validation', 'rejete')->count(),
            'conteste' => AffaireJudiciaire::where('statut_validation', 'conteste')->count(),
        ];

        $healthMetrics = [
            'detectees_non_reviewees' => AffaireJudiciaire::enAttente()
                ->where('detecte_at', '<', now()->subDays(7))->count(),
            'contestees_non_traitees' => AffaireJudiciaire::where('statut_validation', 'conteste')
                ->where('updated_at', '<', now()->subHours(72))->count(),
            'sans_source_haute' => AffaireJudiciaire::publiques()
                ->whereDoesntHave('sources', fn ($q) => $q->where('fiabilite', 'haute'))
                ->count(),
        ];

        return Inertia::render('Admin/AffairesJudiciaires/Index', [
            'affaires' => $affaires,
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'health_metrics' => $healthMetrics,
            'types_affaire' => AffaireJudiciaire::TYPES_AFFAIRE(),
            'categories' => AffaireJudiciaire::CATEGORIES(),
            'statuts_judiciaires' => AffaireJudiciaire::STATUTS_JUDICIAIRES(),
        ]);
    }

    public function show(AffaireJudiciaire $affaire): Response
    {
        $affaire->load([
            'sources',
            'moderationLogs.moderator',
            'personnePolitique',
            'acteurAN',
            'senateur',
            'validateur',
        ]);

        return Inertia::render('Admin/AffairesJudiciaires/Show', [
            'affaire' => $affaire,
            'types_affaire' => AffaireJudiciaire::TYPES_AFFAIRE(),
            'categories' => AffaireJudiciaire::CATEGORIES(),
            'statuts_judiciaires' => AffaireJudiciaire::STATUTS_JUDICIAIRES(),
            'statuts_validation' => AffaireJudiciaire::STATUTS_VALIDATION(),
            'types_source' => AffaireSource::TYPES_SOURCE(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AffairesJudiciaires/Show', [
            'affaire' => null,
            'types_affaire' => AffaireJudiciaire::TYPES_AFFAIRE(),
            'categories' => AffaireJudiciaire::CATEGORIES(),
            'statuts_judiciaires' => AffaireJudiciaire::STATUTS_JUDICIAIRES(),
            'statuts_validation' => AffaireJudiciaire::STATUTS_VALIDATION(),
            'types_source' => AffaireSource::TYPES_SOURCE(),
        ]);
    }

    public function store(ValiderAffaireRequest $request)
    {
        $data = $request->validated();
        $sources = $data['sources'] ?? [];
        unset($data['sources']);

        $data['source_detection'] = 'manuel';
        $data['detecte_at'] = now();
        $data['detection_confidence'] = 1.00;
        $data['statut_validation'] = 'en_review';

        $affaire = AffaireJudiciaire::create($data);

        foreach ($sources as $sourceData) {
            $affaire->sources()->create($sourceData);
        }

        $affaire->moderationLogs()->create([
            'action' => 'detection',
            'nouveau_statut' => 'en_review',
            'commentaire' => 'Saisie manuelle par un administrateur',
            'moderator_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.affaires.show', $affaire)
            ->with('success', 'Affaire créée avec succès.');
    }

    public function prendreEnCharge(AffaireJudiciaire $affaire)
    {
        $affaire->prendreEnCharge(request()->user());

        return back()->with('success', 'Affaire prise en charge.');
    }

    public function valider(ValiderAffaireRequest $request, AffaireJudiciaire $affaire)
    {
        $data = $request->validated();
        $sources = $data['sources'] ?? [];
        unset($data['sources']);

        $affaire->update($data);

        $affaire->sources()->delete();
        foreach ($sources as $sourceData) {
            $sourceData['verifie_par'] = $request->user()->id;
            $sourceData['verifie_at'] = now();
            $affaire->sources()->create($sourceData);
        }

        $affaire->valider($request->user(), $data['commentaire_validation'] ?? null);

        return redirect()->route('admin.affaires.show', $affaire)
            ->with('success', 'Affaire validée et publiée.');
    }

    public function rejeter(Request $request, AffaireJudiciaire $affaire)
    {
        $request->validate(['motif' => 'required|string|max:2000']);

        $affaire->rejeter($request->user(), $request->input('motif'));

        return back()->with('success', 'Affaire rejetée.');
    }

    public function completer(Request $request, AffaireJudiciaire $affaire)
    {
        $request->validate(['commentaire' => 'required|string|max:2000']);

        $affaire->demanderComplement($request->user(), $request->input('commentaire'));

        return back()->with('success', 'Demande de complément envoyée.');
    }

    public function archiver(Request $request, AffaireJudiciaire $affaire)
    {
        $request->validate(['motif' => 'required|string|max:2000']);

        $affaire->archiver($request->user(), $request->input('motif'));

        return back()->with('success', 'Affaire archivée.');
    }

    public function ajouterSource(AjouterSourceRequest $request, AffaireJudiciaire $affaire)
    {
        $data = $request->validated();
        $data['verifie_par'] = $request->user()->id;
        $data['verifie_at'] = now();

        $affaire->sources()->create($data);

        $affaire->moderationLogs()->create([
            'action' => 'mise_a_jour',
            'commentaire' => 'Source ajoutée : ' . ($data['media'] ?? $data['url']),
            'moderator_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Source ajoutée.');
    }

    public function supprimerSource(AffaireSource $source)
    {
        $affaire = $source->affaire;
        $source->delete();

        $affaire->moderationLogs()->create([
            'action' => 'mise_a_jour',
            'commentaire' => 'Source supprimée',
            'moderator_id' => request()->user()->id,
        ]);

        return back()->with('success', 'Source supprimée.');
    }

    public function stats(): Response
    {
        $global = StatsAffaireJudiciaire::global()->first();

        $healthMetrics = [
            'total_detectees' => AffaireJudiciaire::count(),
            'total_validees' => AffaireJudiciaire::publiques()->count(),
            'total_rejetees' => AffaireJudiciaire::where('statut_validation', 'rejete')->count(),
            'en_attente' => AffaireJudiciaire::enAttente()->count(),
            'taux_rejet' => AffaireJudiciaire::count() > 0
                ? round((AffaireJudiciaire::where('statut_validation', 'rejete')->count() / AffaireJudiciaire::count()) * 100, 1)
                : 0,
            'delai_moyen_validation' => AffaireJudiciaire::publiques()
                ->whereNotNull('detecte_at')
                ->whereNotNull('valide_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (valide_at - detecte_at)) / 3600) as avg_hours')
                ->value('avg_hours'),
            'par_source' => AffaireJudiciaire::whereNotNull('source_detection')
                ->selectRaw('source_detection, COUNT(*) as total')
                ->groupBy('source_detection')
                ->pluck('total', 'source_detection'),
        ];

        return Inertia::render('Admin/AffairesJudiciaires/Stats', [
            'stats_global' => $global?->data,
            'health_metrics' => $healthMetrics,
        ]);
    }
}
