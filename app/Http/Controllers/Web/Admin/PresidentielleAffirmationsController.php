<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExtraireEurostatJob;
use App\Models\Affirmation;
use App\Models\AffirmationConstat;
use App\Models\AffirmationGraphique;
use App\Models\AffirmationSource;
use App\Models\AffirmationVerdict;
use App\Models\EurostatIndicateur;
use App\Models\ImportLog;
use App\Models\ProgrammeTheme;
use App\Services\Presidentielle\Eurostat\ExtractionEurostat;
use App\Services\Presidentielle\ModerationService;
use App\Services\Presidentielle\ReglesAffirmation;
use App\Support\UrlSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Back-office de « Ce qu'on entend » et de ses séries Eurostat.
 *
 * Séparé de PresidentielleModerationController (1 600 lignes), mais dans le même groupe de
 * routes : mêmes permissions, et surtout le même middleware GardeIntegriteExport — une
 * modification qui rendrait impubliable une fiche publiée est annulée avant d'atteindre
 * l'export. Valider, publier, dépublier et supprimer passent par les actions génériques
 * (type `affirmation`).
 *
 * Écritures sous forme lisible par AuditEcritures (`Modele::create([...])` explicite ou
 * `$modele->update($validated)`) : chaque clé validée doit arriver en base.
 */
class PresidentielleAffirmationsController extends Controller
{
    public function index(ModerationService $service)
    {
        $fiches = Affirmation::with(['theme', 'verdicts', 'constats.sources', 'sources', 'graphiques'])
            ->where('election', '2027')
            ->get()
            ->sortBy(fn ($f) => [$f->theme?->ordre ?? 99, $f->enonce])
            ->values();

        return Inertia::render('Admin/Presidentielle/Affirmations', [
            'fiches' => $fiches->map(fn (Affirmation $f) => [
                'id' => $f->id,
                'slug' => $f->slug,
                'enonce' => $f->enonce,
                'theme' => $f->theme?->nom,
                'verdicts' => $f->verdicts->map(fn ($v) => ['portee' => $v->portee, 'verdict' => $v->verdict])->all(),
                'statut_validation' => $f->statut_validation,
                'affiche_publiquement' => $f->affiche_publiquement,
                'a_verifier' => $f->constats->where('verification', '!==', 'verifie')->count(),
                'constats' => $f->constats->count(),
                'sources_sans_url' => $f->sources->reject(fn ($s) => UrlSource::estValide($s->url))->count(),
                'chiffres_sans_source' => $f->constats
                    ->filter(fn ($c) => in_array($c->section, AffirmationConstat::SECTIONS_SOURCEES, true) && $c->sources->isEmpty())
                    ->count(),
                'graphiques' => $f->graphiques->count(),
                'raisons' => $service->raisonsNonPubliable($f),
            ])->all(),
            'symetrie' => $this->symetrie($fiches),
            'verdicts' => Affirmation::VERDICTS,
            'colorations' => Affirmation::COLORATIONS,
        ]);
    }

    /**
     * Tableau de l'annexe C, recalculé : pour chaque coloration perçue, les verdicts de ses
     * fiches, regroupés par famille. INTERNE — il ne sert qu'à voir un déséquilibre, jamais
     * à le corriger en forçant des verdicts.
     */
    private function symetrie($fiches): array
    {
        $vue = function ($lot) {
            $lignes = [];
            foreach ([...array_keys(Affirmation::COLORATIONS), 'non_renseignee'] as $coloration) {
                $du = $lot->filter(fn ($f) => ($f->coloration_percue ?? 'non_renseignee') === $coloration);
                $familles = ['confirme' => 0, 'nuance' => 0, 'infirme' => 0, 'inverifiable' => 0];
                foreach ($du as $f) {
                    foreach ($f->verdicts as $v) {
                        $familles[Affirmation::FAMILLES_VERDICT[$v->verdict] ?? 'inverifiable']++;
                    }
                }
                $lignes[] = [
                    'coloration' => $coloration,
                    'libelle' => Affirmation::COLORATIONS[$coloration] ?? 'Non renseignée',
                    'fiches' => $du->count(),
                    'familles' => $familles,
                    'detail' => $du->map(fn ($f) => [
                        'enonce' => $f->enonce,
                        'verdicts' => $f->verdicts->pluck('verdict')->all(),
                    ])->values()->all(),
                ];
            }

            return $lignes;
        };

        return [
            'publiees' => $vue($fiches->filter(fn ($f) => $f->affiche_publiquement && $f->statut_validation === 'valide')),
            'toutes' => $vue($fiches),
        ];
    }

