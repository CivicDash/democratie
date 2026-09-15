<?php

namespace App\Services\Presidentielle;

use App\Models\ArgumentMesureLien;
use App\Models\CandidatPresidentielle;
use App\Models\Controverse;
use App\Models\EvenementCampagne;
use App\Models\IngestionProposition;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeTheme;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Génère l'export JSON statique consommé par le front Astro (plan §6).
 * NE SORT QUE le contenu `statut_validation = valide AND affiche_publiquement = true`.
 * `build()` produit la structure (testable) ; `write()` l'écrit sur disque.
 * Le hash de contenu (`meta.content_hash`) est déterministe (indépendant de la date).
 */
class PresidentielleExporter
{
    public function __construct(private IntegriteChecker $integrite, private HatvpSummary $hatvp) {}

    /** @return array<string,mixed> */
    public function build(string $election = '2027'): array
    {
        $themes = ProgrammeTheme::actif()->ordonne()->get();
        $themesExport = $themes->map(fn ($t) => [
            'slug' => $t->slug,
            'nom' => $t->nom,
            'icone' => $t->icone,
            'description' => $t->description,
            'ordre' => $t->ordre,
        ])->values()->all();

        $candidats = CandidatPresidentielle::publie()
            ->where('election', $election)
            ->with([
                'personnePolitique',
                // Publiées (comparateur, plein contenu) ET validées non publiées (« relevées »)
                'mesures' => fn ($q) => $q->where('statut_validation', 'valide')->orderBy('ordre')->with([
                    'theme',
                    // Argumentaire via les liaisons publiées ; le sens est porté par la liaison,
                    // le fait (+ sources + controverse) par l'argument publié.
                    'liens' => fn ($l) => $l->publie()->with([
                        'argument' => fn ($a) => $a->publie()->with(['sources', 'controverse']),
                    ]),
                    'scrutinLiens' => fn ($l) => $l->publie(),
                ]),
                'propositions' => fn ($p) => $p->whereIn('statut', ['detecte', 'validee', 'rattachee'])->with(['theme', 'document']),
                'programmeDocuments' => fn ($d) => $d->publie()->with('items'),
            ])
            ->orderBy('ordre_affichage')
            ->get();

        $candidatsExport = [];
        foreach ($candidats as $candidat) {
            $slug = $candidat->personnePolitique?->slug ?? $candidat->uuid;
            $candidatsExport[$slug] = $this->exportCandidat($candidat, $themesExport);
        }

        $comparateur = $this->buildComparateur($candidatsExport, $themesExport);

        // Controverses publiées : note méthodologique affichée en tête du dépliant pour/contre
        // (les arguments référencent leur controverse par slug dans chaque mesure).
        $controverses = Controverse::publie()->with('theme')->orderBy('ordre')->get()
            ->mapWithKeys(fn ($c) => [$c->slug => [
                'titre' => $c->titre,
                'theme' => $c->theme?->slug,
                'note_methodologique' => $c->note_methodologique,
                'ordre' => $c->ordre,
                'chantier' => $this->chantierControverse($c),
            ]])->all();

        $contenu = [
            'election' => $election,
            'themes' => $themesExport,
            'candidats' => $candidatsExport,
            'comparateur' => $comparateur,
            'controverses' => $controverses,
            'calendrier' => $this->buildCalendrier($election, $candidatsExport),
            // Dans $contenu, donc pris dans le content_hash : publier une question
            // déclenche le rebuild du front. Une clé posée après le « + ['meta' …] »
            // ne serait jamais hachée et pourrait changer sans qu'aucun rebuild ne parte.
            'quiz' => $this->buildQuiz($election),
            'jeu' => $this->buildJeu($election),
        ];

        return $contenu + [
            'meta' => [
                'election' => $election,
                'nb_candidats' => count($candidatsExport),
                'nb_themes' => count($themesExport),
                'content_hash' => $this->hash($contenu),
                'genere_le' => now()->toDateString(),
            ],
        ];
    }

    /**
     * Ajoute le paramètre de temps à une URL YouTube quand le repérage est un timecode.
     *
     * Le repérage vaut soit « 01:57:01 » pour une vidéo, soit « lignes ~1440-1443 » ou
     * « paragraphe … » pour une source écrite : on ne touche qu'au premier cas.
     */
    private function lienHorodate(?string $url, ?string $reperage): ?string
    {
        if (! $url || ! $reperage || ! preg_match('#youtube\.com|youtu\.be#', $url)) {
            return $url;
        }
        if (! preg_match('/^(?:(\d{1,2}):)?(\d{1,2}):(\d{2})$/', trim($reperage), $m)) {
            return $url;
        }

        $secondes = ((int) ($m[1] ?: 0)) * 3600 + ((int) $m[2]) * 60 + (int) $m[3];
        if ($secondes <= 0) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?')."t={$secondes}s";
    }

