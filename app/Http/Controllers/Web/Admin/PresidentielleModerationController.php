<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\ModerationException;
use App\Http\Controllers\Controller;
use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\Controverse;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\MesureScrutinLien;
use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeDocument;
use App\Models\ProgrammeMesure;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\ModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
        'argument_lien' => ArgumentMesureLien::class,   // liaison argument↔mesure (porte le sens)
        'controverse' => Controverse::class,
        'lien' => MesureScrutinLien::class,              // lien mesure↔scrutin (module cohérence)
        'parcours' => ParcoursEvenement::class,
        'programme_document' => ProgrammeDocument::class,
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
            'referentiels' => ProgrammeDocument::with('candidat.personnePolitique')->withCount('items')->get()
                ->map(fn ($d) => [
                    'id' => $d->id, 'titre' => $d->titre, 'url' => $d->url,
                    'candidat' => $d->candidat?->personnePolitique?->nom_complet,
                    'nb_items' => $d->items_count,
                    'statut_validation' => $d->statut_validation,
                    'affiche_publiquement' => $d->affiche_publiquement,
                ]),
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

        // Prises de parole (documents d'ingestion) pour permettre la suppression d'un import erroné.
        $documents = IngestionDocument::withCount('propositions')
            ->withCount(['propositions as rattachees_count' => fn ($q) => $q->where('statut', 'rattachee')])
            ->orderByDesc('id')->limit(30)->get()
            ->map(fn ($d) => [
                'id' => $d->id, 'titre' => $d->titre, 'type' => $d->type,
                'nb_propositions' => $d->propositions_count,
                'nb_rattachees' => $d->rattachees_count,
            ]);

        return Inertia::render('Admin/Presidentielle/Propositions', [
            'propositions' => $propositions,
            'documents' => $documents,
            'statut' => $request->query('statut', 'detecte'),
        ]);
    }

    /**
     * Supprime une prise de parole (document d'ingestion) et ses propositions.
     * Refuse si des propositions ont déjà été rattachées à des mesures : il faut
     * d'abord traiter ces mesures (éviter de casser du contenu validé/publié).
     */
    public function documentDestroy(IngestionDocument $document)
    {
        if ($document->propositions()->where('statut', 'rattachee')->exists()) {
            throw ValidationException::withMessages([
                'document' => 'Suppression refusée : des propositions sont rattachées à des mesures. '
                    .'Dans « Mesures », dépubliez puis supprimez ces mesures (leur proposition revient en file) avant de supprimer ce discours.',
            ]);
        }

        $titre = $document->titre;
        $document->propositions()->delete();
        $document->delete();

        return back()->with('success', "Prise de parole supprimée : « {$titre} » (et ses propositions).");
    }

    /** File des mesures par statut de validation (ou « publie » = affichées publiquement). */
    public function mesures(Request $request)
    {
        $statut = $request->query('statut', 'detecte');

        $mesures = ProgrammeMesure::with(['candidat.personnePolitique', 'theme'])
            ->withCount(['liens as pour_count' => fn ($q) => $q->where('sens', 'pour')->publie()])
            ->withCount(['liens as contre_count' => fn ($q) => $q->where('sens', 'contre')->publie()])
            ->when($statut === 'publie', fn ($q) => $q->where('affiche_publiquement', true))
            ->when(! in_array($statut, ['tous', 'publie'], true), fn ($q) => $q->where('statut_validation', $statut))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Presidentielle/Mesures', [
            'mesures' => $mesures,
            'statut' => $statut,
        ]);
    }

    /** File des candidats par statut de validation. */
    public function candidats(Request $request)
    {
        $candidats = CandidatPresidentielle::with('personnePolitique')
            ->when($request->query('statut', 'tous') !== 'tous', fn ($q) => $q->where('statut_validation', $request->query('statut')))
            ->orderBy('ordre_affichage')->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Presidentielle/Candidats', [
            'candidats' => $candidats,
            'statut' => $request->query('statut', 'tous'),
        ]);
    }

    /**
     * Ajout manuel d'un candidat (ex. nouvelle déclaration de candidature).
     * Entre TOUJOURS en statut detecte / non publié : le circuit de validation
     * s'applique ensuite comme pour les imports. Réutilise la personne politique
     * existante si elle est déjà en base (slug prénom-nom).
     */
    public function candidatStore(Request $request)
    {
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'parti' => ['nullable', 'string', 'max:150'],
            'nuance' => ['nullable', 'string', 'max:10'],
            'statut_candidature' => ['required', 'in:'.implode(',', CandidatPresidentielle::STATUTS_CANDIDATURE)],
            'date_declaration' => ['nullable', 'date'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'site_campagne_url' => ['nullable', 'url', 'max:500'],
            'slogan' => ['nullable', 'string', 'max:200'],
            'couleur_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'couleur_hex.regex' => 'Couleur au format #rrggbb.',
        ]);

        $slug = Str::slug($data['prenom'].' '.$data['nom']);
        $personne = PersonnePolitique::firstOrCreate(
            ['slug' => $slug],
            [
                'prenom' => $data['prenom'], 'nom' => $data['nom'],
                'parti_politique' => $data['parti'] ?? null,
                'nuance_politique' => $data['nuance'] ?? null,
            ]
        );

        if (CandidatPresidentielle::where('personne_politique_id', $personne->id)->where('election', '2027')->exists()) {
            throw ValidationException::withMessages(['nom' => 'Ce candidat existe déjà pour 2027 (voir la liste).']);
        }

        CandidatPresidentielle::create([
            'personne_politique_id' => $personne->id,
            'election' => '2027',
            'statut_candidature' => $data['statut_candidature'],
            'date_declaration' => $data['date_declaration'] ?? null,
            'parti_soutien' => $data['parti'] ?? null,
            'nuance_politique' => $data['nuance'] ?? null,
            'slogan' => $data['slogan'] ?? null,
            'site_campagne_url' => $data['site_campagne_url'] ?? null,
            'couleur_hex' => $data['couleur_hex'] ?? null,
            'detection_raw_data' => ['source_declaration_url' => $data['source_url'] ?? null],
            'source_detection' => 'manuel',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ]);

        return back()->with('success', 'Candidat ajouté en file de modération (statut detecte).');
    }

    /**
     * Synchronise le parcours d'un candidat depuis les données CivicDash
     * (postes ministériels, mandats). Les événements entrent en `detecte`.
     */
    public function syncParcours(CandidatPresidentielle $candidat)
    {
        $slug = $candidat->personnePolitique?->slug;
        if (! $slug) {
            throw ValidationException::withMessages(['candidat' => 'Personne politique introuvable pour ce candidat.']);
        }

        Artisan::call('presidentielle:import-parcours', ['--candidat' => $slug]);
        $sortie = trim(preg_replace('/\s+/', ' ', Artisan::output()));

        return back()->with('success', 'Sync parcours — '.Str::limit($sortie, 300));
    }

    /** File des événements de parcours (validation avant publication). */
    public function parcours(Request $request)
    {
        $evenements = ParcoursEvenement::with('personnePolitique')
            ->when($request->query('statut', 'detecte') !== 'tous', fn ($q) => $q->where('statut_validation', $request->query('statut', 'detecte')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Presidentielle/Parcours', [
            'evenements' => $evenements,
            'statut' => $request->query('statut', 'detecte'),
        ]);
    }

    /** Gestion des médias (portrait + bannière + couleur) et des liens par candidat. */
    public function medias()
    {
        $candidats = CandidatPresidentielle::with('personnePolitique')
            ->orderBy('ordre_affichage')->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nom' => $c->personnePolitique?->nom_complet,
                'couleur_hex' => $c->couleur_hex ?? '#64748b',
                'slogan' => $c->slogan,
                'photo_url' => $c->photo_url, 'photo_credit' => $c->photo_credit, 'photo_licence' => $c->photo_licence,
                'hero_banner_url' => $c->hero_banner_url, 'hero_credit' => $c->hero_credit, 'hero_licence' => $c->hero_licence,
                'site_campagne_url' => $c->site_campagne_url,
                'site_web' => $c->personnePolitique?->site_web,
                'twitter_url' => $c->personnePolitique?->twitter_url,
                'instagram_url' => $c->personnePolitique?->instagram_url,
                'facebook_url' => $c->personnePolitique?->facebook_url,
                'mastodon_url' => $c->personnePolitique?->mastodon_url,
                'bluesky_url' => $c->personnePolitique?->bluesky_url,
                'linkedin_url' => $c->personnePolitique?->linkedin_url,
                'youtube_url' => $c->personnePolitique?->youtube_url,
                'tiktok_url' => $c->personnePolitique?->tiktok_url,
            ]);

        return Inertia::render('Admin/Presidentielle/Medias', ['candidats' => $candidats]);
    }

    /** Enregistre les URLs d'images + crédits/licences (obligatoires si URL fournie). */
    public function updateMedias(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'couleur_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'photo_url' => ['nullable', 'url', 'max:500'],
            'photo_credit' => ['nullable', 'string', 'max:255', 'required_with:photo_url'],
            'photo_licence' => ['nullable', 'string', 'max:120', 'required_with:photo_url'],
            'hero_banner_url' => ['nullable', 'url', 'max:500'],
            'hero_credit' => ['nullable', 'string', 'max:255', 'required_with:hero_banner_url'],
            'hero_licence' => ['nullable', 'string', 'max:120', 'required_with:hero_banner_url'],
            'site_campagne_url' => ['nullable', 'url', 'max:500'],
            'site_web' => ['nullable', 'url', 'max:500'],
            'twitter_url' => ['nullable', 'url', 'max:500'],
            'instagram_url' => ['nullable', 'url', 'max:500'],
            'facebook_url' => ['nullable', 'url', 'max:500'],
            'mastodon_url' => ['nullable', 'url', 'max:500'],
            'bluesky_url' => ['nullable', 'url', 'max:500'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'tiktok_url' => ['nullable', 'url', 'max:500'],
        ], [
            'photo_url.url' => 'Le portrait doit être une URL directe d\'image (https://…).',
            'photo_credit.required_with' => 'Le crédit du portrait est obligatoire.',
            'photo_licence.required_with' => 'La licence du portrait est obligatoire.',
            'hero_credit.required_with' => 'Le crédit de la bannière est obligatoire.',
            'hero_licence.required_with' => 'La licence de la bannière est obligatoire.',
        ]);

        $candidat = CandidatPresidentielle::with('personnePolitique')->findOrFail($data['id']);

        $champsCandidat = ['couleur_hex', 'slogan', 'photo_url', 'photo_credit', 'photo_licence',
            'hero_banner_url', 'hero_credit', 'hero_licence', 'site_campagne_url'];
        $candidat->update(collect($data)->only($champsCandidat)->all());

        $candidat->personnePolitique?->update(collect($data)->only([
            'site_web', 'twitter_url', 'instagram_url', 'facebook_url',
            'mastodon_url', 'bluesky_url', 'linkedin_url', 'youtube_url', 'tiktok_url',
        ])->all());

        return back()->with('success', 'Médias et liens enregistrés pour '.$candidat->personnePolitique?->nom_complet.'.');
    }

    /**
     * Upload de propositions depuis le BO (contrat JSON §11 + transcription optionnelle).
     * Réutilise la commande d'import (vérification verbatim incluse) : les modérateurs
     * chargent les discours sans passer par le terminal. Fichiers archivés pour trace.
     */
    public function propositionsImport(Request $request)
    {
        $request->validate([
            'fichier' => ['required', 'file', 'max:10240'],   // JSON de propositions (≤10 Mo)
            'source' => ['nullable', 'file', 'max:20480'],    // transcription txt/srt/vtt (≤20 Mo)
        ], [
            'fichier.required' => 'Le fichier JSON de propositions est requis.',
        ]);

        $dir = storage_path('app/ingestion/uploads');
        File::ensureDirectoryExists($dir);
        $stamp = now()->format('Ymd_His').'_'.Str::random(5);

        $jsonPath = $dir.'/'.$stamp.'_propositions.json';
        $request->file('fichier')->move($dir, basename($jsonPath));

        $args = ['fichier' => $jsonPath];
        if ($request->file('source')) {
            $srcPath = $dir.'/'.$stamp.'_transcription.txt';
            $request->file('source')->move($dir, basename($srcPath));
            $args['--source'] = $srcPath;
        }

        $code = Artisan::call('presidentielle:import-propositions', $args);
        $sortie = trim(preg_replace('/\s+/', ' ', Artisan::output()));

        if ($code !== 0) {
            throw ValidationException::withMessages(['fichier' => 'Import refusé : '.Str::limit($sortie, 400)]);
        }

        return back()->with('success', Str::limit($sortie, 400));
    }

    /** Fiche « argumentaire » d'une mesure : liaisons pour/contre vers des faits sourcés + publiabilité. */
    public function arguments(ProgrammeMesure $mesure, ModerationService $service)
    {
        $mesure->load([
            'candidat.personnePolitique', 'theme',
            'liens' => fn ($q) => $q->orderBy('sens')->orderByDesc('id'),
            'liens.argument.sources', 'liens.argument.controverse',
        ]);

        return Inertia::render('Admin/Presidentielle/Arguments', [
            'mesure' => [
                'id' => $mesure->id,
                'titre' => $mesure->titre,
                'resume' => $mesure->resume,
                'candidat' => $mesure->candidat?->personnePolitique?->nom_complet,
                'theme' => $mesure->theme?->nom,
                'statut_validation' => $mesure->statut_validation,
                'affiche_publiquement' => $mesure->affiche_publiquement,
                'source_officielle_url' => $mesure->source_officielle_url,
            ],
            // Une liaison = l'emploi d'un fait (argument) dans un sens pour CETTE mesure.
            'liens' => $mesure->liens->map(fn ($l) => [
                'id' => $l->id,
                'sens' => $l->sens,
                'note_contextuelle' => $l->note_contextuelle,
                'statut_validation' => $l->statut_validation,
                'affiche_publiquement' => $l->affiche_publiquement,
                'valide_par' => $l->valide_par,
                'double_valide_par' => $l->double_valide_par,
                'source_detection' => $l->source_detection,
                'detection_confidence' => $l->detection_confidence,
                'raisons_non_publiable' => $service->raisonsNonPubliable($l),
                'argument' => [
                    'id' => $l->argument->id,
                    'titre' => $l->argument->titre,
                    'contenu' => $l->argument->contenu,
                    'type_argument' => $l->argument->type_argument,
                    'statut_validation' => $l->argument->statut_validation,
                    'affiche_publiquement' => $l->argument->affiche_publiquement,
                    'controverse' => $l->argument->controverse?->titre,
                    'nb_autres_liens' => $l->argument->liens()->where('id', '!=', $l->id)->count(),
                    'sources' => $l->argument->sources->map(fn ($s) => [
                        'id' => $s->id, 'type_source' => $s->type_source, 'titre' => $s->titre,
                        'url' => $s->url, 'media' => $s->media, 'fiabilite' => $s->fiabilite,
                        'archive_url' => $s->archive_url,
                    ]),
                ],
            ]),
            'types_argument' => Argument::TYPES,
            'types_source' => ArgumentSource::TYPES_SOURCE,
            'raisons_non_publiable' => $service->raisonsNonPubliable($mesure),
        ]);
    }

    /**
     * Crée un fait (argument autonome) ET sa liaison à une mesure (detecte).
     * Le sens et la note contextuelle sont portés par la liaison.
     */
    public function argumentStore(Request $request)
    {
        $data = $request->validate([
            'mesure_id' => ['required', 'integer', 'exists:programme_mesures,id'],
            'sens' => ['required', 'in:pour,contre'],
            'titre' => ['required', 'string', 'max:255'],
            'contenu' => ['required', 'string', 'max:500'],
            'type_argument' => ['required', 'in:'.implode(',', Argument::TYPES)],
            'note_contextuelle' => ['required', 'string', 'max:2000'],
            'controverse_id' => ['nullable', 'integer', 'exists:controverses,id'],
        ], [
            'contenu.max' => 'Contenu limité à 500 caractères (factuel, sans adjectif militant).',
            'note_contextuelle.required' => 'La note contextuelle (pourquoi ce fait joue dans ce sens pour cette mesure) est obligatoire.',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $argument = Argument::create([
                'controverse_id' => $data['controverse_id'] ?? null,
                'titre' => $data['titre'],
                'contenu' => $data['contenu'],
                'type_argument' => $data['type_argument'],
                'statut_validation' => 'detecte',
                'affiche_publiquement' => false,
            ]);
            ArgumentMesureLien::create([
                'argument_id' => $argument->id,
                'mesure_id' => $data['mesure_id'],
                'sens' => $data['sens'],
                'note_contextuelle' => $data['note_contextuelle'],
                'source_detection' => 'manuel',
                'statut_validation' => 'detecte',
                'affiche_publiquement' => false,
            ]);
        });

        return back()->with('success', 'Fait « '.$data['sens'].' » ajouté (detecte). Ajoutez au moins une source fiable avant validation.');
    }

    /** Relie un fait EXISTANT à une (autre) mesure — réutilisation d'un argument sourcé. */
    public function lienStore(Request $request)
    {
        $data = $request->validate([
            'argument_id' => ['required', 'integer', 'exists:arguments,id'],
            'mesure_id' => ['required', 'integer', 'exists:programme_mesures,id'],
            'sens' => ['required', 'in:pour,contre'],
            'note_contextuelle' => ['required', 'string', 'max:2000'],
        ]);

        try {
            ArgumentMesureLien::create($data + [
                'source_detection' => 'manuel', 'statut_validation' => 'detecte', 'affiche_publiquement' => false,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            throw ValidationException::withMessages(['argument_id' => 'Cette liaison (argument + mesure + sens) existe déjà.']);
        }

        return back()->with('success', 'Fait relié à la mesure ('.$data['sens'].').');
    }

    /** Résout une liaison auto-détectée « à résoudre » en lui affectant une mesure. */
    public function lienResolve(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:argument_mesure_liens,id'],
            'mesure_id' => ['required', 'integer', 'exists:programme_mesures,id'],
        ]);

        ArgumentMesureLien::findOrFail($data['id'])->update([
            'mesure_id' => $data['mesure_id'],
            'candidat_slug_propose' => null,
            'mesure_proposee' => null,
        ]);

        return back()->with('success', 'Liaison reliée à la mesure.');
    }

    /** Ajoute une source à un argument (URL obligatoire — l'intégrité l'exige). */
    public function argumentSourceStore(Request $request)
    {
        $data = $request->validate([
            'argument_id' => ['required', 'integer', 'exists:arguments,id'],
            'type_source' => ['required', 'in:'.implode(',', ArgumentSource::TYPES_SOURCE)],
            'titre' => ['nullable', 'string', 'max:500'],
            'url' => ['required', 'url', 'max:1000'],
            'media' => ['nullable', 'string', 'max:200'],
            'date_publication' => ['nullable', 'date'],
            'extrait' => ['nullable', 'string', 'max:1000'],
            'archive_url' => ['nullable', 'url', 'max:1000'],
            'fiabilite' => ['required', 'in:haute,moyenne,basse'],
        ]);

        ArgumentSource::create($data + ['verifie_par' => $request->user()->id, 'verifie_at' => now()]);

        return back()->with('success', 'Source ajoutée.');
    }

    /** File des controverses (regroupements d'arguments) + liaisons à résoudre. */
    public function controverses(Request $request)
    {
        $controverses = Controverse::with('theme')->withCount('arguments')
            ->when($request->query('statut', 'tous') !== 'tous', fn ($q) => $q->where('statut_validation', $request->query('statut')))
            ->orderByDesc('id')->paginate(25)->withQueryString();

        // Liaisons auto-détectées non encore reliées à une mesure (à résoudre).
        $liensAResoudre = ArgumentMesureLien::whereNull('mesure_id')->with('argument')
            ->orderByDesc('id')->limit(50)->get()
            ->map(fn ($l) => [
                'id' => $l->id, 'sens' => $l->sens,
                'argument_titre' => $l->argument?->titre,
                'candidat_slug_propose' => $l->candidat_slug_propose,
                'mesure_proposee' => $l->mesure_proposee,
                'detection_confidence' => $l->detection_confidence,
            ]);

        return Inertia::render('Admin/Presidentielle/Controverses', [
            'controverses' => $controverses,
            'liens_a_resoudre' => $liensAResoudre,
            'themes' => \App\Models\ProgrammeTheme::orderBy('ordre')->get(['id', 'nom']),
            'statut' => $request->query('statut', 'tous'),
        ]);
    }

    /** Crée une controverse (detecte). */
    public function controverseStore(Request $request)
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'theme_id' => ['nullable', 'integer', 'exists:programme_themes,id'],
            'note_methodologique' => ['nullable', 'string', 'max:5000'],
        ]);

        Controverse::create([
            'slug' => Str::slug($data['titre']).'-'.Str::lower(Str::random(4)),
            'titre' => $data['titre'],
            'theme_id' => $data['theme_id'] ?? null,
            'note_methodologique' => $data['note_methodologique'] ?? null,
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ]);

        return back()->with('success', 'Controverse créée (detecte).');
    }

    /**
     * Import d'arguments depuis le BO (contrats §4 v1.1 « arguments » et v1.2
     * « arguments_controverse »). Réutilise la commande d'import ; tout atterrit en detecte.
     */
    public function argumentsImport(Request $request)
    {
        $request->validate([
            'fichier' => ['required', 'file', 'max:10240'],
        ], [
            'fichier.required' => 'Le fichier JSON d\'arguments est requis.',
        ]);

        $dir = storage_path('app/ingestion/uploads');
        File::ensureDirectoryExists($dir);
        $stamp = now()->format('Ymd_His').'_'.Str::random(5);
        $jsonPath = $dir.'/'.$stamp.'_arguments.json';
        $request->file('fichier')->move($dir, basename($jsonPath));

        $code = Artisan::call('presidentielle:import-arguments', ['fichier' => $jsonPath]);
        $sortie = trim(preg_replace('/\s+/', ' ', Artisan::output()));

        if ($code !== 0) {
            throw ValidationException::withMessages(['fichier' => 'Import refusé : '.Str::limit($sortie, 400)]);
        }

        return back()->with('success', Str::limit($sortie, 400));
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
            'action' => ['required', 'string', 'in:prendre_en_charge,demander_complement,valider,double_valider,publier,depublier,supprimer,mettre_en_avant,retirer_en_avant'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        $model = self::MODELS[$data['type']];
        $entite = $model::findOrFail($data['id']);
        $user = $request->user();
        $commentaire = $data['commentaire'] ?? null;

        // Suppression d'une mesure (soft-delete) + détachement de sa proposition d'ingestion :
        // débloque la suppression du discours d'origine. Réservé aux mesures.
        if ($data['action'] === 'supprimer') {
            if (! $entite instanceof ProgrammeMesure) {
                throw ValidationException::withMessages(['action' => 'La suppression ne concerne que les mesures.']);
            }
            try {
                $service->supprimerMesure($entite, $user, $commentaire);
            } catch (ModerationException $e) {
                throw ValidationException::withMessages(['action' => $e->getMessage()]);
            }

            return back()->with('success', 'Mesure supprimée ; sa proposition est revenue en file de tri.');
        }

        // Mesure « phare » : alimente le comparateur (priorité) ET le quiz d'affinité.
        // Réservé aux mesures ; sans effet public tant que la mesure n'est pas publiée.
        if (in_array($data['action'], ['mettre_en_avant', 'retirer_en_avant'], true)) {
            if (! $entite instanceof ProgrammeMesure) {
                throw ValidationException::withMessages(['action' => 'La mise en avant ne concerne que les mesures.']);
            }
            $entite->update(['est_mise_en_avant' => $data['action'] === 'mettre_en_avant']);

            return back()->with('success', $data['action'] === 'mettre_en_avant' ? 'Mesure marquée « phare » (comparateur + quiz).' : 'Mesure retirée des phares.');
        }

        try {
            match ($data['action']) {
                'prendre_en_charge' => $service->prendreEnCharge($entite, $user),
                'demander_complement' => $service->demanderComplement($entite, $user, $commentaire ?? 'complément requis'),
                'valider' => $service->valider($entite, $user, $commentaire),
                'double_valider' => $entite instanceof ArgumentMesureLien
                    ? $service->doubleValider($entite, $user)
                    : throw new ModerationException('La double validation ne concerne que les liaisons argument↔mesure « contre ».'),
                'publier' => $service->publier($entite, $user),
                'depublier' => $service->depublier($entite, $user, $commentaire),
            };
        } catch (ModerationException $e) {
            throw ValidationException::withMessages(['action' => $e->getMessage()]);
        }

        return back()->with('success', 'Action « '.$data['action'].' » appliquée.');
    }
}