    public function import(Request $request)
    {
        $request->validate([
            'fichier' => ['required', 'file', 'max:10240'],
        ], [
            'fichier.required' => 'Le fichier JSON est requis.',
        ]);

        $dir = storage_path('app/ingestion/uploads');
        File::ensureDirectoryExists($dir);
        $chemin = $dir.'/'.now()->format('Ymd_His').'_'.Str::random(5).'_affirmations.json';
        $request->file('fichier')->move($dir, basename($chemin));

        $code = Artisan::call('presidentielle:import-affirmations', array_filter([
            'fichier' => $chemin,
            '--remplacer' => $request->boolean('remplacer'),
            '--dry-run' => $request->boolean('apercu'),
        ]));
        $sortie = trim(preg_replace('/\s+/', ' ', Artisan::output()));

        if ($code !== 0) {
            throw ValidationException::withMessages(['fichier' => 'Import refusé : '.Str::limit($sortie, 1500)]);
        }

        return back()->with('success', Str::limit($sortie, 600));
    }

    public function show(Affirmation $affirmation, ModerationService $service)
    {
        $affirmation->load(['themesSecondaires:id', 'verdicts', 'constats.sources', 'sources.constats', 'graphiques']);
        $codes = $affirmation->graphiques->flatMap(fn ($g) => (array) $g->indicateurs)->unique()->values();
        $indicateurs = EurostatIndicateur::whereIn('code', $codes)->get()->keyBy('code');

        return Inertia::render('Admin/Presidentielle/AffirmationDetail', [
            'fiche' => [
                'id' => $affirmation->id,
                'slug' => $affirmation->slug,
                'enonce' => $affirmation->enonce,
                'resume' => $affirmation->resume,
                'theme_id' => $affirmation->theme_id,
                'themes_secondaires' => $affirmation->themesSecondaires->pluck('id')->all(),
                'part_de_valeur' => $affirmation->part_de_valeur,
                'derniere_verification' => $affirmation->derniere_verification?->toDateString(),
                'coloration_percue' => $affirmation->coloration_percue,
                'statut_validation' => $affirmation->statut_validation,
                'affiche_publiquement' => $affirmation->affiche_publiquement,
            ],
            'raisons' => $service->raisonsNonPubliable($affirmation),
            'verdicts' => $affirmation->verdicts->map(fn ($v) => $v->only(['id', 'ordre', 'portee', 'verdict']))->all(),
            'constats' => $affirmation->constats->map(fn (AffirmationConstat $c) => [
                'id' => $c->id,
                'section' => $c->section,
                'groupe' => $c->groupe,
                'ordre' => $c->ordre,
                'texte' => $c->texte,
                'verification' => $c->verification,
                'note_verification' => $c->note_verification,
                'sources' => $c->sources->pluck('id')->all(),
                'alerte_bornes' => self::evolutionSansBornes($c->texte),
            ])->all(),
            'sources' => $affirmation->sources->map(fn (AffirmationSource $s) => [
                'id' => $s->id,
                'cle' => $s->cle,
                'producteur' => $s->producteur,
                'titre' => $s->titre,
                'url' => $s->url,
                'archive_url' => $s->archive_url,
                'categorie' => $s->categorie,
                'date_publication' => $s->date_publication?->toDateString(),
                'date_consultation' => $s->date_consultation?->toDateString(),
                'url_valide' => UrlSource::estValide($s->url),
                'exclue' => ReglesAffirmation::domaineExclu($s->url),
                'citations' => $s->constats->count(),
            ])->all(),
            'graphiques' => $affirmation->graphiques->map(fn (AffirmationGraphique $g) => [
                'id' => $g->id,
                'constat_id' => $g->constat_id,
                'type' => $g->type,
                'titre' => $g->titre,
                'sous_titre' => $g->sous_titre,
                'note' => $g->note,
                'options' => $g->options,
                'indicateurs' => collect((array) $g->indicateurs)->map(fn ($code) => [
                    'code' => $code,
                    'etat' => isset($indicateurs[$code]) ? ($indicateurs[$code]->estPublie() ? $indicateurs[$code]->etat() : 'nouveau') : 'jamais_extrait',
                    'dernieres' => isset($indicateurs[$code]) ? self::dernieresValeurs($indicateurs[$code]->series_publiees ?? $indicateurs[$code]->series_detectees ?? []) : [],
                ])->all(),
            ])->all(),
            'themes' => ProgrammeTheme::actif()->ordonne()->get(['id', 'nom']),
            'listes' => [
                'verdicts' => Affirmation::VERDICTS,
                'colorations' => Affirmation::COLORATIONS,
                'sections' => AffirmationConstat::SECTIONS,
                'categories' => AffirmationSource::CATEGORIES,
                'types' => AffirmationGraphique::TYPES,
            ],
        ]);
    }