    /** Nombre de citations retenues par candidat, et seuil d'entrée dans le vivier. */
    private const JEU_PAR_CANDIDAT = 12;

    private const JEU_MINIMUM = 8;

    /**
     * Vivier du jeu « Qui a dit quoi ? ».
     *
     * On y met des CITATIONS VERBATIM, pas les titres de mesures : ces titres sont nos
     * résumés, personne ne les a prononcés. Demander « qui a dit ça » à propos d'une
     * phrase que nous avons écrite serait un contresens, et un contresens sur la seule
     * chose que le jeu prétend enseigner.
     *
     * La question posée est celle de l'AUTEUR d'une phrase, pas du partage d'une opinion :
     * elle a exactement une bonne réponse, y compris quand deux candidats pensent la même
     * chose. C'est ce qui permet de tirer les leurres sans enquêter sur les convergences.
     *
     * Tirage ÉQUILIBRÉ, et c'est l'essentiel : à volume libre, Mélenchon pèserait 20 % du
     * corpus et répondre son nom à chaque carte donnerait autant que le hasard, sans lire.
     * Le jeu mesurerait notre dépouillement. Plafond par candidat, et seuil d'entrée pour
     * écarter ceux dont deux citations feraient un leurre reconnaissable.
     *
     * Ordre déterministe (par identifiant) : le content_hash doit rester stable d'un export
     * à l'autre, sans quoi le front se reconstruirait à chaque passage du cron.
     */
    private function buildJeu(string $election): array
    {
        if (! Schema::hasTable('ingestion_propositions')) {
            return ['election' => $election, 'candidats' => [], 'citations' => []];
        }

        $lignes = DB::table('ingestion_propositions as p')
            ->join('programme_mesures as m', 'm.id', '=', 'p.mesure_id')
            ->join('candidats_presidentielle as c', 'c.id', '=', 'm.candidat_id')
            ->join('personnes_politiques as pp', 'pp.id', '=', 'c.personne_politique_id')
            ->leftJoin('programme_themes as t', 't.id', '=', 'm.theme_id')
            ->leftJoin('ingestion_documents as d', 'd.id', '=', 'p.document_id')
            ->whereNull('m.deleted_at')
            ->where('m.affiche_publiquement', true)
            ->where('c.election', $election)
            ->where('c.affiche_publiquement', true)
            ->whereRaw('length(p.citation_verbatim) between 40 and 180')
            ->orderBy('m.id')
            ->get([
                'p.uuid as ref', 'p.citation_verbatim', 'p.timestamp_ou_paragraphe', 'p.source_url',
                'pp.slug as candidat', 'pp.prenom', 'pp.nom', 'c.couleur_hex', 't.slug as theme',
                'd.titre as doc_titre', 'd.url as doc_url', 'd.date_publication',
            ]);

        $parCandidat = $lignes->groupBy('candidat')
            ->filter(fn ($g) => $g->count() >= self::JEU_MINIMUM);

        $citations = $parCandidat
            ->flatMap(fn ($g) => $g->take(self::JEU_PAR_CANDIDAT))
            ->map(fn ($l) => [
                'ref' => $l->ref,
                'texte' => trim($l->citation_verbatim),
                'candidat' => $l->candidat,
                'theme' => $l->theme,
                'source' => array_filter([
                    'titre' => $l->doc_titre,
                    // Lien pointé à la seconde quand le repérage est un timecode : la
                    // correction devient vérifiable en un clic, au bon moment du discours.
                    'url' => $this->lienHorodate(
                        $this->url($l->source_url) ?? $this->url($l->doc_url),
                        $l->timestamp_ou_paragraphe,
                    ),
                    'date' => $l->date_publication
                        ? \Illuminate\Support\Carbon::parse($l->date_publication)->format('d/m/Y')
                        : null,
                    'reperage' => $l->timestamp_ou_paragraphe,
                ], fn ($v) => $v !== null && $v !== ''),
            ])
            ->values()->all();

        $candidats = $parCandidat
            ->map(fn ($g) => [
                'slug' => $g->first()->candidat,
                'nom' => trim($g->first()->prenom.' '.$g->first()->nom),
                'couleur' => $g->first()->couleur_hex,
            ])
            ->values()
            ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()->all();

        return ['election' => $election, 'candidats' => $candidats, 'citations' => $citations];
    }

