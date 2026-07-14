<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\ModerationException;
use App\Http\Controllers\Controller;
use App\Models\Argument;
use App\Models\CandidatPresidentielle;
use App\Models\IngestionProposition;
use App\Models\MesureScrutinLien;
use App\Models\ProgrammeMesure;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\ModerationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Back-office de modération présidentielle (plan §5).
 * Accès : permission `moderer_presidentielle` (gate spatie).
 */
class PresidentielleModerationController extends Controller
{
    /** Types d'entités pilotables par le workflow statut_validation. */
    private const MODELS = [
        'candidat' => CandidatPresidentielle::class,
        'mesure' => ProgrammeMesure::class,
        'argument' => Argument::class,
        'lien' => MesureScrutinLien::class,
    ];

    /** File de modération : compteurs par statut + propositions en attente. */
    public function index(IntegriteChecker $integrite)
    {
        $parStatut = fn (string $model) => $model::query()
            ->selectRaw('statut_validation, count(*) as n')
            ->groupBy('statut_validation')->pluck('n', 'statut_validation');

        return Inertia::render('Admin/Presidentielle/Moderation', [
            'files' => [
                'candidats' => $parStatut(CandidatPresidentielle::class),
                'mesures' => $parStatut(ProgrammeMesure::class),
                'arguments' => $parStatut(Argument::class),
            ],
            'propositions_en_attente' => IngestionProposition::enAttente()->count(),
            'integrite' => $integrite->analyser('2027'),
        ]);
    }

    /** File d'ingestion : propositions en attente de validation. */
    public function propositions(Request $request)
    {
        $propositions = IngestionProposition::with(['candidat.personnePolitique', 'theme', 'document'])
            ->when($request->query('statut', 'detecte') !== 'tous', fn ($q) => $q->where('statut', $request->query('statut', 'detecte')))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Presidentielle/Propositions', [
            'propositions' => $propositions,
            'statut' => $request->query('statut', 'detecte'),
        ]);
    }

    /** File des mesures par statut de validation. */
    public function mesures(Request $request)
    {
        $mesures = ProgrammeMesure::with(['candidat.personnePolitique', 'theme'])
            ->withCount(['arguments as pour_count' => fn ($q) => $q->where('sens', 'pour')->publie()])
            ->withCount(['arguments as contre_count' => fn ($q) => $q->where('sens', 'contre')->publie()])
            ->when($request->query('statut', 'detecte') !== 'tous', fn ($q) => $q->where('statut_validation', $request->query('statut', 'detecte')))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Presidentielle/Mesures', [
            'mesures' => $mesures,
            'statut' => $request->query('statut', 'detecte'),
        ]);
    }

    /** Gestion des médias (portrait + bannière) par candidat. */
    public function medias()
    {
        $candidats = CandidatPresidentielle::with('personnePolitique')
            ->orderBy('ordre_affichage')->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nom' => $c->personnePolitique?->nom_complet,
                'couleur_hex' => $c->couleur_hex,
                'photo_url' => $c->photo_url, 'photo_credit' => $c->photo_credit, 'photo_licence' => $c->photo_licence,
                'hero_banner_url' => $c->hero_banner_url, 'hero_credit' => $c->hero_credit, 'hero_licence' => $c->hero_licence,
            ]);

        return Inertia::render('Admin/Presidentielle/Medias', ['candidats' => $candidats]);
    }

    /** Enregistre les URLs d'images + crédits/licences (obligatoires si URL fournie). */
    public function updateMedias(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'photo_url' => ['nullable', 'url', 'max:500'],
            'photo_credit' => ['nullable', 'string', 'max:255', 'required_with:photo_url'],
            'photo_licence' => ['nullable', 'string', 'max:120', 'required_with:photo_url'],
            'hero_banner_url' => ['nullable', 'url', 'max:500'],
            'hero_credit' => ['nullable', 'string', 'max:255', 'required_with:hero_banner_url'],
            'hero_licence' => ['nullable', 'string', 'max:120', 'required_with:hero_banner_url'],
        ], [
            'photo_credit.required_with' => 'Le crédit du portrait est obligatoire.',
            'photo_licence.required_with' => 'La licence du portrait est obligatoire.',
            'hero_credit.required_with' => 'Le crédit de la bannière est obligatoire.',
            'hero_licence.required_with' => 'La licence de la bannière est obligatoire.',
        ]);

        $candidat = CandidatPresidentielle::findOrFail($data['id']);
        $candidat->update(collect($data)->except('id')->all());

        return back()->with('success', 'Médias enregistrés.');
    }

    /** Valide (crée une mesure) ou rejette une proposition d'ingestion. */
    public function propositionAction(Request $request, ModerationService $service)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'action' => ['required', 'in:valider,rejeter'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        $proposition = IngestionProposition::findOrFail($data['id']);
        try {
            if ($data['action'] === 'valider') {
                $service->creerMesureDepuisProposition($proposition, $request->user());
            } else {
                $service->rejeterProposition($proposition, $request->user(), $data['commentaire'] ?? null);
            }
        } catch (ModerationException $e) {
            throw ValidationException::withMessages(['action' => $e->getMessage()]);
        }

        return back()->with('success', 'Proposition '.($data['action'] === 'valider' ? 'rattachée à une nouvelle mesure' : 'rejetée').'.');
    }

    /** Applique une action de modération à une entité. */
    public function action(Request $request, ModerationService $service)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(self::MODELS))],
            'id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:prendre_en_charge,demander_complement,valider,double_valider,publier,depublier'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        $model = self::MODELS[$data['type']];
        $entite = $model::findOrFail($data['id']);
        $user = $request->user();
        $commentaire = $data['commentaire'] ?? null;

        try {
            match ($data['action']) {
                'prendre_en_charge' => $service->prendreEnCharge($entite, $user),
                'demander_complement' => $service->demanderComplement($entite, $user, $commentaire ?? 'complément requis'),
                'valider' => $service->valider($entite, $user, $commentaire),
                'double_valider' => $service->doubleValider($entite, $user),
                'publier' => $service->publier($entite, $user),
                'depublier' => $service->depublier($entite, $user, $commentaire),
            };
        } catch (ModerationException $e) {
            throw ValidationException::withMessages(['action' => $e->getMessage()]);
        }

        return back()->with('success', 'Action « '.$data['action'].' » appliquée.');
    }
}