    public function update(Request $request, Affirmation $affirmation)
    {
        $validated = $request->validate([
            'enonce' => ['required', 'string', 'max:300'],
            'resume' => ['nullable', 'string', 'max:2000'],
            'theme_id' => ['required', 'integer', 'exists:programme_themes,id'],
            'part_de_valeur' => ['boolean'],
            'derniere_verification' => ['nullable', 'date'],
            'coloration_percue' => ['nullable', Rule::in(array_keys(Affirmation::COLORATIONS))],
        ]);
        $affirmation->update($validated);

        $secondaires = $request->validate([
            'themes_secondaires' => ['array'],
            'themes_secondaires.*' => ['integer', 'exists:programme_themes,id'],
        ]);
        $affirmation->themesSecondaires()->sync($secondaires['themes_secondaires'] ?? []);

        return back()->with('success', 'Fiche enregistrée.');
    }

    public function verdictStore(Request $request, Affirmation $affirmation)
    {
        $request->validate([
            'portee' => ['nullable', 'string', 'max:300'],
            'verdict' => ['required', Rule::in(array_keys(Affirmation::VERDICTS))],
        ]);

        AffirmationVerdict::create([
            'affirmation_id' => $affirmation->id,
            'ordre' => (int) $affirmation->verdicts()->max('ordre') + 1,
            'portee' => $request->input('portee'),
            'verdict' => $request->input('verdict'),
        ]);

        return back()->with('success', 'Verdict ajouté.');
    }

    public function verdictUpdate(Request $request, AffirmationVerdict $verdict)
    {
        $validated = $request->validate([
            'portee' => ['nullable', 'string', 'max:300'],
            'verdict' => ['required', Rule::in(array_keys(Affirmation::VERDICTS))],
        ]);
        $verdict->update($validated);

        return back()->with('success', 'Verdict enregistré.');
    }

    public function verdictDestroy(AffirmationVerdict $verdict)
    {
        $verdict->delete();

        return back()->with('success', 'Verdict retiré.');
    }

    public function constatStore(Request $request, Affirmation $affirmation)
    {
        $request->validate([
            'section' => ['required', Rule::in(array_keys(AffirmationConstat::SECTIONS))],
            'groupe' => ['nullable', 'string', 'max:200'],
            'texte' => ['required', 'string', 'max:5000'],
        ]);

        $constat = AffirmationConstat::create([
            'affirmation_id' => $affirmation->id,
            'section' => $request->input('section'),
            'groupe' => $request->input('groupe'),
            'ordre' => (int) $affirmation->constats()->max('ordre') + 1,
            'texte' => $request->input('texte'),
            // Un constat saisi à la main n'est pas vérifié du seul fait d'avoir été écrit.
            'verification' => 'a_verifier',
        ]);
        $constat->sources()->sync($this->sourcesDe($request, $affirmation));

        return back()->with('success', 'Constat ajouté (à vérifier).');
    }

    public function constatUpdate(Request $request, AffirmationConstat $constat)
    {
        $validated = $request->validate([
            'section' => ['required', Rule::in(array_keys(AffirmationConstat::SECTIONS))],
            'groupe' => ['nullable', 'string', 'max:200'],
            'texte' => ['required', 'string', 'max:5000'],
            'note_verification' => ['nullable', 'string', 'max:2000'],
        ]);
        $constat->update($validated);
        $constat->sources()->sync($this->sourcesDe($request, $constat->affirmation));

        return back()->with('success', 'Constat enregistré.');
    }

