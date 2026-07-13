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
