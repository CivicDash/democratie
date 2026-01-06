<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EluFollower;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ElusSuivisController extends Controller
{
    /**
     * Afficher la liste des élus suivis par l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        $followedElus = EluFollower::where('user_id', $user->id)
            ->orderBy('followed_at', 'desc')
            ->get()
            ->map(function ($follow) {
                return [
                    'id' => $follow->id,
                    'elu_type' => $follow->elu_type,
                    'elu_id' => $follow->elu_id,
                    'elu_nom' => $follow->elu_nom,
                    'elu_photo_url' => $follow->elu_photo_url,
                    'elu_groupe' => $follow->elu_groupe,
                    'elu_circonscription' => $follow->elu_circonscription,
                    'followed_at' => $follow->followed_at?->toISOString() ?? $follow->created_at?->toISOString(),
                    'notify_votes' => $follow->notify_votes,
                    'notify_interventions' => $follow->notify_interventions,
                    'notify_amendements' => $follow->notify_amendements,
                    'notify_propositions' => $follow->notify_propositions,
                    'notify_rapports' => $follow->notify_rapports,
                    'notify_commissions' => $follow->notify_commissions,
                    'notify_actualites' => $follow->notify_actualites,
                    'notify_site' => $follow->notify_site,
                    'notify_email' => $follow->notify_email,
                    'email_frequency' => $follow->email_frequency,
                ];
            });

        $stats = [
            'total' => $followedElus->count(),
            'deputes' => $followedElus->where('elu_type', 'depute')->count(),
            'senateurs' => $followedElus->where('elu_type', 'senateur')->count(),
            'maires' => $followedElus->where('elu_type', 'maire')->count(),
            'ministres' => $followedElus->where('elu_type', 'ministre')->count(),
        ];

        return Inertia::render('Profile/ElusSuivis', [
            'followedElus' => $followedElus,
            'stats' => $stats,
        ]);
    }
}