    /** Marque un constat vérifié (ou le remet à vérifier), et trace qui l'a fait. */
    public function constatVerification(Request $request, AffirmationConstat $constat)
    {
        $validated = $request->validate([
            'verification' => ['required', Rule::in(array_keys(AffirmationConstat::VERIFICATIONS))],
        ]);
        $verifie = $validated['verification'] === 'verifie';
        $validated['verifie_par'] = $verifie ? $request->user()->id : null;
        $validated['verifie_at'] = $verifie ? now() : null;
        $constat->update($validated);

        return back()->with('success', $verifie ? 'Constat vérifié.' : 'Constat remis à vérifier.');
    }

    public function constatDestroy(AffirmationConstat $constat)
    {
        $constat->delete();

        return back()->with('success', 'Constat supprimé.');
    }

    public function sourceStore(Request $request, Affirmation $affirmation)
    {
        $request->validate([
            'cle' => ['required', 'string', 'max:80', Rule::unique('affirmation_sources', 'cle')->where('affirmation_id', $affirmation->id)],
            'producteur' => ['required', 'string', 'max:200'],
            'titre' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'url:http,https', 'max:1000'],
            'archive_url' => ['nullable', 'url:http,https', 'max:1000'],
            'categorie' => ['required', Rule::in(array_keys(AffirmationSource::CATEGORIES))],
            'date_publication' => ['nullable', 'date'],
            'date_consultation' => ['nullable', 'date'],
        ]);

        AffirmationSource::create([
            'affirmation_id' => $affirmation->id,
            'cle' => $request->input('cle'),
            'producteur' => $request->input('producteur'),
            'titre' => $request->input('titre'),
            'url' => $request->input('url'),
            'archive_url' => $request->input('archive_url'),
            'categorie' => $request->input('categorie'),
            'date_publication' => $request->input('date_publication'),
            'date_consultation' => $request->input('date_consultation'),
        ]);

