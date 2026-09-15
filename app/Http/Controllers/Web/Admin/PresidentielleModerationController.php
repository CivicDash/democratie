<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\ModerationException;
use App\Http\Controllers\Controller;
use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\Controverse;
use App\Models\EvenementCampagne;
use App\Models\HatvpDeclaration;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\MesureScrutinLien;
use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;
use App\Models\PresidentielleModerationLog;
use App\Models\PresidentielleSignalement;
use App\Models\ProgrammeDocument;
use App\Models\ProgrammeMesure;
use App\Models\User;
use App\Services\Presidentielle\HatvpSummary;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\ModerationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
    /** Actions unitaires acceptées par action(). */
    private const ACTIONS = ['prendre_en_charge', 'demander_complement', 'valider', 'double_valider', 'publier', 'depublier', 'supprimer', 'mettre_en_avant', 'retirer_en_avant'];

    /**
     * Sous-ensemble applicable en lot : uniquement les transitions d'état du workflow.
     * Volontairement sans 'supprimer' ni la mise en avant — une suppression de masse ne doit
     * pas tenir à une case cochée par erreur.
     */
    private const ACTIONS_LOT = ['valider', 'double_valider', 'publier', 'depublier'];

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
            'signalements_en_attente' => PresidentielleSignalement::enAttente()->count(),
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
        $q = trim((string) $request->query('q', ''));

        $mesures = ProgrammeMesure::with(['candidat.personnePolitique', 'theme'])
            ->withCount(['liens as pour_count' => fn ($r) => $r->where('sens', 'pour')->publie()])
            ->withCount(['liens as contre_count' => fn ($r) => $r->where('sens', 'contre')->publie()])
            ->when($statut === 'publie', fn ($r) => $r->where('affiche_publiquement', true))
            ->when(! in_array($statut, ['tous', 'publie'], true), fn ($r) => $r->where('statut_validation', $statut))
            // La file grossit à chaque source ingérée : sans recherche, retrouver une
            // mesure suppose de feuilleter vingt-cinq lignes à la fois.
            ->when($q !== '', fn ($r) => $r->where(fn ($sub) => $sub
                ->where('titre', 'ilike', "%{$q}%")
                ->orWhereHas('candidat.personnePolitique', fn ($p) => $p
                    ->where('nom', 'ilike', "%{$q}%")
                    ->orWhere('prenom', 'ilike', "%{$q}%"))))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Presidentielle/Mesures', [
            'mesures' => $mesures,
            'statut' => $statut,
            'q' => $q,
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
        $controverses = Controverse::with(['theme', 'arguments.liens'])->withCount('arguments')
            ->when($request->query('statut', 'tous') !== 'tous', fn ($q) => $q->where('statut_validation', $request->query('statut')))
            ->orderByDesc('id')->paginate(25)->withQueryString()
            // Publier un argumentaire complet représente des centaines d'actions unitaires.
            // On expose donc, par controverse, les identifiants de ses faits et de ses liaisons
            // regroupés par étape du workflow, pour permettre une action en lot ciblée.
            // Chaque objet reste traité et tracé individuellement côté serveur.
            ->through(function (Controverse $c) {
                $args = $c->arguments;
                $liens = $args->flatMap->liens;
                $aValider = fn ($col) => $col->where('statut_validation', '!=', 'valide')->pluck('id')->values();
                $aPublier = fn ($col) => $col->where('statut_validation', 'valide')->where('affiche_publiquement', false)->pluck('id')->values();

                return [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'titre' => $c->titre,
                    'theme' => $c->theme ? ['nom' => $c->theme->nom] : null,
                    'arguments_count' => $c->arguments_count,
                    'statut_validation' => $c->statut_validation,
                    'affiche_publiquement' => $c->affiche_publiquement,
                    'lot' => [
                        'arguments_a_valider' => $aValider($args),
                        'arguments_a_publier' => $aPublier($args),
                        'liens_a_valider' => $aValider($liens),
                        'liens_a_publier' => $aPublier($liens),
                        // La seconde validation d'une liaison « contre » exige un modérateur
                        // différent du premier : le lot la propose, le service la refuse si
                        // c'est la même personne. Le garde-fou reste entier.
                        'liens_a_double_valider' => $liens->where('sens', 'contre')
                            ->where('statut_validation', 'valide')
                            ->whereNull('double_valide_par')->pluck('id')->values(),
                    ],
                ];
            });

        // Liaisons auto-détectées non encore reliées à une mesure (à résoudre).
        $liensAResoudre = ArgumentMesureLien::whereNull('mesure_id')->with('argument')
            ->orderByDesc('id')->limit(50)->get();

        // Pré-charge, par candidat proposé, ses mesures — pour une recherche sans saisie d'ID.
        $mesuresParSlug = [];
        foreach ($liensAResoudre->pluck('candidat_slug_propose')->filter()->unique() as $slug) {
            $mesuresParSlug[$slug] = ProgrammeMesure::whereHas('candidat', fn ($q) => $q
                ->where('election', '2027')->whereHas('personnePolitique', fn ($p) => $p->where('slug', $slug)))
                ->orderBy('titre')->get(['id', 'titre', 'theme_id'])
                ->map(fn ($m) => ['id' => $m->id, 'titre' => $m->titre])->values();
        }

        $liensAResoudre = $liensAResoudre->map(fn ($l) => [
            'id' => $l->id, 'sens' => $l->sens,
            'argument_titre' => $l->argument?->titre,
            'candidat_slug_propose' => $l->candidat_slug_propose,
            'mesure_proposee' => $l->mesure_proposee,
            'detection_confidence' => $l->detection_confidence,
            'mesures_candidat' => $mesuresParSlug[$l->candidat_slug_propose] ?? [],
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

    /** File des signalements citoyens (« Signaler une erreur »). */
    public function signalements(Request $request)
    {
        $statut = $request->query('statut', 'nouveau');

        $signalements = PresidentielleSignalement::with('moderator')
            ->parStatut($statut)
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($s) => [
                'id' => $s->id,
                'type_incident' => $s->type_incident,
                'type_libelle' => PresidentielleSignalement::TYPES_INCIDENT[$s->type_incident] ?? $s->type_incident,
                'description' => $s->description,
                'email' => $s->email,
                'candidat_slug' => $s->candidat_slug,
                'theme_slug' => $s->theme_slug,
                'contexte_url' => $s->contexte_url,
                'statut' => $s->statut,
                'moderator' => $s->moderator?->name,
                'resolution_note' => $s->resolution_note,
                'resolved_at' => optional($s->resolved_at)->toDateTimeString(),
                'created_at' => optional($s->created_at)->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Presidentielle/Signalements', [
            'signalements' => $signalements,
            'types_incident' => PresidentielleSignalement::TYPES_INCIDENT,
            'statut' => $statut,
        ]);
    }

    /** Traite un signalement : prise en charge, résolution ou rejet (journalisé). */
    public function signalementAction(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:presidentielle_signalements,id'],
            'action' => ['required', 'in:prendre_en_charge,resoudre,rejeter'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $signalement = PresidentielleSignalement::findOrFail($data['id']);
        $ancien = $signalement->statut;
        $user = $request->user();

        [$statut, $resolu] = match ($data['action']) {
            'prendre_en_charge' => ['en_cours', false],
            'resoudre' => ['resolu', true],
            'rejeter' => ['rejete', true],
        };

        $signalement->update([
            'statut' => $statut,
            'moderator_id' => $user->id,
            'resolution_note' => $data['note'] ?? $signalement->resolution_note,
            'resolved_at' => $resolu ? now() : $signalement->resolved_at,
        ]);

        // Journalisation dans le log de modération présidentielle (traçabilité).
        PresidentielleModerationLog::create([
            'entite_type' => $signalement->getMorphClass(),
            'entite_id' => $signalement->id,
            'action' => $data['action'],
            'ancien_statut' => $ancien,
            'nouveau_statut' => $statut,
            'commentaire' => $data['note'] ?? null,
            'moderator_id' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Signalement mis à jour ('.$statut.').');
    }

    /** Écran BO : rattachement des déclarations HATVP aux candidats (recherche + aperçu + rattachement). */
    public function hatvp()
    {
        $candidats = CandidatPresidentielle::where('election', '2027')->with('personnePolitique')
            ->orderBy('ordre_affichage')->orderBy('id')->get()
            ->map(function ($c) {
                $pid = $c->personne_politique_id;
                $liee = $pid
                    ? HatvpDeclaration::where('personne_politique_id', $pid)->orderByDesc('date_depot')->first()
                    : null;

                return [
                    'id' => $c->id,
                    'personne_politique_id' => $pid,
                    'nom' => $c->personnePolitique?->nom_complet,
                    'nom_recherche' => trim(($c->personnePolitique?->nom ?? '').' '.($c->personnePolitique?->prenom ?? '')),
                    'hatvp_statut' => $c->hatvp_statut,
                    'declaration_liee' => $liee ? [
                        'uuid' => $liee->uuid, 'type' => $liee->type_declaration,
                        'date_depot' => optional($liee->date_depot)->format('d/m/Y'),
                    ] : null,
                ];
            });

        return Inertia::render('Admin/Presidentielle/Hatvp', [
            'candidats' => $candidats,
            'statuts' => ['a_verifier', 'lie', 'non_soumis', 'non_disponible'],
        ]);
    }

    /** Recherche JSON de déclarations HATVP par nom (pour rattacher). */
    public function hatvpSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['resultats' => []]);
        }

        $resultats = HatvpDeclaration::where(fn ($w) => $w
            ->where('nom', 'ILIKE', "%{$q}%")
            ->orWhere('prenom', 'ILIKE', "%{$q}%")
            ->orWhereRaw("(prenom || ' ' || nom) ILIKE ?", ["%{$q}%"]))
            ->orderByDesc('date_depot')->limit(25)->get()
            ->map(fn ($d) => [
                'uuid' => $d->uuid,
                'nom' => $d->nom, 'prenom' => $d->prenom,
                'type' => $d->type_declaration,
                'date_depot' => optional($d->date_depot)->format('d/m/Y'),
                'type_mandat' => $d->type_mandat,
                'deja_liee_a' => $d->personne_politique_id,
            ]);

        return response()->json(['resultats' => $resultats]);
    }

    /** Aperçu JSON « façon CivicDash » d'une déclaration (avant rattachement). */
    public function hatvpPreview(string $uuid, HatvpSummary $builder)
    {
        return response()->json(['summary' => $builder->pourUuid($uuid)]);
    }

    /** Rattache une déclaration à la personne d'un candidat (pose la FK, statut « lié »). */
    public function hatvpRattacher(Request $request)
    {
        $data = $request->validate([
            'candidat_id' => ['required', 'integer', 'exists:candidats_presidentielle,id'],
            'declaration_uuid' => ['required', 'string', 'exists:hatvp_declarations,uuid'],
        ]);

        $candidat = CandidatPresidentielle::with('personnePolitique')->findOrFail($data['candidat_id']);
        if (! $candidat->personne_politique_id) {
            throw ValidationException::withMessages(['candidat_id' => 'Ce candidat n’a pas de personne politique associée.']);
        }

        HatvpDeclaration::where('uuid', $data['declaration_uuid'])
            ->update(['personne_politique_id' => $candidat->personne_politique_id]);
        $candidat->update(['hatvp_statut' => 'lie']);

        return back()->with('success', 'Déclaration HATVP rattachée à '.$candidat->personnePolitique?->nom_complet.'.');
    }

    /** Détache toutes les déclarations HATVP d'un candidat. */
    public function hatvpDetacher(Request $request)
    {
        $data = $request->validate(['candidat_id' => ['required', 'integer', 'exists:candidats_presidentielle,id']]);
        $candidat = CandidatPresidentielle::findOrFail($data['candidat_id']);
        if ($candidat->personne_politique_id) {
            HatvpDeclaration::where('personne_politique_id', $candidat->personne_politique_id)
                ->update(['personne_politique_id' => null]);
        }
        $candidat->update(['hatvp_statut' => 'a_verifier']);

        return back()->with('success', 'Déclaration(s) HATVP détachée(s).');
    }

    /** Fixe l'état d'honnêteté HATVP d'un candidat (non_soumis / non_disponible / a_verifier). */
    public function hatvpStatut(Request $request)
    {
        $data = $request->validate([
            'candidat_id' => ['required', 'integer', 'exists:candidats_presidentielle,id'],
            'statut' => ['required', 'in:a_verifier,lie,non_soumis,non_disponible'],
        ]);
        CandidatPresidentielle::whereKey($data['candidat_id'])->update(['hatvp_statut' => $data['statut']]);

        return back()->with('success', 'Statut HATVP mis à jour ('.$data['statut'].').');
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
    /**
     * Calendrier de campagne : liste des événements et de leur état de validation.
     * Un événement n'est publiable que daté ET vérifiable à une source.
     */
    public function evenements(Request $request)
    {
        $evenements = EvenementCampagne::with(['candidats.personnePolitique', 'document'])
            ->where('election', '2027')->orderByDesc('date_debut')->get()
            ->map(fn (EvenementCampagne $e) => [
                'id' => $e->id,
                'titre' => $e->titre,
                'type' => $e->type,
                'date_debut' => $e->date_debut?->toDateString(),
                'precision_date' => $e->precision_date,
                'lieu' => $e->lieu,
                'ville' => $e->ville,
                'statut' => $e->statut,
                'statut_validation' => $e->statut_validation,
                'affiche_publiquement' => $e->affiche_publiquement,
                'url_video' => $e->url_video,
                'url_source' => $e->url_source,
                'note_methodologique' => $e->note_methodologique,
                'document_titre' => $e->document?->titre,
                'candidats' => $e->candidats->map(fn ($c) => trim(($c->personnePolitique?->prenom ?? '').' '.($c->personnePolitique?->nom ?? '')))->values(),
                'raisons_non_publiable' => $e->raisonsNonPubliable(),
            ]);

        return Inertia::render('Admin/Presidentielle/Evenements', [
            'evenements' => $evenements,
            'types' => ['meeting', 'debat', 'discours', 'interview', 'emission', 'deplacement', 'communique', 'autre'],
        ]);
    }

    /** Mise à jour d'un événement (date, lieu, type, statut) avant validation. */
    public function evenementUpdate(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'titre' => ['required', 'string', 'max:500'],
            'type' => ['required', 'string', 'max:30'],
            'date_debut' => ['required', 'date'],
            'precision_date' => ['required', 'in:heure,jour,mois'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:120'],
            'statut' => ['required', 'in:annonce,confirme,reporte,annule'],
            'note_methodologique' => ['nullable', 'string', 'max:2000'],
        ]);

        EvenementCampagne::findOrFail($data['id'])->update($data);

        return back()->with('success', 'Événement mis à jour.');
    }

    /**
     * Validation puis publication d'un événement. Les invariants sont vérifiés ici :
     * un calendrier sans lien de vérification contredirait la promesse du site.
     */
    public function evenementAction(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'action' => ['required', 'in:valider,publier,depublier'],
        ]);

        $e = EvenementCampagne::findOrFail($data['id']);

        if ($data['action'] === 'publier') {
            if ($e->statut_validation !== 'valide') {
                throw ValidationException::withMessages(['action' => 'L\'événement doit être validé avant publication.']);
            }
            if ($raisons = $e->raisonsNonPubliable()) {
                throw ValidationException::withMessages(['action' => 'Publication impossible : '.implode(' ; ', $raisons)]);
            }
        }

        $e->update(match ($data['action']) {
            'valider' => ['statut_validation' => 'valide', 'valide_par' => $request->user()->id, 'valide_at' => now()],
            'publier' => ['affiche_publiquement' => true],
            'depublier' => ['affiche_publiquement' => false],
        });

        return back()->with('success', 'Action « '.$data['action'].' » appliquée.');
    }

    /**
     * Audience d'objectif2027.fr. Lit des agrégats déjà calculés : aucune donnée
     * personnelle n'est manipulée ici, la table ne contient que des compteurs.
     */
    public function audience(Request $request)
    {
        $jours = max(7, min(180, (int) $request->query('jours', 30)));
        $depuis = now()->subDays($jours)->toDateString();

        $mesures = DB::table('audience_jour')->where('jour', '>=', $depuis)
            ->selectRaw('jour, sum(vues_humaines) as humains, sum(vues_bots) as bots, max(visiteurs_estimes) as visiteurs')
            ->groupBy('jour')->orderBy('jour')->get()->keyBy('jour');

        // Un jour sans trafic n'a pas de ligne en base. Sans ce remplissage, la courbe
        // rapproche silencieusement deux dates éloignées et l'axe des abscisses ment :
        // une coupure de trois jours se lit comme une simple baisse.
        $parJour = collect();
        for ($d = now()->subDays($jours)->startOfDay(); $d->lte(now()->startOfDay()); $d->addDay()) {
            $cle = $d->toDateString();
            $ligne = $mesures->get($cle);
            $parJour->push([
                'jour' => $cle,
                'humains' => (int) ($ligne->humains ?? 0),
                'bots' => (int) ($ligne->bots ?? 0),
                'visiteurs' => (int) ($ligne->visiteurs ?? 0),
            ]);
        }

        $parPage = DB::table('audience_jour')->where('jour', '>=', $depuis)
            ->selectRaw('chemin, sum(vues_humaines) as humains, sum(vues_bots) as bots')
            ->groupBy('chemin')->orderByDesc('humains')->limit(30)->get();

        return Inertia::render('Admin/Presidentielle/Audience', [
            'par_jour' => $parJour,
            'par_page' => $parPage,
            'jours' => $jours,
            'totaux' => [
                'humains' => (int) $parJour->sum('humains'),
                'bots' => (int) $parJour->sum('bots'),
                'visiteurs_max' => (int) $parJour->max('visiteurs'),
                'meilleur_jour' => $parJour->sortByDesc('humains')->first(),
            ],
            // Sans journal, la page doit expliquer quoi faire plutôt que d'afficher zéro.
            'actif' => DB::table('audience_jour')->exists(),
        ]);
    }

    /**
     * Répartition thématique de la campagne : combien de mesures par thème, et de qui.
     *
     * Le décompte brut répond à une question simple — quels sujets occupent la campagne —
     * mais il en cache une autre : un thème peut être gros parce qu'il est réellement
     * débattu, ou seulement parce qu'un candidat y a été dépouillé plus finement que les
     * autres. Les deux se ressemblent sur un histogramme et se distinguent sur deux
     * indicateurs, servis ici à côté du total :
     *   - le nombre de candidats ayant au moins une mesure sur le thème ;
     *   - la part du candidat le plus représenté (la « concentration »).
     * Un thème à 120 mesures dont 70 % viennent d'une seule personne est un artefact de
     * corpus, pas un fait de campagne. La page le dit, plutôt que de laisser croire.
     */
    public function themes(Request $request)
    {
        $perimetres = ['toutes', 'validees', 'publiees'];
        $perimetre = in_array($request->query('perimetre'), $perimetres, true)
            ? $request->query('perimetre') : 'toutes';

        $candidatId = (int) $request->query('candidat', 0) ?: null;

        $filtrer = function ($q) use ($perimetre, $candidatId) {
            if ($perimetre === 'validees') {
                $q->where('m.statut_validation', 'valide');
            } elseif ($perimetre === 'publiees') {
                $q->where('m.affiche_publiquement', true);
            }
            if ($candidatId) {
                $q->where('m.candidat_id', $candidatId);
            }

            return $q;
        };

        // Un décompte par thème ET par candidat : c'est la même requête qui alimente le
        // total, l'empilement du graphe et la concentration. Les agréger côté PHP évite
        // trois requêtes qui pourraient diverger.
        $lignes = $filtrer(DB::table('programme_mesures as m')
            ->join('candidats_presidentielle as c', 'c.id', '=', 'm.candidat_id')
            ->join('personnes_politiques as pp', 'pp.id', '=', 'c.personne_politique_id')
            ->whereNull('m.deleted_at')
            ->where('c.election', '2027'))
            ->selectRaw('m.theme_id, m.candidat_id, pp.nom as candidat_nom, c.couleur_hex, count(*) as n')
            ->groupBy('m.theme_id', 'm.candidat_id', 'pp.nom', 'c.couleur_hex')
            ->get();

        // Les thèmes sans aucune mesure doivent apparaître à zéro : un sujet absent de la
        // campagne est une information, pas une ligne à omettre.
        $themes = DB::table('programme_themes')->orderBy('ordre')->orderBy('nom')
            ->get(['id', 'slug', 'nom']);

        $parTheme = $lignes->groupBy('theme_id');
        $total = (int) $lignes->sum('n');

        $donnees = $themes->map(function ($t) use ($parTheme, $total) {
            $l = $parTheme->get($t->id, collect());
            $n = (int) $l->sum('n');
            $tete = $l->sortByDesc('n')->first();

            return [
                'id' => $t->id,
                'slug' => $t->slug,
                'nom' => $t->nom,
                'total' => $n,
                'part' => $total > 0 ? round($n * 100 / $total, 1) : 0.0,
                'candidats' => $l->count(),
                'tete_nom' => $tete->candidat_nom ?? null,
                'tete_n' => (int) ($tete->n ?? 0),
                'concentration' => $n > 0 ? round(((int) ($tete->n ?? 0)) * 100 / $n, 1) : 0.0,
                // Empilement du graphe, du plus gros contributeur au plus petit.
                'detail' => $l->sortByDesc('n')->values()->map(fn ($x) => [
                    'candidat_id' => $x->candidat_id,
                    'nom' => $x->candidat_nom,
                    'couleur' => $x->couleur_hex,
                    'n' => (int) $x->n,
                ]),
            ];
        })->values();

        // Ce qui n'est pas encore compté : la file d'ingestion. Sans ce chiffre, un thème
        // peut sembler délaissé alors que ses propositions attendent simplement d'être
        // rattachées à des mesures.
        $enFile = DB::table('ingestion_propositions')
            ->where('statut', 'detecte')
            ->when($candidatId, fn ($q) => $q->where('candidat_id', $candidatId))
            ->selectRaw('theme_id, count(*) as n')
            ->groupBy('theme_id')->pluck('n', 'theme_id');

        $donnees = $donnees->map(fn ($d) => $d + ['en_file' => (int) ($enFile[$d['id']] ?? 0)]);

        return Inertia::render('Admin/Presidentielle/Themes', [
            'themes' => $donnees,
            'total' => $total,
            'total_en_file' => (int) $enFile->sum(),
            'perimetre' => $perimetre,
            'candidat_id' => $candidatId,
            'candidats' => CandidatPresidentielle::with('personnePolitique')
                ->where('election', '2027')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'nom' => $c->personnePolitique?->nom_complet ?? '—',
                    'couleur' => $c->couleur_hex,
                ])
                ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
        ]);
    }

    public function action(Request $request, ModerationService $service)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(self::MODELS))],
            'id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:'.implode(',', self::ACTIONS)],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        $model = self::MODELS[$data['type']];
        $entite = $model::findOrFail($data['id']);

        try {
            $message = $this->appliquerAction($entite, $data['action'], $request->user(), $service, $data['commentaire'] ?? null);
        } catch (ModerationException $e) {
            throw ValidationException::withMessages(['action' => $e->getMessage()]);
        }

        return back()->with('success', $message);
    }

    /**
     * Applique en lot une action de modération à plusieurs entités du même type.
     *
     * Chaque entité passe individuellement par ModerationService : les invariants sont
     * vérifiés un par un et chaque action reste tracée dans presidentielle_moderation_logs.
     * En particulier, la double validation d'une liaison « contre » continue d'exiger un
     * modérateur DIFFÉRENT du premier validateur — le lot n'affaiblit aucun garde-fou.
     *
     * Un échec n'interrompt pas le lot : les motifs sont collectés et rendus à l'écran, car
     * sur une centaine d'objets un blocage isolé ne doit pas annuler le travail restant.
     */
    public function actionLot(Request $request, ModerationService $service)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(self::MODELS))],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:'.implode(',', self::ACTIONS_LOT)],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        $model = self::MODELS[$data['type']];
        $user = $request->user();
        $commentaire = $data['commentaire'] ?? null;

        $traites = 0;
        $echecs = [];

        foreach ($model::findMany($data['ids']) as $entite) {
            try {
                $this->appliquerAction($entite, $data['action'], $user, $service, $commentaire);
                $traites++;
            } catch (ModerationException $e) {
                $echecs[] = ['id' => $entite->getKey(), 'motif' => $e->getMessage()];
            }
        }

        $resume = $traites.' élément(s) traité(s)';
        if ($echecs) {
            $resume .= ', '.count($echecs).' en échec';
        }

        return back()
            ->with('success', 'Action « '.$data['action'].' » en lot : '.$resume.'.')
            ->with('echecs_lot', $echecs);
    }

    /**
     * Cœur commun aux actions unitaires et en lot. Retourne le message de succès.
     *
     * @throws ModerationException si l'action est refusée pour cette entité
     */
    private function appliquerAction(Model $entite, string $action, User $user, ModerationService $service, ?string $commentaire): string
    {
        // Suppression d'une mesure (soft-delete) + détachement de sa proposition d'ingestion :
        // débloque la suppression du discours d'origine. Réservé aux mesures.
        if ($action === 'supprimer') {
            if (! $entite instanceof ProgrammeMesure) {
                throw new ModerationException('La suppression ne concerne que les mesures.');
            }
            $service->supprimerMesure($entite, $user, $commentaire);

            return 'Mesure supprimée ; sa proposition est revenue en file de tri.';
        }

        // Mesure « phare » : alimente le comparateur (priorité) ET le quiz d'affinité.
        // Réservé aux mesures ; sans effet public tant que la mesure n'est pas publiée.
        if (in_array($action, ['mettre_en_avant', 'retirer_en_avant'], true)) {
            if (! $entite instanceof ProgrammeMesure) {
                throw new ModerationException('La mise en avant ne concerne que les mesures.');
            }
            $entite->update(['est_mise_en_avant' => $action === 'mettre_en_avant']);

            return $action === 'mettre_en_avant'
                ? 'Mesure marquée « phare » (comparateur + quiz).'
                : 'Mesure retirée des phares.';
        }

        match ($action) {
            'prendre_en_charge' => $service->prendreEnCharge($entite, $user),
            'demander_complement' => $service->demanderComplement($entite, $user, $commentaire ?? 'complément requis'),
            'valider' => $service->valider($entite, $user, $commentaire),
            'double_valider' => $entite instanceof ArgumentMesureLien
                ? $service->doubleValider($entite, $user)
                : throw new ModerationException('La double validation ne concerne que les liaisons argument↔mesure « contre ».'),
            'publier' => $service->publier($entite, $user),
            'depublier' => $service->depublier($entite, $user, $commentaire),
        };

        return 'Action « '.$action.' » appliquée.';
    }

    /**
     * Journal des décisions prises sur une entité.
     *
     * `presidentielle_moderation_logs` était alimenté à chaque geste depuis la mise en
     * service — plus de mille sept cents lignes — et affiché nulle part. On ne pouvait
     * donc pas répondre à « qui a validé cette question clé, quand, et pourquoi ».
     *
     * Une route dédiée, appelée à l'ouverture du dépliant, plutôt qu'un eager-load :
     * les files affichent vingt-cinq lignes et le journal n'est consulté que
     * ponctuellement.
     */
    public function journal(string $type, int $id)
    {
        abort_unless(isset(self::MODELS[$type]), 404);

        $modele = self::MODELS[$type];

        $entrees = PresidentielleModerationLog::with('moderator:id,name')
            ->where('entite_type', (new $modele)->getMorphClass())
            ->where('entite_id', $id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (PresidentielleModerationLog $l) => [
                'id' => $l->id,
                'action' => $l->action,
                'ancien_statut' => $l->ancien_statut,
                'nouveau_statut' => $l->nouveau_statut,
                'commentaire' => $l->commentaire,
                'moderateur' => $l->moderator?->name ?? 'Système',
                'date' => $l->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json(['entrees' => $entrees]);
    }
}