    /**
     * Questions de quiz publiées.
     *
     * Le garde n'est pas décoratif : le code peut être déployé avant que la migration ne
     * soit passée. Sans lui, l'export lèverait, `deploy.sh` sortirait en erreur et
     * figerait objectif2027.fr en entier — même mécanique que pour le calendrier.
     *
     * Les candidats figurent dans le JSON : c'est un fichier public, il n'y a rien à
     * cacher. Les masquer pendant que le visiteur répond est un choix d'interface, pour
     * qu'il réponde au contenu et non à l'étiquette — pas un secret.
     */
    private function buildQuiz(string $election): array
    {
        if (! Schema::hasTable('quiz_questions')) {
            return ['election' => $election, 'questions' => []];
        }

        $questions = QuizQuestion::publie()
            ->where('election', $election)
            ->with(['theme', 'controverse', 'options.mesures.candidat.personnePolitique'])
            ->orderBy('ordre')->orderBy('id')
            ->get()
            ->map(function (QuizQuestion $q) {
                $options = $q->options->map(function ($o) {
                    // Seules les mesures publiées comptent : une option adossée à un
                    // brouillon n'aurait aucune position visible derrière elle.
                    $mesures = $o->mesures->where('affiche_publiquement', true);

                    return [
                        'ref' => $o->uuid,
                        'libelle' => $o->libelle,
                        // Une position commune à plusieurs candidats les crédite tous.
                        'candidats' => $mesures
                            ->map(fn ($m) => [
                                'slug' => $m->candidat?->personnePolitique?->slug,
                                'nom' => $m->candidat?->personnePolitique?->nom_complet,
                                'couleur' => $m->candidat?->couleur_hex,
                            ])
                            ->filter(fn ($c) => $c['slug'] !== null)
                            ->unique('slug')->values()->all(),
                        'mesures' => $mesures->map(fn ($m) => [
                            'titre' => $m->titre,
                            'candidat_slug' => $m->candidat?->personnePolitique?->slug,
                            'source_url' => $this->url($m->source_officielle_url),
                        ])->values()->all(),
                    ];
                })->values()->all();

                return [
                    'ref' => $q->uuid,
                    'theme' => $q->theme?->slug,
                    'format' => $q->format,
                    'intitule' => $q->intitule,
                    'precision' => $q->precision_contexte,
                    'controverse' => $q->controverse?->slug,
                    'options' => $options,
                ];
            })
            ->values()->all();

        return ['election' => $election, 'questions' => $questions];
    }

    /** Ne renvoie une URL que si elle est publique et valide (jamais de placeholder). */
    private function url(?string $u): ?string
    {
        if (! $u || str_contains($u, 'A_COMPLETER')) {
            return null;
        }

        return preg_match('#^https?://#i', $u) ? $u : null;
    }