        return back()->with('success', 'Source ajoutée.');
    }

    public function sourceUpdate(Request $request, AffirmationSource $source)
    {
        $validated = $request->validate([
            'producteur' => ['required', 'string', 'max:200'],
            'titre' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'url:http,https', 'max:1000'],
            'archive_url' => ['nullable', 'url:http,https', 'max:1000'],
            'categorie' => ['required', Rule::in(array_keys(AffirmationSource::CATEGORIES))],
            'date_publication' => ['nullable', 'date'],
            'date_consultation' => ['nullable', 'date'],
        ]);
        $source->update($validated);

        return back()->with('success', 'Source enregistrée.');
    }

    public function sourceDestroy(AffirmationSource $source)
    {
        $source->delete();

        return back()->with('success', 'Source supprimée.');
    }

    public function graphiqueUpdate(Request $request, AffirmationGraphique $graphique)
    {
        $validated = $request->validate([
            'constat_id' => ['nullable', 'integer', Rule::exists('affirmation_constats', 'id')->where('affirmation_id', $graphique->affirmation_id)],
            'titre' => ['required', 'string', 'max:300'],
            'sous_titre' => ['nullable', 'string', 'max:300'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $graphique->update($validated);

        return back()->with('success', 'Graphique enregistré.');
    }

    /** Les séries Eurostat : ce que le site montre, ce que la dernière extraction a trouvé. */
    public function eurostat()
    {
        $modeles = EurostatIndicateur::all()->keyBy('code');
        $graphiques = AffirmationGraphique::with(['affirmation:id,enonce,slug,affiche_publiquement', 'affirmation.constats' => fn ($q) => $q->where('section', 'europe')])->get();

        $indicateurs = collect(config('eurostat.indicateurs'))->map(function ($def) use ($modeles, $graphiques) {
            $m = $modeles[$def['code']] ?? null;
            $fiches = $graphiques->filter(fn ($g) => in_array($def['code'], (array) $g->indicateurs, true))
                ->pluck('affirmation')->filter()->unique('id')->values();

            return [
                'id' => $m?->id,
                'code' => $def['code'],
                'titre' => $def['titre'],
                'unite' => $def['unite'],
                'jeu' => $def['jeu'],
                'etat' => $m ? $m->etat() : 'jamais_extrait',
                'extraction_publiee' => $m?->extraction_publiee?->toDateString(),
                'extraction_detectee' => $m?->extraction_detectee?->toDateString(),
                'diff' => $m?->diff,
                'fiches' => $fiches->map(fn ($f) => [
                    'id' => $f->id,
                    'enonce' => $f->enonce,
                    'publiee' => $f->affiche_publiquement,
                    'textes' => $f->constats->pluck('texte')->all(),
                ])->all(),
            ];
        })->values();

        $derniere = ImportLog::where('command', 'presidentielle:eurostat-extraire')->latest('started_at')->first();

        return Inertia::render('Admin/Presidentielle/Eurostat', [
            'indicateurs' => $indicateurs,
            'derniere_extraction' => $derniere ? [
                'date' => $derniere->started_at?->format('d/m/Y H:i'),
                'statut' => $derniere->status,
                'message' => $derniere->error_message,
            ] : null,
        ]);
    }

    public function eurostatExtraire()
    {
        ExtraireEurostatJob::dispatch();

        return back()->with('success', 'Extraction lancée. Elle prend une à deux minutes : rechargez la page ensuite.');
    }

    /**
     * Publie la série détectée. Quand des fiches l'utilisent, la révision peut contredire
     * les phrases écrites sous le graphique : il faut les avoir relues.
     */
    public function eurostatValider(Request $request, EurostatIndicateur $indicateur, ExtractionEurostat $extraction)
    {
        $utilisee = AffirmationGraphique::get()->contains(fn ($g) => in_array($indicateur->code, (array) $g->indicateurs, true));
        if ($utilisee && $indicateur->etat() === 'revision' && ! $request->boolean('textes_relus')) {
            throw ValidationException::withMessages([
                'textes_relus' => 'Cette série est citée par des fiches : relisez leurs phrases de comparaison européenne, puis cochez « textes relus ».',
            ]);
        }

        $extraction->valider($indicateur, $request->user());

        return back()->with('success', "Série « {$indicateur->code} » validée : c'est désormais celle que le site montre.");
    }

    /** Première extraction : valider d'un coup les séries jamais publiées. */
    public function eurostatValiderNouveaux(Request $request, ExtractionEurostat $extraction)
    {
        $nouveaux = EurostatIndicateur::whereNull('series_publiees')->whereNotNull('series_detectees')->get();
        foreach ($nouveaux as $indicateur) {
            $extraction->valider($indicateur, $request->user());
        }

        return back()->with('success', $nouveaux->count().' série(s) nouvelle(s) validée(s).');
    }

    /** @return list<int> ids de sources de la fiche citées par le constat */
    private function sourcesDe(Request $request, Affirmation $affirmation): array
    {
        $data = $request->validate([
            'sources' => ['array'],
            'sources.*' => ['integer', Rule::exists('affirmation_sources', 'id')->where('affirmation_id', $affirmation->id)],
        ]);

        return array_values(array_unique($data['sources'] ?? []));
    }

    /**
     * Règle de présentation des chiffres du cadre : une évolution s'écrit avec ses deux
     * bornes. Heuristique pour le relecteur, jamais bloquante : un pourcentage signé sans
     * deux années distinctes dans la phrase.
     */
    public static function evolutionSansBornes(string $texte): bool
    {
        if (! preg_match('/[+\-−]\s?\d+(?:[,.]\d+)?\s?%/u', $texte)) {
            return false;
        }
        preg_match_all('/\b(?:19|20)\d{2}\b/', $texte, $annees);

        return count(array_unique($annees[0])) < 2;
    }

    /** France et UE-27 à la dernière année, pour lire un graphique sans l'afficher. */
    private static function dernieresValeurs(array $series): array
    {
        $sortie = [];
        foreach (['FR', 'EU27_2020'] as $pays) {
            $dernier = collect($series[$pays] ?? [])->last();
            if ($dernier) {
                $sortie[] = ['pays' => $pays, 'annee' => $dernier['annee'], 'valeur' => $dernier['valeur'], 'statut' => $dernier['statut']];
            }
        }

        return $sortie;
    }
}
