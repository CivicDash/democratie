<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommuneAbonnement;
use App\Models\CommuneEvenement;
use App\Models\CommunePage;
use App\Models\DeputeCirconscription;
use App\Models\Senateur;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CommunePageController extends Controller
{
    public function index(string $codeInsee): Response
    {
        $page = $this->resolvePage($codeInsee);
        $ville = $page->ville;

        $data = Cache::remember("commune_hub:{$codeInsee}", 300, function () use ($page, $ville) {
            $maire = $ville->maireActuel;

            $articles = $page->actus_actives
                ? $page->articles()->publies()->recents()->limit(3)->get()->map(fn ($a) => [
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'slug' => $a->slug,
                    'extrait' => $a->extrait_auto,
                    'categorie' => $a->categorie,
                    'categorie_label' => $a->categorie_labell,
                    'image_url' => $a->image_url,
                    'publie_at' => $a->publie_at?->format('d/m/Y'),
                ])
                : [];

            $evenements = $page->evenements_actifs
                ? $page->evenements()->publies()->prochains()->limit(3)->get()->map(fn ($e) => [
                    'id' => $e->id,
                    'titre' => $e->titre,
                    'slug' => $e->slug,
                    'categorie' => $e->categorie,
                    'categorie_label' => $e->categorie_label,
                    'date_debut' => $e->date_debut->format('d/m/Y H:i'),
                    'lieu_nom' => $e->lieu_nom,
                    'image_url' => $e->image_url,
                ])
                : [];

            $deputes = $this->getDeputesCommune($ville);
            $senateurs = $this->getSenateursCommune($ville);
            $timeline = $this->getTimeline($page, 8);
            $galerie = $this->getGalerieImages($page, 6);
            $communesVoisines = $this->getCommunesVoisines($ville, 6);
            $communesSimilaires = $this->getCommunesSimilaires($ville, 4);
            $evenementsSemaine = $this->getEvenementsSemaine($page);

            return [
                'ville' => $this->formatVille($ville),
                'page' => $this->formatPage($page),
                'maire' => $maire ? [
                    'id' => $maire->id,
                    'nom' => $maire->nom,
                    'prenom' => $maire->prenom,
                    'civilite' => $maire->civilite,
                    'photo_url' => $maire->photo_url,
                    'nuance_politique' => $maire->nuance_politique ?? null,
                ] : null,
                'articles' => $articles,
                'evenements' => $evenements,
                'timeline' => $timeline,
                'galerie' => $galerie,
                'deputes' => $deputes,
                'senateurs' => $senateurs,
                'stats' => $this->getStatsCommune($ville),
                'communes_voisines' => $communesVoisines,
                'communes_similaires' => $communesSimilaires,
                'evenements_semaine' => $evenementsSemaine,
            ];
        });

        $data['seo'] = $this->seoData($ville, $page);
        $data['est_abonne'] = false;
        $data['abonnement'] = null;
        if ($user = auth()->user()) {
            $abonnement = CommuneAbonnement::where('user_id', $user->id)
                ->where('commune_code_insee', $codeInsee)
                ->first();
            $data['est_abonne'] = (bool) $abonnement;
            $data['abonnement'] = $abonnement;
            $data['est_admin'] = $page->estAdministrePar($user);
            $data['role_admin'] = $page->roleAdmin($user);
        }

        return Inertia::render('Commune/Index', $data);
    }

    public function budget(string $codeInsee): Response
    {
        $page = $this->resolvePage($codeInsee);
        $ville = $page->ville;

        $budgets = $ville->budgets()
            ->orderByDesc('annee')
            ->limit(10)
            ->get()
            ->map(fn ($b) => [
                'annee' => $b->annee,
                'recettes_fonctionnement' => $b->recettes_fonctionnement,
                'depenses_fonctionnement' => $b->depenses_fonctionnement,
                'recettes_investissement' => $b->recettes_investissement,
                'depenses_investissement' => $b->depenses_investissement,
                'encours_dette' => $b->encours_dette,
                'population' => $b->population,
            ]);

        return Inertia::render('Commune/Budget', [
            'ville' => $this->formatVille($ville),
            'page' => $this->formatPage($page),
            'budgets' => $budgets,
            'seo' => $this->seoData($ville, $page, 'Budget', "Budget municipal de {$ville->nom} : recettes, depenses et dette sur 10 ans."),
        ]);
    }

    public function elus(string $codeInsee): Response
    {
        $page = $this->resolvePage($codeInsee);
        $ville = $page->ville;

        $maire = $ville->maireActuel;
        $historiqueMaires = $ville->mandatsMaires()
            ->with('maire:id,nom,prenom,civilite')
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'nom' => $m->maire?->nom,
                'prenom' => $m->maire?->prenom,
                'civilite' => $m->maire?->civilite,
                'date_debut' => $m->date_debut?->format('Y'),
                'date_fin' => $m->date_fin?->format('Y'),
                'est_actuel' => $m->est_actuel,
                'nuance' => $m->nuance,
            ]);

        return Inertia::render('Commune/Elus', [
            'ville' => $this->formatVille($ville),
            'page' => $this->formatPage($page),
            'seo' => $this->seoData($ville, $page, 'Elus', "Elus de {$ville->nom} : maire, deputes, senateurs et historique des mandats."),
            'maire' => $maire ? [
                'id' => $maire->id,
                'nom' => $maire->nom,
                'prenom' => $maire->prenom,
                'civilite' => $maire->civilite,
                'photo_url' => $maire->photo_url,
                'nuance_politique' => $maire->nuance_politique ?? null,
            ] : null,
            'historique_maires' => $historiqueMaires,
            'deputes' => $this->getDeputesCommune($ville),
            'senateurs' => $this->getSenateursCommune($ville),
        ]);
    }

    public function elections(string $codeInsee): Response
    {
        $page = $this->resolvePage($codeInsee);
        $ville = $page->ville;

        $resultatsParTour = $ville->resultatsMunicipaux()
            ->with('listes')
            ->orderBy('tour')
            ->get();

        $tours = [];
        foreach ($resultatsParTour as $resultat) {
            $tours[$resultat->tour] = [
                'tour' => $resultat->tour,
                'inscrits' => $resultat->inscrits,
                'votants' => $resultat->votants,
                'exprimes' => $resultat->exprimes,
                'abstentions' => $resultat->abstentions,
                'taux_participation' => $resultat->taux_participation,
                'taux_abstention' => $resultat->taux_abstention,
                'statut' => $resultat->statut_libelle,
                'listes' => $resultat->listes->map(fn ($l) => [
                    'id' => $l->id,
                    'nom_liste' => $l->nom_liste,
                    'tete_liste' => $l->tete_de_liste_nom_complet,
                    'nuance' => $l->nuance_politique,
                    'voix' => $l->voix,
                    'pourcentage' => (float) $l->pourcentage_exprimes,
                    'sieges' => $l->sieges_obtenus,
                    'elu' => $l->elu,
                ])->sortByDesc('voix')->values(),
            ];
        }

        return Inertia::render('Commune/Elections', [
            'ville' => $this->formatVille($ville),
            'page' => $this->formatPage($page),
            'tours' => array_values($tours),
            'seo' => $this->seoData($ville, $page, 'Elections', "Resultats des elections municipales a {$ville->nom} : listes, scores et participation."),
        ]);
    }

    public function faq(string $codeInsee): Response
    {
        $page = $this->resolvePage($codeInsee);
        $ville = $page->ville;

        $faqItems = $this->generateFaqItems($ville, $page);

        return Inertia::render('Commune/Faq', [
            'ville' => $this->formatVille($ville),
            'page' => $this->formatPage($page),
            'faq' => $faqItems,
            'seo' => $this->seoData($ville, $page, 'FAQ', "Questions frequentes sur {$ville->nom} : maire, budget, population, superficie et informations pratiques."),
        ]);
    }

    public function abonner(Request $request, string $codeInsee)
    {
        $page = $this->resolvePage($codeInsee);

        CommuneAbonnement::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'commune_code_insee' => $codeInsee,
            ],
            [
                'notif_actus' => $request->boolean('notif_actus', true),
                'notif_evenements' => $request->boolean('notif_evenements', true),
                'notif_forum' => $request->boolean('notif_forum', false),
                'notif_email' => $request->boolean('notif_email', false),
            ]
        );

        $page->increment('abonnes_count');

        return back()->with('success', 'Vous suivez maintenant cette commune.');
    }

    public function desabonner(Request $request, string $codeInsee)
    {
        CommuneAbonnement::where('user_id', $request->user()->id)
            ->where('commune_code_insee', $codeInsee)
            ->delete();

        $page = CommunePage::where('code_insee', $codeInsee)->first();
        $page?->decrement('abonnes_count');

        return back()->with('success', 'Vous ne suivez plus cette commune.');
    }

    public function updatePreferencesAbonnement(Request $request, string $codeInsee)
    {
        $validated = $request->validate([
            'notif_actus' => 'boolean',
            'notif_evenements' => 'boolean',
            'notif_forum' => 'boolean',
            'notif_email' => 'boolean',
        ]);

        CommuneAbonnement::where('user_id', $request->user()->id)
            ->where('commune_code_insee', $codeInsee)
            ->update($validated);

        return back()->with('success', 'Preferences mises a jour.');
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    private function resolvePage(string $codeInsee): CommunePage
    {
        $page = CommunePage::with('ville.maireActuel')
            ->where('code_insee', $codeInsee)
            ->firstOrFail();

        return $page;
    }

    private function formatVille(Ville $ville): array
    {
        return [
            'id' => $ville->id,
            'code_insee' => $ville->code_insee,
            'nom' => $ville->nom,
            'slug' => $ville->slug,
            'code_postal' => $ville->code_postal_principal,
            'departement_code' => $ville->departement_code,
            'departement_nom' => $ville->departement_nom,
            'region_nom' => $ville->region_nom,
            'population' => $ville->population,
            'population_formate' => $ville->population_formate,
            'superficie_formate' => $ville->superficie_formate,
            'densite_formate' => $ville->densite_formate,
            'latitude' => $ville->latitude,
            'longitude' => $ville->longitude,
            'blason_url' => $ville->blason_url,
            'wikipedia_url' => $ville->wikipedia_url_formate,
            'site_officiel' => $ville->site_officiel,
            'est_prefecture' => $ville->est_prefecture,
            'est_chef_lieu_region' => $ville->est_chef_lieu_region,
            'epci_nom' => $ville->epci_nom,
            'altitude_formate' => $ville->altitude_formate,
        ];
    }

    private function formatPage(CommunePage $page): array
    {
        return [
            'id' => $page->id,
            'code_insee' => $page->code_insee,
            'statut' => $page->statut,
            'est_active' => $page->est_active,
            'est_reclamee' => $page->est_reclamee,
            'couleur_primaire' => $page->couleur_primaire,
            'couleur_secondaire' => $page->couleur_secondaire,
            'description_courte' => $page->description_courte,
            'mot_du_maire' => $page->mot_du_maire,
            'adresse_mairie' => $page->adresse_mairie,
            'telephone' => $page->telephone,
            'email_mairie' => $page->email_mairie,
            'site_officiel' => $page->site_officiel,
            'horaires_ouverture' => $page->horaires_ouverture,
            'image_couverture_url' => $page->image_couverture_url,
            'logo_url' => $page->logo_url,
            'reseaux_sociaux' => $page->reseaux_sociaux,
            'fonctionnalites' => [
                'actus' => $page->actus_actives,
                'evenements' => $page->evenements_actifs,
                'forum' => $page->forum_actif,
                'notifications' => $page->notifications_actives,
            ],
            'vues_totales' => $page->vues_totales,
            'abonnes_count' => $page->abonnes_count,
        ];
    }

    private function getDeputesCommune(Ville $ville): array
    {
        if (! $ville->circonscription || ! $ville->departement_code) {
            return [];
        }

        // circonscription est au format "13-01", on extrait le numero apres le tiret
        $numCirco = str_contains($ville->circonscription, '-')
            ? (int) explode('-', $ville->circonscription)[1]
            : (int) $ville->circonscription;

        return Cache::remember("commune_deputes:{$ville->code_insee}", 3600, function () use ($ville, $numCirco) {
            return DeputeCirconscription::with('depute')
                ->where('num_departement', $ville->departement_code)
                ->where('num_circo', $numCirco)
                ->actif()
                ->get()
                ->filter(fn ($dc) => $dc->depute)
                ->map(fn ($dc) => [
                    'uid' => $dc->depute->uid,
                    'nom' => $dc->depute->nom,
                    'prenom' => $dc->depute->prenom,
                    'civilite' => $dc->depute->civilite,
                    'photo_url' => $dc->depute->photo_url,
                    'groupe' => $dc->depute->groupe_politique_actuel?->libelle_abrege,
                    'circonscription' => $dc->num_circo,
                ])
                ->values()
                ->toArray();
        });
    }

    private function getSenateursCommune(Ville $ville): array
    {
        if (! $ville->departement_nom) {
            return [];
        }

        return Cache::remember("commune_senateurs:{$ville->departement_code}", 3600, function () use ($ville) {
            return Senateur::where('circonscription', 'ILIKE', '%'.$ville->departement_nom.'%')
                ->where('etat', 'En cours')
                ->get()
                ->map(fn ($s) => [
                    'matricule' => $s->matricule,
                    'nom' => $s->nom_usuel,
                    'prenom' => $s->prenom_usuel,
                    'civilite' => $s->civilite,
                    'photo_url' => $s->photo_url ?? null,
                    'groupe' => $s->groupe_politique,
                ])
                ->toArray();
        });
    }

    private function getStatsCommune(Ville $ville): array
    {
        $stats = $ville->stats;
        $dernierBudget = $ville->budgets()->first();

        return [
            'population' => $ville->population,
            'population_formate' => $ville->population_formate,
            'superficie' => $ville->superficie_km2,
            'densite' => $ville->densite,
            'budget_total' => $dernierBudget ? ($dernierBudget->recettes_fonctionnement ?? 0) + ($dernierBudget->recettes_investissement ?? 0) : null,
            'dette' => $dernierBudget?->encours_dette,
            'annee_budget' => $dernierBudget?->annee,
        ];
    }

    private function getTimeline(CommunePage $page, int $limit = 8): array
    {
        $items = collect();

        if ($page->actus_actives) {
            $articles = $page->articles()->publies()->recents()->limit($limit)->get();
            foreach ($articles as $a) {
                $items->push([
                    'type' => 'article',
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'slug' => $a->slug,
                    'extrait' => $a->extrait_auto,
                    'categorie' => $a->categorie,
                    'categorie_label' => $a->categorie_labell,
                    'image_url' => $a->image_url,
                    'date' => $a->publie_at?->toISOString(),
                    'date_formate' => $a->publie_at?->format('d/m/Y'),
                ]);
            }
        }

        if ($page->evenements_actifs) {
            $evenements = $page->evenements()->publies()->prochains()->limit($limit)->get();
            foreach ($evenements as $e) {
                $items->push([
                    'type' => 'evenement',
                    'id' => $e->id,
                    'titre' => $e->titre,
                    'slug' => $e->slug,
                    'categorie' => $e->categorie,
                    'categorie_label' => $e->categorie_label,
                    'date' => $e->date_debut->toISOString(),
                    'date_formate' => $e->date_debut->format('d/m/Y H:i'),
                    'lieu_nom' => $e->lieu_nom,
                    'image_url' => $e->image_url,
                    'inscription_requise' => $e->inscription_requise,
                    'est_complet' => $e->est_complet,
                    'places_restantes' => $e->places_restantes,
                ]);
            }
        }

        return $items->sortByDesc('date')->take($limit)->values()->toArray();
    }

    private function getGalerieImages(CommunePage $page, int $limit = 6): array
    {
        return $page->galerieImages()
            ->visibles()
            ->ordonne()
            ->limit($limit)
            ->get()
            ->map(fn ($img) => [
                'id' => $img->id,
                'image_url' => $img->image_url,
                'legende' => $img->legende,
                'credit' => $img->credit,
                'source' => $img->source,
            ])
            ->toArray();
    }

    private function getCommunesVoisines(Ville $ville, int $limit = 6): array
    {
        if (! $ville->latitude || ! $ville->longitude) {
            return [];
        }

        return Cache::remember("commune_voisines:{$ville->code_insee}", 86400, function () use ($ville, $limit) {
            return Ville::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('id', '!=', $ville->id)
                ->where('arrondissement_municipal', false)
                ->whereNotNull('population')
                ->selectRaw('*, (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?)) * sin(radians(latitude))
                    )
                ) AS distance', [$ville->latitude, $ville->longitude, $ville->latitude])
                ->having('distance', '<', 30)
                ->orderBy('distance')
                ->limit($limit)
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'nom' => $v->nom,
                    'slug' => $v->slug,
                    'code_insee' => $v->code_insee,
                    'population_formate' => $v->population_formate,
                    'distance_km' => round($v->distance, 1),
                    'has_hub' => CommunePage::where('ville_id', $v->id)->exists(),
                ])
                ->toArray();
        });
    }

    private function getCommunesSimilaires(Ville $ville, int $limit = 4): array
    {
        if (! $ville->population) {
            return [];
        }

        return Cache::remember("commune_similaires:{$ville->code_insee}", 86400, function () use ($ville, $limit) {
            $popMin = $ville->population * 0.7;
            $popMax = $ville->population * 1.3;

            return Ville::where('id', '!=', $ville->id)
                ->where('arrondissement_municipal', false)
                ->where('departement_code', $ville->departement_code)
                ->whereBetween('population', [$popMin, $popMax])
                ->whereNotNull('population')
                ->orderByRaw('ABS(population - ?) ASC', [$ville->population])
                ->limit($limit)
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'nom' => $v->nom,
                    'slug' => $v->slug,
                    'code_insee' => $v->code_insee,
                    'population_formate' => $v->population_formate,
                    'has_hub' => CommunePage::where('ville_id', $v->id)->exists(),
                ])
                ->toArray();
        });
    }

    private function getEvenementsSemaine(CommunePage $page): array
    {
        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        return $page->evenements()
            ->publies()
            ->whereBetween('date_debut', [$debutSemaine, $finSemaine])
            ->orderBy('date_debut')
            ->limit(10)
            ->get()
            ->map(fn (CommuneEvenement $e) => [
                'id' => $e->id,
                'titre' => $e->titre,
                'slug' => $e->slug,
                'date_debut_iso' => $e->date_debut->toIso8601String(),
                'date_courte' => $e->date_debut->format('D H\h'),
            ])
            ->toArray();
    }

    private function generateFaqItems(Ville $ville, CommunePage $page): array
    {
        $nom = $ville->nom;
        $items = [];

        $maire = $ville->maireActuel;
        if ($maire) {
            $items[] = [
                'question' => "Qui est le maire de {$nom} ?",
                'answer' => "Le maire actuel de {$nom} est {$maire->civilite} {$maire->prenom} {$maire->nom}.",
            ];
        }

        if ($ville->population) {
            $pop = number_format($ville->population, 0, ',', ' ');
            $items[] = [
                'question' => "Combien d'habitants a {$nom} ?",
                'answer' => "{$nom} compte {$pop} habitants.",
            ];
        }

        if ($ville->superficie_km2) {
            $items[] = [
                'question' => "Quelle est la superficie de {$nom} ?",
                'answer' => "{$nom} s'etend sur {$ville->superficie_formate}.",
            ];
        }

        $dernierBudget = $ville->budgets()->first();
        if ($dernierBudget) {
            $total = ($dernierBudget->recettes_fonctionnement ?? 0) + ($dernierBudget->recettes_investissement ?? 0);
            $formatted = number_format($total, 0, ',', ' ');
            $items[] = [
                'question' => "Quel est le budget de {$nom} ?",
                'answer' => "Le budget total de {$nom} pour {$dernierBudget->annee} s'eleve a {$formatted} euros en recettes (fonctionnement + investissement).",
            ];
        }

        if ($ville->departement_nom) {
            $items[] = [
                'question' => "Dans quel departement se trouve {$nom} ?",
                'answer' => "{$nom} se situe dans le departement {$ville->departement_nom} ({$ville->departement_code}), en region {$ville->region_nom}.",
            ];
        }

        if ($ville->code_postal_principal) {
            $items[] = [
                'question' => "Quel est le code postal de {$nom} ?",
                'answer' => "Le code postal principal de {$nom} est {$ville->code_postal_principal}.",
            ];
        }

        if ($page->telephone || $page->email_mairie) {
            $contact = "Vous pouvez contacter la mairie de {$nom}";
            if ($page->telephone) {
                $contact .= " par telephone au {$page->telephone}";
            }
            if ($page->email_mairie) {
                $contact .= ($page->telephone ? ' ou' : '')." par email a {$page->email_mairie}";
            }
            $contact .= '.';
            if ($page->adresse_mairie) {
                $contact .= " Adresse : {$page->adresse_mairie}.";
            }
            $items[] = [
                'question' => "Comment contacter la mairie de {$nom} ?",
                'answer' => $contact,
            ];
        }

        if ($ville->est_prefecture) {
            $items[] = [
                'question' => "{$nom} est-elle une prefecture ?",
                'answer' => "Oui, {$nom} est la prefecture du departement {$ville->departement_nom}.",
            ];
        }

        if ($ville->altitude_min || $ville->altitude_max) {
            $items[] = [
                'question' => "A quelle altitude se trouve {$nom} ?",
                'answer' => "{$nom} se situe entre {$ville->altitude_min} m et {$ville->altitude_max} m d'altitude.",
            ];
        }

        return $items;
    }

    private function seoData(Ville $ville, CommunePage $page, ?string $titre = null, ?string $description = null, ?string $type = 'website'): array
    {
        $nomVille = $ville->nom;

        return [
            'title' => ($titre ? "{$titre} - {$nomVille}" : $nomVille).' - Hub Citoyen',
            'description' => $description
                ?? $page->description_courte
                ?? "Decouvrez {$nomVille} ({$ville->departement_nom}) : actualites municipales, evenements, budget, elus et vie citoyenne.",
            'image' => $page->image_couverture_url ?? $ville->blason_url,
            'url' => url()->current(),
            'type' => $type,
            'ville_nom' => $nomVille,
            'code_postal' => $ville->code_postal_principal,
            'departement' => $ville->departement_nom,
            'latitude' => $ville->latitude,
            'longitude' => $ville->longitude,
            'population' => $ville->population,
            'telephone' => $page->telephone,
            'email' => $page->email_mairie,
            'adresse' => $page->adresse_mairie,
            'site_officiel' => $page->site_officiel ?? $ville->site_officiel,
            'logo' => $page->logo_url ?? $ville->blason_url,
        ];
    }
}