    private function exportCandidat(CandidatPresidentielle $candidat, array $themes): array
    {
        $mesuresParTheme = [];
        $mesuresRelevees = [];   // validées mais argumentaire pour/contre pas encore publié
        foreach ($candidat->mesures as $mesure) {
            $themeSlug = $mesure->theme?->slug ?? 'autre';
            if (! $mesure->affiche_publiquement) {
                // « Relevée » : fait vérifié, affiché a minima (titre + source), sans argumentaire
                $mesuresRelevees[$themeSlug][] = [
                    'titre' => $mesure->titre,
                    'source_url' => $this->url($mesure->source_officielle_url),
                    'date_annonce' => optional($mesure->date_annonce)->toDateString(),
                    'statut' => $mesure->statut_mesure,
                ];

                continue;
            }
            $mesuresParTheme[$themeSlug][] = [
                'titre' => $mesure->titre,
                'resume' => $mesure->resume,
                'chiffrage' => $mesure->chiffrage_annonce,
                'source_url' => $this->url($mesure->source_officielle_url),
                'statut' => $mesure->statut_mesure,
                'date_annonce' => optional($mesure->date_annonce)->toDateString(),
                'mise_en_avant' => (bool) $mesure->est_mise_en_avant,
                'arguments' => [
                    'pour' => $this->exportArguments($mesure->liens->where('sens', 'pour')),
                    'contre' => $this->exportArguments($mesure->liens->where('sens', 'contre')),
                ],
                'en_pratique' => $mesure->scrutinLiens->map(fn ($l) => [
                    'sens' => $l->sens_lien,
                    'niveau' => $l->niveau,
                    'explication' => $l->explication,
                    'scrutin_date' => optional($l->scrutin_date)->toDateString(),
                    'scrutin_intitule' => $l->scrutin_intitule,
                    'scrutin_resultat' => $l->scrutin_resultat,
                    'url' => $this->url($l->scrutin_url),
                ])->values()->all(),
            ];
        }

        // Signaux « en traitement » : propositions rattachées à un thème mais pas encore publiées.
        $enTraitement = [];
        foreach ($candidat->propositions as $p) {
            $slug = $p->theme?->slug;
            if (! $slug) {
                continue;
            }
            $enTraitement[$slug] ??= ['count' => 0, 'source_url' => null];
            $enTraitement[$slug]['count']++;
            $enTraitement[$slug]['source_url'] ??= $p->source_url;
        }

        // État honnête par thème sur TOUT le référentiel (design spec §1.3 + état « relevée »).
        $etatsParTheme = [];
        foreach ($themes as $t) {
            $slug = $t['slug'];
            if (! empty($mesuresParTheme[$slug])) {
                $etatsParTheme[$slug] = ['etat' => 'publie', 'nb_mesures' => count($mesuresParTheme[$slug])];
            } elseif (! empty($mesuresRelevees[$slug])) {
                $etatsParTheme[$slug] = ['etat' => 'relevee', 'nb_mesures' => count($mesuresRelevees[$slug])];
            } elseif (! empty($enTraitement[$slug])) {
                $etatsParTheme[$slug] = [
                    'etat' => 'en_traitement',
                    'nb_signaux' => $enTraitement[$slug]['count'],
                    'source_url' => $this->url($enTraitement[$slug]['source_url']),
                ];
            } else {
                $etatsParTheme[$slug] = ['etat' => 'non_exprime'];
            }
        }

        $themesPublies = count(array_filter($etatsParTheme, fn ($e) => $e['etat'] === 'publie'));
        $themesExprimes = count(array_filter($etatsParTheme, fn ($e) => $e['etat'] !== 'non_exprime'));

        return [
            'slug' => $candidat->personnePolitique?->slug,
            'nom_complet' => $candidat->personnePolitique?->nom_complet,
            'slogan' => $candidat->slogan,
            'parti_soutien' => $candidat->parti_soutien,
            'nuance' => $candidat->nuance_politique,
            'couleur_hex' => $candidat->couleur_hex,
            'photo' => ($candidat->photo_url && $candidat->photo_credit && $candidat->photo_licence) ? [
                'url' => $this->url($candidat->photo_url),
                'credit' => $candidat->photo_credit,
                'licence' => $candidat->photo_licence,
            ] : null,
            'hero_banner' => ($candidat->hero_banner_url && $candidat->hero_credit && $candidat->hero_licence) ? [
                'url' => $this->url($candidat->hero_banner_url),
                'credit' => $candidat->hero_credit,
                'licence' => $candidat->hero_licence,
            ] : null,
            'statut_candidature' => $candidat->statut_candidature,
            'date_declaration' => optional($candidat->date_declaration)->toDateString(),
            'site_campagne_url' => $this->url($candidat->site_campagne_url),
            'programme_url_officiel' => $this->url($candidat->programme_url_officiel),
            'reseaux' => array_filter([
                'site' => $this->url($candidat->personnePolitique?->site_web),
                'x' => $this->url($candidat->personnePolitique?->twitter_url),
                'instagram' => $this->url($candidat->personnePolitique?->instagram_url),
                'facebook' => $this->url($candidat->personnePolitique?->facebook_url),
                'mastodon' => $this->url($candidat->personnePolitique?->mastodon_url),
                'bluesky' => $this->url($candidat->personnePolitique?->bluesky_url),
                'linkedin' => $this->url($candidat->personnePolitique?->linkedin_url),
                'youtube' => $this->url($candidat->personnePolitique?->youtube_url),
                'tiktok' => $this->url($candidat->personnePolitique?->tiktok_url),
            ]),
            'couverture' => [
                'themes_publies' => $themesPublies,
                'themes_exprimes' => $themesExprimes,
                'themes_total' => count($themes),
            ],
            'etats_par_theme' => $etatsParTheme,
            'parcours' => $candidat->personnePolitique
                ? $candidat->personnePolitique->parcoursEvenements()->publie()->chronologique()
                    ->with(['actions' => fn ($q) => $q->publie()->orderBy('date_action')])
                    ->get()->map(fn ($e) => [
                        'type' => $e->type,
                        'titre' => $e->titre,
                        'organisation' => $e->organisation,
                        'date_debut' => optional($e->date_debut)->toDateString(),
                        'date_fin' => optional($e->date_fin)->toDateString(),
                        'source_url' => $this->url($e->source_url),
                        // « Actions durant cette fonction » — données CivicDash, critère mécanique
                        'actions' => $e->actions->map(fn ($a) => [
                            'type' => $a->type,
                            'titre' => $a->titre_court,
                            'date' => optional($a->date_action)->toDateString(),
                            'explication' => $a->explication,
                            'critere' => $a->critere,
                            'source_url' => $this->url($a->source_url),
                        ])->values()->all(),
                    ])->values()->all()
                : [],
            'mesures_par_theme' => $mesuresParTheme,
            'mesures_relevees_par_theme' => $mesuresRelevees,
            'programme_complet' => $this->exportProgrammeComplet($candidat),
            'prises_de_parole' => $this->exportPrisesDeParole($candidat),
            'affaires' => $this->exportAffaires($candidat),
            'hatvp' => $this->exportHatvp($candidat),
        ];
    }

