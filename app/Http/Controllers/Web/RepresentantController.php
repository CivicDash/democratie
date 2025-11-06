<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeputeSenateur;
use App\Models\GroupeParlementaire;
use App\Models\Profile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RepresentantController extends Controller
{
    /**
     * Page "Mes Représentants"
     */
    public function mesRepresentants(): Response
    {
        $user = auth()->user();
        $profile = $user->profile;

        $data = [
            'hasLocation' => false,
            'depute' => null,
            'senateurs' => [],
            'location' => null,
        ];

        if ($profile && $profile->circonscription && $profile->department_id) {
            $data['hasLocation'] = true;
            $data['location'] = [
                'city' => $profile->city_name,
                'postal_code' => $profile->postal_code,
                'circonscription' => $profile->circonscription,
                'department' => $profile->department?->name,
            ];

            // Député de la circonscription
            $depute = DeputeSenateur::deputes()
                ->enExercice()
                ->where('circonscription', $profile->circonscription)
                ->with(['groupeParlementaire'])
                ->first();

            if ($depute) {
                $data['depute'] = [
                    'id' => $depute->id,
                    'nom_complet' => $depute->nom_complet,
                    'photo_url' => $depute->photo_url,
                    'groupe' => [
                        'nom' => $depute->groupe_politique,
                        'sigle' => $depute->groupe_sigle,
                        'couleur' => $depute->groupeParlementaire?->couleur_hex ?? '#6B7280',
                    ],
                    'circonscription' => $depute->circonscription,
                    'profession' => $depute->profession,
                    'nb_propositions' => $depute->nb_propositions,
                    'nb_amendements' => $depute->nb_amendements,
                    'taux_presence' => $depute->taux_presence,
                    'url_profil' => $depute->url_profil,
                ];
            }

            // Sénateurs du département
            $senateurs = DeputeSenateur::senateurs()
                ->enExercice()
                ->where('circonscription', 'like', substr($profile->circonscription, 0, 2) . '%')
                ->with(['groupeParlementaire'])
                ->get();

            $data['senateurs'] = $senateurs->map(fn($senateur) => [
                'id' => $senateur->id,
                'nom_complet' => $senateur->nom_complet,
                'photo_url' => $senateur->photo_url,
                'groupe' => [
                    'nom' => $senateur->groupe_politique,
                    'sigle' => $senateur->groupe_sigle,
                    'couleur' => $senateur->groupeParlementaire?->couleur_hex ?? '#6B7280',
                ],
                'profession' => $senateur->profession,
                'nb_propositions' => $senateur->nb_propositions,
                'nb_amendements' => $senateur->nb_amendements,
                'taux_presence' => $senateur->taux_presence,
                'url_profil' => $senateur->url_profil,
            ])->toArray();
        }

        return Inertia::render('Representants/MesRepresentants', $data);
    }

    /**
     * Liste complète des députés
     */
    public function deputes(Request $request): Response
    {
        $query = DeputeSenateur::deputes()
            ->enExercice()
            ->with(['groupeParlementaire']);

        // Filtres
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('groupe')) {
            $query->where('groupe_sigle', $request->groupe);
        }

        if ($request->filled('department')) {
            $query->where('circonscription', 'like', $request->department . '%');
        }

        // Tri
        $sortBy = $request->get('sort', 'nom');
        $sortOrder = $request->get('order', 'asc');

        switch ($sortBy) {
            case 'groupe':
                $query->orderBy('groupe_sigle', $sortOrder);
                break;
            case 'circonscription':
                $query->orderBy('circonscription', $sortOrder);
                break;
            default:
                $query->orderBy('nom', $sortOrder);
        }

        $deputes = $query->paginate(30)->withQueryString();
        
        // Récupérer les groupes parlementaires pour les filtres et l'hémicycle
        $groupes = GroupeParlementaire::where('source', 'assemblee')
            ->where('actif', true)
            ->get(['sigle', 'nom', 'couleur_hex'])
            ->map(fn($g) => [
                'sigle' => $g->sigle,
                'nom' => $g->nom,
                'couleur_hex' => $g->couleur_hex,
            ]);

        return Inertia::render('Representants/Deputes/Index', [
            'deputes' => $deputes,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'department', 'sort', 'order']),
        ]);
    }

    /**
     * Fiche détaillée d'un député
     */
    public function showDepute(DeputeSenateur $depute): Response
    {
        $depute->load(['groupeParlementaire']);

        return Inertia::render('Representants/Deputes/Show', [
            'depute' => [
                'id' => $depute->id,
                'nom_complet' => $depute->nom_complet,
                'civilite' => $depute->civilite,
                'prenom' => $depute->prenom,
                'nom' => $depute->nom,
                'photo_url' => $depute->photo_url,
                'age' => $depute->age,
                'profession' => $depute->profession,
                'circonscription' => $depute->circonscription,
                'groupe' => [
                    'id' => $depute->groupeParlementaire?->id,
                    'nom' => $depute->groupe_politique,
                    'sigle' => $depute->groupe_sigle,
                    'couleur' => $depute->groupeParlementaire?->couleur_hex ?? '#6B7280',
                    'position' => $depute->groupeParlementaire?->position_politique,
                ],
                'mandat' => [
                    'debut' => $depute->debut_mandat?->format('d/m/Y'),
                    'fin' => $depute->fin_mandat?->format('d/m/Y'),
                    'legislature' => $depute->legislature,
                ],
                'statistiques' => [
                    'nb_propositions' => $depute->nb_propositions,
                    'nb_amendements' => $depute->nb_amendements,
                    'taux_presence' => $depute->taux_presence,
                ],
                'fonctions' => $depute->fonctions,
                'commissions' => $depute->commissions,
                'url_profil' => $depute->url_profil,
            ],
        ]);
    }

    /**
     * Liste complète des sénateurs
     */
    public function senateurs(Request $request): Response
    {
        $query = DeputeSenateur::senateurs()
            ->enExercice()
            ->with(['groupeParlementaire']);

        // Filtres
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('groupe')) {
            $query->where('groupe_sigle', $request->groupe);
        }

        if ($request->filled('department')) {
            $query->where('circonscription', 'like', $request->department . '%');
        }

        // Tri
        $sortBy = $request->get('sort', 'nom');
        $sortOrder = $request->get('order', 'asc');

        switch ($sortBy) {
            case 'groupe':
                $query->orderBy('groupe_sigle', $sortOrder);
                break;
            case 'department':
                $query->orderBy('circonscription', $sortOrder);
                break;
            default:
                $query->orderBy('nom', $sortOrder);
        }

        $senateurs = $query->paginate(30)->withQueryString();

        return Inertia::render('Representants/Senateurs/Index', [
            'senateurs' => $senateurs,
            'filters' => $request->only(['search', 'groupe', 'department', 'sort', 'order']),
        ]);
    }

    /**
     * Fiche détaillée d'un sénateur
     */
    public function showSenateur(DeputeSenateur $senateur): Response
    {
        $senateur->load(['groupeParlementaire']);

        return Inertia::render('Representants/Senateurs/Show', [
            'senateur' => [
                'id' => $senateur->id,
                'nom_complet' => $senateur->nom_complet,
                'civilite' => $senateur->civilite,
                'prenom' => $senateur->prenom,
                'nom' => $senateur->nom,
                'photo_url' => $senateur->photo_url,
                'age' => $senateur->age,
                'profession' => $senateur->profession,
                'department' => substr($senateur->circonscription ?? '', 0, 2),
                'groupe' => [
                    'id' => $senateur->groupeParlementaire?->id,
                    'nom' => $senateur->groupe_politique,
                    'sigle' => $senateur->groupe_sigle,
                    'couleur' => $senateur->groupeParlementaire?->couleur_hex ?? '#6B7280',
                    'position' => $senateur->groupeParlementaire?->position_politique,
                ],
                'mandat' => [
                    'debut' => $senateur->debut_mandat?->format('d/m/Y'),
                    'fin' => $senateur->fin_mandat?->format('d/m/Y'),
                    'legislature' => $senateur->legislature,
                ],
                'statistiques' => [
                    'nb_propositions' => $senateur->nb_propositions,
                    'nb_amendements' => $senateur->nb_amendements,
                    'taux_presence' => $senateur->taux_presence,
                ],
                'fonctions' => $senateur->fonctions,
                'commissions' => $senateur->commissions,
                'url_profil' => $senateur->url_profil,
            ],
        ]);
    }
}