    /**
     * Déclarations d'intérêts HATVP (DIA) — résumé « façon CivicDash » + graphe revenus.
     * Le détail n'est exposé QUE si le rattachement a été validé au BO (hatvp_statut = 'lie') ;
     * sinon on ne renvoie que l'état honnête. Le patrimoine (DSP) n'est jamais repris.
     */
    private function exportHatvp(CandidatPresidentielle $candidat): array
    {
        $statut = $candidat->hatvp_statut ?? 'a_verifier';
        $personne = $candidat->personnePolitique;

        if ($statut !== 'lie' || ! $personne) {
            return ['statut' => $statut];
        }

        $data = $this->hatvp->pourPersonne($personne);

        return [
            'statut' => 'lie',
            'url' => $data['declarations'][0]['url'] ?? null,
            'derniere_declaration' => $data['declarations'][0]['date_depot'] ?? null,
            'summary' => $data['summary'],
        ];
    }

    private const STATUT_JUDICIAIRE_LABEL = [
        'en_cours' => 'Procédure en cours',
        'mis_en_examen' => 'Mise en examen',
        'condamne_premiere_instance' => 'Condamnation en première instance — appel possible',
        'condamne_appel' => 'Condamnation en appel — pourvoi en cours',
        'condamne_definitif' => 'Condamnation définitive',
        'relaxe' => 'Relaxe', 'acquitte' => 'Acquittement',
        'non_lieu' => 'Non-lieu', 'prescrit' => 'Prescrit', 'amnistie' => 'Amnistie',
    ];

    /**
     * Volet affaires judiciaires (composant le plus sensible).
     * `publiees` : affaires valides + affichées, avec présomption d'innocence tant que
     * la condamnation n'est pas définitive. `en_verification` : une affaire est dans le
     * circuit de modération mais pas publiée → le front n'affiche PAS « aucune affaire ».
     */
    private function exportAffaires(CandidatPresidentielle $candidat): array
    {
        $personne = $candidat->personnePolitique;
        $verif = optional($candidat->revue_judiciaire_at)->toDateString();
        if (! $personne) {
            return ['publiees' => [], 'en_verification' => false, 'derniere_verification' => $verif];
        }

        $publiees = $personne->affairesJudiciaires()
            ->where('statut_validation', 'valide')->where('affiche_publiquement', true)
            ->with('sources')->orderBy('ordre_affichage')->get()
            ->map(function ($a) {
                $raw = $a->detection_raw_data ?? [];

                return [
                    'titre' => $a->titre,
                    'statut_judiciaire' => $a->statut_judiciaire,
                    'statut_label' => self::STATUT_JUDICIAIRE_LABEL[$a->statut_judiciaire] ?? $a->statut_judiciaire,
                    'presomption_innocence' => $a->statut_judiciaire !== 'condamne_definitif',
                    'aucune_mise_en_examen' => $a->statut_judiciaire === 'en_cours',
                    'description' => $a->description,
                    'autorite' => $a->juridiction,
                    'qualifications' => $raw['qualifications_parquet'] ?? [],
                    'position_interesse' => $raw['position_interesse'] ?? null,
                    'sources' => $a->sources->map(fn ($s) => [
                        'media' => $s->media,
                        'url' => $this->url($s->url),
                        'archive_url' => $this->url($s->archive_url),
                        'date' => optional($s->date_publication)->toDateString(),
                        'fiabilite' => $s->fiabilite,
                    ])->values()->all(),
                ];
            })->values()->all();

        $enVerification = $personne->affairesJudiciaires()
            ->where('affiche_publiquement', false)
            ->whereIn('statut_validation', ['detecte', 'en_review', 'a_completer'])
            ->exists();

        return ['publiees' => $publiees, 'en_verification' => $enVerification, 'derniere_verification' => $verif];
    }

    /**
     * Chronologie des prises de parole (documents d'ingestion) + citations verbatim
     * avec timecode vérifiable (deep-link vidéo &t=Ns). Le différenciateur du site :
     * chaque citation est vérifiable à la source en un clic.
     */
    /**
     * Bandeau « programme complet — consulter » (plan §11.5) : référentiel officiel
     * validé/publié, rendu en chapitres + liens d'ancre vers le texte officiel.
     */
    private function exportProgrammeComplet(CandidatPresidentielle $candidat): ?array
    {
        $doc = $candidat->programmeDocuments->first();
        if (! $doc) {
            return null;
        }

        $chapitres = collect($doc->structure ?? [])->map(function ($ch) use ($doc) {
            $numero = $ch['numero'] ?? null;

            return [
                'numero' => $numero,
                'titre' => $ch['titre'] ?? '',
                'sous_pages' => $doc->items->where('chapitre_numero', $numero)->map(fn ($i) => [
                    'titre' => $i->titre,
                    'url' => $this->url($i->url_ancre),
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'titre' => $doc->titre,
            'url' => $this->url($doc->url),
            'nb_chapitres' => count($chapitres),
            'nb_entrees' => $doc->items->count(),
            'chapitres' => $chapitres,
        ];
    }

    private function exportPrisesDeParole(CandidatPresidentielle $candidat): array
    {
        $parDocument = [];
        foreach ($candidat->propositions as $p) {
            $doc = $p->document;
            if (! $doc) {
                continue;
            }
            $parDocument[$doc->id] ??= [
                'titre' => $doc->titre,
                'type' => $doc->type,
                'date' => optional($doc->date_publication)->toDateString(),
                'url' => $this->url($doc->url),
                'nb' => 0,
                'citations' => [],
            ];
            $secondes = $this->timecodeEnSecondes($p->timestamp_ou_paragraphe);
            $urlSource = $this->url($p->source_url) ?? $this->url($doc->url);
            $parDocument[$doc->id]['nb']++;
            $parDocument[$doc->id]['citations'][] = [
                'theme' => $p->theme?->slug,
                'resume' => $p->resume_propose,
                'citation' => $this->tronqueMots((string) $p->citation_verbatim, 15),
                'timecode' => $p->timestamp_ou_paragraphe,
                'timestamp_s' => $secondes,
                'verifier_url' => $this->deeplinkVideo($urlSource, $secondes),
                'statut' => $p->statut,
            ];
        }

        return array_values($parDocument);
    }

    /** "01:10" (h:m) ou "01:10:30" (h:m:s) -> secondes. Null si non parsable. */
    private function timecodeEnSecondes(?string $tc): ?int
    {
        if (! $tc || ! preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', trim($tc))) {
            return null;
        }
        $parts = array_map('intval', explode(':', trim($tc)));

        return count($parts) === 3
            ? $parts[0] * 3600 + $parts[1] * 60 + $parts[2]
            : $parts[0] * 3600 + $parts[1] * 60; // format meeting h:mm (discours > 40 min)
    }

    /** Deep-link vidéo avec timecode ; sinon renvoie l'URL telle quelle. */
    private function deeplinkVideo(?string $url, ?int $secondes): ?string
    {
        if (! $url) {
            return null;
        }
        if ($secondes !== null && preg_match('#youtube\.com/watch\?v=|youtu\.be/#', $url)) {
            return $url.(str_contains($url, '?') ? '&' : '?').'t='.$secondes.'s';
        }

        return $url;
    }

    /**
     * Travail en cours sur une controverse, en VOLUME seulement — jamais un nom, jamais un
     * contenu. Même principe que `affaires.en_verification` : le site peut annoncer
     * honnêtement son avancement sans publier une liaison non encore doublement validée.
     *
     * Sous le seuil de deux candidats on ne renvoie rien : un compteur à 1, croisé avec la
     * page thème, désignerait le candidat dont la position est en cours d'instruction.
     */
    private const TYPE_EVT = [
        'meeting' => ['Meeting', '🎤', '#1d4ed8'],
        'debat' => ['Débat', '🎙', '#7c3aed'],
        'discours' => ['Discours', '📣', '#0f766e'],
        'interview' => ['Interview', '🎬', '#b45309'],
        'emission' => ['Émission', '📺', '#be123c'],
        'deplacement' => ['Déplacement', '🚩', '#4d7c0f'],
        'communique' => ['Communiqué', '📄', '#475569'],
        'autre' => ['Prise de parole', '📅', '#64748b'],
    ];

    /**
     * Calendrier des prises de parole. Volontairement SANS champ dérivé de now() : le hash
     * de contenu doit rester déterministe, et sur un site statique reconstruit seulement
     * quand le contenu change, un « à venir » calculé au build se périmerait en silence.
     * Le passé/futur se calcule côté client.
     *
     * Les citations ne sont pas inlinées — un seul débat en porte 262 : on n'expose que le
     * volume et le lien vers la fiche candidat, où elles sont déjà timecodées.
     */
    private function buildCalendrier(string $election, array $candidatsExport): array
    {
        // Le code peut être déployé avant que la migration ne soit passée : sans ce garde,
        // l'export lèverait, deploy.sh sortirait en erreur et figerait le site entier.
        if (! Schema::hasTable('evenements_campagne')) {
            return ['election' => $election, 'evenements' => []];
        }

        $evenements = EvenementCampagne::where('election', $election)->publie()
            ->with(['candidats.personnePolitique', 'document'])
            ->chronologique()->get();

        return ['election' => $election, 'evenements' => $evenements->map(function (EvenementCampagne $e) use ($candidatsExport) {
            [$label, $icone, $couleur] = self::TYPE_EVT[$e->type] ?? self::TYPE_EVT['autre'];

            $nbParCandidat = $e->ingestion_document_id
                ? IngestionProposition::where('document_id', $e->ingestion_document_id)
                    ->whereNotNull('candidat_id')->selectRaw('candidat_id, count(*) as n')
                    ->groupBy('candidat_id')->pluck('n', 'candidat_id')->all()
                : [];

            // Ne jamais faire fuiter par le calendrier un candidat non publié.
            $intervenants = $e->candidats
                ->filter(fn ($c) => isset($candidatsExport[$c->personnePolitique?->slug]))
                ->sortBy(fn ($c) => $c->personnePolitique->nom)
                ->map(fn ($c) => [
                    'slug' => $c->personnePolitique->slug,
                    'nom' => trim(($c->personnePolitique->prenom ?? '').' '.($c->personnePolitique->nom ?? '')),
                    'couleur_hex' => $c->couleur_hex,
                    'role' => $c->pivot->role,
                    'nb_citations' => (int) ($nbParCandidat[$c->id] ?? 0),
                ])->values()->all();

            return [
                'id' => 'evt-'.$e->id,
                'uid' => "o2027-{$e->uuid}@objectif2027.fr",
                // Clés au format FullCalendar : le jour où une grille arrive, aucun adaptateur.
                'title' => $e->titre,
                'start' => $e->journee_entiere ? $e->date_debut->toDateString() : $e->date_debut->toIso8601String(),
                'end' => $e->date_fin ? ($e->journee_entiere ? $e->date_fin->toDateString() : $e->date_fin->toIso8601String()) : null,
                'allDay' => (bool) $e->journee_entiere,
                'color' => $couleur,
                'precision_date' => $e->precision_date,
                'type' => $e->type,
                'type_label' => $label,
                'icon' => $icone,
                'statut' => $e->statut,
                'lieu' => $e->lieu,
                'ville' => $e->ville,
                'organisateur' => $e->organisateur,
                'media' => $e->media,
                'description' => $e->description,
                'candidats' => $intervenants,
                'nb_citations' => array_sum($nbParCandidat),
                'urlVideo' => $this->url($e->url_video),
                'urlSource' => $this->url($e->url_source),
                'archive_url' => $this->url($e->archive_url),
                'source' => $e->document
                    ? ['type' => $e->document->type, 'duree_s' => $e->document->duree_s, 'a_ete_depouille' => true]
                    : ['a_ete_depouille' => false],
                'note_methodologique' => $e->note_methodologique,
            ];
        })->values()->all()];
    }

    private function chantierControverse(Controverse $c): ?array
    {
        $liens = ArgumentMesureLien::whereIn('argument_id', $c->arguments()->select('id'))
            ->whereNotNull('mesure_id')
            ->where(fn ($q) => $q->where('affiche_publiquement', false)
                ->orWhere('statut_validation', '!=', 'valide'))
            ->with('mesure:id,candidat_id')
            ->get();

        $candidats = $liens->pluck('mesure.candidat_id')->filter()->unique()->count();

        return $candidats >= 2
            ? ['candidats' => $candidats, 'mesures' => $liens->pluck('mesure_id')->unique()->count()]
            : null;
    }

    private function tronqueMots(string $txt, int $max): string
    {
        $mots = preg_split('/\s+/', trim($txt));

        return count($mots) <= $max ? $txt : implode(' ', array_slice($mots, 0, $max)).' …';
    }

    /**
     * @param  \Illuminate\Support\Collection  $liens  liaisons publiées d'un même sens
     */
    private function exportArguments($liens): array
    {
        return $liens
            ->filter(fn ($l) => $l->argument && $l->argument->affiche_publiquement)
            ->map(fn ($l) => [
                'ref' => $l->argument->uuid,                 // même ref = même fait partagé entre mesures
                'titre' => $l->argument->titre,
                'contenu' => $l->argument->contenu,
                'type' => $l->argument->type_argument,
                'note_contextuelle' => $l->note_contextuelle, // pourquoi ce fait joue dans ce sens ICI
                'controverse' => $l->argument->controverse && $l->argument->controverse->affiche_publiquement
                    ? $l->argument->controverse->slug : null,
                'sources' => $l->argument->sources->map(fn ($s) => [
                    'type' => $s->type_source,
                    'titre' => $s->titre,
                    'url' => $this->url($s->url),
                    'media' => $s->media,
                    // L'auteur nommé et la date font partie de l'argument, pas du décor :
                    // une évaluation de 2001 et une de 2024 ne pèsent pas pareil, et « le
                    // Conseil d'analyse économique » vaut mieux que « des économistes ».
                    'auteur' => $s->auteur,
                    'date' => optional($s->date_publication)->toDateString(),
                    // Verbatim d'une publication tierce : même prudence que pour les
                    // citations de meeting (droit de courte citation).
                    'extrait' => $s->extrait ? $this->tronqueMots($s->extrait, 25) : null,
                    'archive_url' => $this->url($s->archive_url),
                    'fiabilite' => $s->fiabilite,
                ])->values()->all(),
            ])->values()->all();
    }

    /** Matrice thème → candidat → mesures mises en avant, pour le comparateur. */
    private function buildComparateur(array $candidatsExport, array $themes): array
    {
        $matrice = [];
        foreach ($themes as $theme) {
            $ligne = [];
            foreach ($candidatsExport as $slug => $data) {
                $mesures = $data['mesures_par_theme'][$theme['slug']] ?? [];
                $ligne[$slug] = array_values(array_filter($mesures, fn ($m) => $m['mise_en_avant'])) ?: $mesures;
            }
            $matrice[$theme['slug']] = $ligne;
        }

        return $matrice;
    }

    private function hash(array $contenu): string
    {
        return hash('sha256', json_encode($contenu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Écrit l'export sur disque : un fichier par candidat + themes.json +
     * comparateur.json + meta.json. Retourne la liste des chemins écrits.
     *
     * @return array<int,string>
     */
    public function write(array $data, string $dir): array
    {
        File::ensureDirectoryExists($dir.'/candidats');
        $ecrits = [];

        $ecrits[] = $this->put("{$dir}/themes.json", $data['themes']);
        $ecrits[] = $this->put("{$dir}/comparateur.json", $data['comparateur']);
        $ecrits[] = $this->put("{$dir}/meta.json", $data['meta']);
        // Controverses : sans ce fichier, le slug référencé par chaque argument pointe dans le
        // vide et la note méthodologique — qui explique pourquoi les deux camps citent des
        // données exactes — n'atteint jamais le front.
        $ecrits[] = $this->put("{$dir}/controverses.json", $data['controverses']);
        $ecrits[] = $this->put("{$dir}/calendrier.json", $data['calendrier']);
        $ecrits[] = $this->put("{$dir}/quiz.json", $data['quiz']);
        $ecrits[] = $this->put("{$dir}/jeu.json", $data['jeu']);

        foreach ($data['candidats'] as $slug => $candidat) {
            $ecrits[] = $this->put("{$dir}/candidats/{$slug}.json", $candidat);
        }

        // Index des candidats (léger) pour la grille /candidats
        $index = array_map(fn ($c) => [
            'slug' => $c['slug'],
            'nom_complet' => $c['nom_complet'],
            'parti_soutien' => $c['parti_soutien'],
            'nuance' => $c['nuance'],
            'couleur_hex' => $c['couleur_hex'],
            'statut_candidature' => $c['statut_candidature'],
            'couverture' => $c['couverture'],
        ], array_values($data['candidats']));
        $ecrits[] = $this->put("{$dir}/candidats.json", $index);

        return $ecrits;
    }

    private function put(string $path, mixed $content): string
    {
        File::put($path, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
