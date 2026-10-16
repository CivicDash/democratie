<?php

namespace App\Services\Presidentielle;

use App\Models\Affirmation;
use App\Models\ArgumentMesureLien;
use App\Models\CadrageBudgetaire;
use App\Models\CadrageLigne;
use App\Models\CandidatPresidentielle;
use App\Models\Controverse;
use App\Models\EurostatIndicateur;
use App\Models\EvenementCampagne;
use App\Models\IngestionProposition;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeTheme;
use App\Models\ProgrammeMesure;
use App\Models\QuizQuestion;
use App\Support\Locuteur;
use App\Support\Porteur;
use App\Support\UrlSource;
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
    /**
     * Colonnes du locuteur et du porteur : le code peut être déployé avant la migration.
     * Sans elles, tout se lit comme aujourd'hui (le candidat seul, porteur « candidat »).
     */
    private bool $avecLocuteur = false;

    private bool $avecPorteur = false;

    public function __construct(private IntegriteChecker $integrite, private HatvpSummary $hatvp) {}

    /** @return array<string,mixed> */
    public function build(string $election = '2027'): array
    {
        $this->avecLocuteur = Schema::hasColumn('ingestion_propositions', 'qualite_locuteur');
        $this->avecPorteur = Schema::hasColumn('ingestion_documents', 'porteur_type')
            && Schema::hasColumn('programme_documents', 'porteur_type');

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
                'mesures' => fn ($q) => $q->where('statut_validation', 'valide')->orderBy('ordre')->orderBy('id')->with([
                    'theme',
                    // Citations d'appui et locuteurs : une mesure de l'équipe de campagne
                    // reste attribuée à qui l'a formulée.
                    'propositionsSource' => fn ($p) => $p->orderBy('id')->with('document'),
                    // Argumentaire via les liaisons publiées ; le sens est porté par la liaison,
                    // le fait (+ sources + controverse) par l'argument publié.
                    'liens' => fn ($l) => $l->publie()->with([
                        'argument' => fn ($a) => $a->publie()->with(['sources', 'controverse']),
                    ]),
                    'scrutinLiens' => fn ($l) => $l->publie(),
                ]),
                // Seul le relu paraît (validée ou rattachée), et seulement ce que disent le
                // candidat et son équipe : la parole d'un parti va dans « Autour du candidat ».
                // Ce filtre fait aussi foi pour etats_par_theme et couverture.
                'propositions' => fn ($p) => $this->duCandidat($p->publique())->orderBy('id')->with(['theme', 'document']),
                // Trié : avec deux référentiels publiés, le premier doit être toujours le même.
                'programmeDocuments' => fn ($d) => $d->publie()->orderBy('id')->with('items'),
            ])
            ->orderBy('ordre_affichage')
            // Départage stable : à ordre égal, l'ordre des clés ne doit pas dépendre du plan
            // de requête (il entre dans le content_hash).
            ->orderBy('id')
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
            'reperes' => $this->buildAffirmations($election),
            'budgets' => $this->buildBudgets($election, $candidats),
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
    private const JEU_PAR_CANDIDAT = 10;

    private const JEU_MINIMUM = 10;

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
            // « Qui a dit quoi ? » demande l'AUTEUR d'une phrase : une phrase d'Éric
            // Coquerel n'a pas pour réponse Jean-Luc Mélenchon. Aucune phrase d'un autre
            // locuteur que le candidat, ni d'un document de parti, n'entre au jeu.
            ->when($this->avecLocuteur, fn ($q) => $q->whereNull('p.locuteur'))
            ->when($this->avecPorteur, fn ($q) => $q->where(fn ($w) => $w->whereNull('d.porteur_type')
                ->orWhereNotIn('d.porteur_type', Porteur::ENTOURAGE)))
            // Une citation ne devient jouable que si c'est une PHRASE ENTIÈRE. Le seul
            // filtre de longueur laissait passer des fragments coupés au milieu — « Ça
            // veut dire créer un prêt long terme à ta zéro pour » — indevinables, et qui
            // donnent en prime une fausse idée de ce que le candidat a dit.
            //
            // Deux signaux structurels suffisent et ne coûtent aucun jugement : la phrase
            // commence par une majuscule, et se termine par une ponctuation forte. Ils
            // écartent 74 % du corpus, ce qui est le prix à payer : il reste neuf
            // candidats et de dix à vingt et une phrases chacun, assez pour une manche.
            ->whereRaw("p.citation_verbatim ~ '^[A-ZÀÉÈÊÎÔÛÇ]'")
            ->whereRaw("p.citation_verbatim ~ '[.!?]\\s*$'")
            ->whereRaw('length(p.citation_verbatim) between 60 and 220')
            // Le résumé accompagne la citation dans le jeu : il ne doit nommer personne.
            // Deux résumés du corpus citent un adversaire (« S'oppose à la position de
            // Jean-Luc Mélenchon… ») — cela ne donne pas la réponse mais rétrécit le champ.
            ->whereRaw("m.titre !~* '(m[ée]lenchon|le pen|zemmour|attal|philippe|retailleau|ruffin|tondelier|glucksmann|villepin|roussel|arthaud|bardella|macron|faure|royal|guedj|cazeneuve|hollande|bertrand|lisnard)'")
            ->orderBy('m.id')
            ->get([
                'p.uuid as ref', 'p.citation_verbatim', 'p.timestamp_ou_paragraphe', 'p.source_url',
                'pp.slug as candidat', 'pp.prenom', 'pp.nom', 'c.couleur_hex', 't.slug as theme',
                'm.titre as contexte',
                'd.titre as doc_titre', 'd.url as doc_url', 'd.date_publication',
            ]);

        $parCandidat = $lignes->groupBy('candidat')
            ->filter(fn ($g) => $g->count() >= self::JEU_MINIMUM);

        $citations = $parCandidat
            ->flatMap(fn ($g) => $g->take(self::JEU_PAR_CANDIDAT))
            ->map(fn ($l) => [
                'ref' => $l->ref,
                'texte' => trim($l->citation_verbatim),
                // Notre résumé neutre, en discours indirect : il rend la phrase
                // compréhensible hors de son contexte sans nommer son auteur. Sans lui,
                // un fragment comme « la capitalisation, c'est pas pour aujourd'hui »
                // reste indevinable même complet.
                'contexte' => trim((string) $l->contexte),
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
                // Clé de tri : nom puis prénom, comme partout ailleurs.
                'tri' => trim($g->first()->nom.' '.$g->first()->prenom),
                'couleur' => $g->first()->couleur_hex,
            ])
            ->values()
            ->sortBy(fn ($x) => $x['tri'], SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($x) => \Illuminate\Support\Arr::except($x, 'tri'))
            ->values()->all();

        return ['election' => $election, 'candidats' => $candidats, 'citations' => $citations];
    }

    /**
     * Repères chiffrés publiés (ex-« Ce qu'on entend »), et les séries Eurostat de leurs
     * graphiques. Un repère répond à une question neutre, sans verdict : ni jauge, ni
     * « vrai / faux » ne sortent d'ici, et l'affirmation d'origine (enonce) non plus.
     *
     * Même garde que le quiz : le code peut précéder la migration, et un export qui lève
     * fige tout le site.
     *
     * Ce qui ne sort JAMAIS d'ici, et que AffirmationExportTest vérifie : les verdicts et
     * l'énoncé d'origine, la coloration politique perçue, les notes de
     * vérification, l'identité des modérateurs, et le texte des phrases non vérifiées
     * (seul leur nombre part, par section). Seules les sources citées par une phrase
     * vérifiée partent, et seules les séries Eurostat relues — `series_publiees`, jamais
     * ce que la dernière extraction a trouvé.
     */
    private function buildAffirmations(string $election): array
    {
        $vide = ['election' => $election, 'legende_statuts' => config('eurostat.legende_statuts'), 'reperes' => [], 'indicateurs' => (object) []];
        if (! Schema::hasTable('affirmations')) {
            return $vide;
        }

        $fiches = Affirmation::publie()
            ->where('election', $election)
            ->with(['theme', 'themesSecondaires', 'constats.sources', 'graphiques'])
            ->get()
            ->sortBy(fn ($f) => [$f->theme?->ordre ?? 99, $f->question])
            ->values();

        $codes = $fiches->flatMap(fn ($f) => $f->graphiquesAffiches()->flatMap(fn ($g) => (array) $g->indicateurs))->unique()->values();
        $indicateurs = EurostatIndicateur::whereIn('code', $codes)->whereNotNull('series_publiees')->orderBy('code')->get()
            ->mapWithKeys(fn (EurostatIndicateur $i) => [$i->code => [
                'titre' => $i->titre,
                'unite' => $i->unite,
                'note_methodo' => $i->note_methodo,
                'sources' => $i->sources,
                'extraction' => $i->extraction_publiee?->toDateString(),
                'series' => $i->series_publiees,
            ]])->all();

        $affirmations = $fiches->map(function (Affirmation $f) {
            $constats = $f->constatsAffiches();
            $citees = $constats->flatMap->sources->unique('id')->sortBy('id')->values();

            return [
                'slug' => $f->slug,
                'question' => $f->question,
                'resume' => $f->resume,
                'theme' => $f->theme?->slug,
                'themes_secondaires' => $f->themesSecondaires->pluck('slug')->values()->all(),
                'derniere_verification' => $f->derniere_verification?->toDateString(),
                'constats' => $constats->map(fn ($c) => [
                    'id' => $c->id,
                    'section' => $c->section,
                    'groupe' => $c->groupe,
                    'texte' => $c->texte,
                    'sources' => $c->sources->sortBy('id')->pluck('cle')->values()->all(),
                ])->values()->all(),
                'a_sourcer' => (object) $f->aSourcer(),
                'sources' => $citees->map(fn ($s) => [
                    'cle' => $s->cle,
                    'producteur' => $s->producteur,
                    'titre' => $s->titre,
                    'url' => $this->url($s->url),
                    'archive_url' => $this->url($s->archive_url),
                    'categorie' => $s->categorie,
                    'date_publication' => $s->date_publication?->toDateString(),
                    'date_consultation' => $s->date_consultation?->toDateString(),
                ])->all(),
                'graphiques' => $f->graphiquesAffiches()->map(fn ($g) => [
                    'constat' => $g->constat_id,
                    'type' => $g->type,
                    'titre' => $g->titre,
                    'sous_titre' => $g->sous_titre,
                    'indicateurs' => array_values((array) $g->indicateurs),
                    'options' => $g->options ?? (object) [],
                    'note' => $g->note,
                ])->values()->all(),
            ];
        })->all();

        return ['reperes' => $affirmations, 'indicateurs' => $indicateurs ?: (object) []] + $vide;
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
        return UrlSource::nettoyer($u);
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
                    'porte_par' => $this->portePar($mesure),
                    'citations' => $this->citationsAppui($mesure),
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
                'porte_par' => $this->portePar($mesure),
                'citations' => $this->citationsAppui($mesure),
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
            // « Mme » ou « M. » : de quoi accorder « Autour de la candidate » sans lire
            // le nom complet.
            'civilite' => $candidat->personnePolitique?->civilite,
            // Nom et prénom séparés : l'ordre alphabétique se fait sur le NOM, et
            // `nom_complet` ne permet pas de le retrouver de façon fiable — « Dominique
            // de Villepin », « Nicolas Dupont-Aignan », « Jean-Luc Mélenchon » se
            // découpent chacun autrement. Il porte en outre la civilité, qui triait
            // « M. » et « Mme » comme du texte et regroupait les candidates entre elles.
            'nom' => $candidat->personnePolitique?->nom,
            'prenom' => $candidat->personnePolitique?->prenom,
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
                        // jour, mois ou année : le front n'affiche que ce qu'on sait.
                        'precision_debut' => $e->precision_debut ?? 'jour',
                        'precision_fin' => $e->precision_fin ?? 'jour',
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
            'autour_du_candidat' => $this->exportAutour($candidat),
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
                    'chronologie' => $this->chronologieAffaire($raw['chronologie'] ?? []),
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
     * Étapes de la procédure, telles que saisies dans `detection_raw_data.chronologie`.
     * Seuls la date (AAAA, AAAA-MM ou AAAA-MM-JJ), le fait et la source sortent : les
     * notes de modération voisines (rappel_statut, taches_moderation…) restent internes.
     */
    private function chronologieAffaire(mixed $etapes): array
    {
        if (! is_array($etapes)) {
            return [];
        }

        return collect($etapes)
            ->filter(fn ($e) => is_array($e)
                && preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', (string) ($e['date'] ?? ''))
                && trim((string) ($e['fait'] ?? '')) !== '')
            ->map(fn ($e) => [
                'date' => $e['date'],
                'fait' => trim($e['fait']),
                'source' => $this->url($e['source'] ?? null),
            ])
            ->values()->all();
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
        // Le référentiel d'un parti ou d'un groupe n'est pas le programme du candidat : il
        // paraît dans « Autour du candidat ».
        $doc = $candidat->programmeDocuments->first(fn ($d) => ! $this->avecPorteur || ! $d->porteurEstEntourage());
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
            'porteur' => $this->porteur($doc),
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
                'porteur' => $this->porteur($doc),
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
            ] + $this->attribution($p);
        }

        return array_values($parDocument);
    }

    /**
     * Le candidat et son équipe de campagne, jamais la parole d'un parti. Même critère que
     * IngestionProposition::scopeDuCandidat(), appliqué seulement aux colonnes présentes.
     */
    private function duCandidat($query)
    {
        if ($this->avecLocuteur) {
            $query->where(fn ($q) => $q->whereNull('qualite_locuteur')->orWhere('qualite_locuteur', '!=', Locuteur::PARTI));
        }
        if ($this->avecPorteur) {
            $query->whereDoesntHave('document', fn ($d) => $d->whereIn('porteur_type', Porteur::ENTOURAGE));
        }

        return $query;
    }

    /** Qui parle, sur chaque citation : nul pour le candidat lui-même. */
    private function attribution(IngestionProposition $p): array
    {
        return $this->avecLocuteur
            ? ['locuteur' => $p->locuteur, 'role_locuteur' => $p->role_locuteur, 'qualite_locuteur' => $p->qualite_locuteur]
            : ['locuteur' => null, 'role_locuteur' => null, 'qualite_locuteur' => null];
    }

    /** Porteur d'un document : {type, libelle, endossement}. */
    private function porteur($document): array
    {
        return $this->avecPorteur
            ? $document->porteurExport()
            : ['type' => 'candidat', 'libelle' => null, 'endossement' => null];
    }

    /**
     * Mesure portée par l'équipe de campagne : toutes ses propositions source viennent de
     * personnalités de l'équipe. Leur nom et leur rôle suivent la mesure partout, comparateur
     * compris. Nul dès qu'une proposition source est celle du candidat lui-même.
     */
    private function portePar(ProgrammeMesure $mesure): ?array
    {
        $sources = $mesure->propositionsSource->where('statut', 'rattachee');
        if (! $this->avecLocuteur || $sources->isEmpty()
            || $sources->contains(fn ($p) => $p->qualite_locuteur !== Locuteur::EQUIPE)) {
            return null;
        }

        return [
            'qualite' => Locuteur::EQUIPE,
            'locuteurs' => $sources
                ->map(fn ($p) => ['nom' => $p->locuteur, 'role' => $p->role_locuteur])
                ->unique('nom')
                ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()->all(),
        ];
    }

    /** Citations d'appui d'une mesure : les propositions rattachées, chacune attribuée. */
    private function citationsAppui(ProgrammeMesure $mesure): array
    {
        return $mesure->propositionsSource
            ->filter(fn ($p) => $p->statut === 'rattachee' && trim((string) $p->citation_verbatim) !== '')
            ->map(function ($p) {
                $secondes = $this->timecodeEnSecondes($p->timestamp_ou_paragraphe);

                return [
                    'citation' => $this->tronqueMots((string) $p->citation_verbatim, 15),
                    'timecode' => $p->timestamp_ou_paragraphe,
                    'verifier_url' => $this->deeplinkVideo($this->url($p->source_url) ?? $this->url($p->document?->url), $secondes),
                ] + $this->attribution($p);
            })
            ->values()->all();
    }

    /**
     * « Autour du candidat » : la parole d'un parti ou d'un groupe parlementaire, publiée à
     * côté de la fiche et jamais dedans — ni mesure, ni comparateur, ni jeu, ni couverture.
     * Clés toujours présentes, tableaux vides si rien : la forme reste stable.
     */
    private function exportAutour(CandidatPresidentielle $candidat): array
    {
        $autour = ['personnalites' => [], 'prises_de_parole' => [], 'documents' => []];
        if (! $this->avecPorteur) {
            return $autour;
        }

        $autour['documents'] = $candidat->programmeDocuments
            ->filter(fn ($d) => $d->porteurEstEntourage())
            ->map(fn ($d) => [
                'titre' => $d->titre,
                'url' => $this->url($d->url),
                'porteur' => $d->porteurExport(),
                'nb_chapitres' => count($d->structure ?? []),
                'nb_entrees' => $d->items->count(),
            ])->values()->all();

        if (! $this->avecLocuteur) {
            return $autour;
        }

        $propositions = IngestionProposition::publieAutour()
            ->where('candidat_id', $candidat->id)
            ->with(['theme', 'document'])
            ->orderBy('id')
            ->get();

        $autour['prises_de_parole'] = $propositions
            ->groupBy('document_id')
            ->sortBy(fn ($g) => [optional($g->first()->document?->date_publication)->toDateString() ?? '9999-12-31', $g->first()->document_id])
            ->map(function ($groupe) {
                $doc = $groupe->first()->document;

                return [
                    'titre' => $doc->titre,
                    'type' => $doc->type,
                    'date' => optional($doc->date_publication)->toDateString(),
                    'url' => $this->url($doc->url),
                    'porteur' => $doc->porteurExport(),
                    'citations' => $groupe->map(function ($p) use ($doc) {
                        $secondes = $this->timecodeEnSecondes($p->timestamp_ou_paragraphe);

                        // Le nom du locuteur voyage AVEC la citation : un extrait de recherche
                        // ne doit jamais la détacher de qui l'a prononcée.
                        return $this->attribution($p) + [
                            'theme' => $p->theme?->slug,
                            'resume' => $p->resume_propose,
                            'citation' => $this->tronqueMots((string) $p->citation_verbatim, 15),
                            'timecode' => $p->timestamp_ou_paragraphe,
                            'timestamp_s' => $secondes,
                            'verifier_url' => $this->deeplinkVideo($this->url($p->source_url) ?? $this->url($doc->url), $secondes),
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

        $autour['personnalites'] = $propositions
            ->filter(fn ($p) => filled($p->locuteur))
            ->groupBy('locuteur')
            ->map(fn ($g, $nom) => [
                'nom' => $nom,
                'role' => $g->last()->role_locuteur,
                'qualite' => $g->last()->qualite_locuteur,
                'nb_citations' => $g->count(),
            ])
            ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()->all();

        return $autour;
    }

    /**
     * Cadrages budgétaires (budgets.json) : une grille commune, la référence officielle en
     * regard, et pour chaque candidat publié son état.
     *
     * Ce que l'export ne fait JAMAIS : additionner, rapporter, convertir du cumulé en annuel
     * (un chiffre calculé n'est pas sourcé), ni dériver quoi que ce soit de la date du jour.
     * Seules les lignes vérifiées sortent ; des autres, seul le nombre part.
     */
    private function buildBudgets(string $election, $candidats): array
    {
        $liste = fn (array $libelles) => collect($libelles)->map(fn ($l, $cle) => ['cle' => $cle, 'libelle' => $l])->values()->all();

        $budgets = [
            'election' => $election,
            'theme' => null,
            'rubriques' => $liste(CadrageLigne::GRILLE),
            'rubriques_reference' => $liste(CadrageLigne::REFERENCE_SEULE),
            'natures' => CadrageBudgetaire::NATURES,
            'unites' => CadrageLigne::UNITES,
            'qualificatifs' => CadrageLigne::QUALIFICATIFS,
            'portees' => CadrageLigne::PORTEES,
            'reference' => null,
            'candidats' => (object) [],
        ];
        if (! Schema::hasTable('cadrages_budgetaires')) {
            return $budgets;
        }

        // La plus récente publiée : le PLF, puis la loi de finances une fois votée.
        $reference = CadrageBudgetaire::reference()->publie()->where('election', $election)
            ->chronologieInverse()->with('lignes')->first();
        if ($reference) {
            $budgets['reference'] = [
                'ref' => $reference->uuid,
                'titre' => $reference->titre,
                'date_source' => $reference->date_source?->toDateString(),
                'note_methodologique' => $reference->note_methodologique,
                'rubriques' => $this->rubriquesCadrage($reference, [...array_keys(CadrageLigne::GRILLE), ...array_keys(CadrageLigne::REFERENCE_SEULE)]),
                'postes' => $this->lignesAffichees($reference, CadrageLigne::POSTE),
                'a_verifier' => $reference->lignes->reject->estAffichee()->count(),
            ];
            $theme = config('presidentielle.theme_budgets');
            $budgets['theme'] = $theme && ProgrammeTheme::actif()->where('slug', $theme)->exists() ? $theme : null;
        }

        $cadrages = CadrageBudgetaire::whereIn('candidat_id', $candidats->pluck('id'))
            ->where('election', $election)
            ->with(['lignes.document', 'document', 'programmeDocument'])
            ->chronologieInverse()
            ->get()
            ->groupBy('candidat_id');

        $parCandidat = [];
        foreach ($candidats as $candidat) {
            $slug = $candidat->personnePolitique?->slug ?? $candidat->uuid;
            $siens = $cadrages->get($candidat->id, collect());
            $publies = $siens->filter(fn ($c) => $c->statut_validation === 'valide' && $c->affiche_publiquement);

            // « En traitement » ne livre rien d'autre que l'état : aucun chiffre non vérifié.
            $parCandidat[$slug] = $publies->isNotEmpty()
                ? ['etat' => 'publie', 'cadrages' => $publies->map(fn ($c) => $this->exportCadrage($c))->values()->all()]
                : ['etat' => $siens->isNotEmpty() ? 'en_traitement' : 'non_exprime'];
        }
        $budgets['candidats'] = $parCandidat ?: (object) [];

        return $budgets;
    }

    private function exportCadrage(CadrageBudgetaire $c): array
    {
        $doc = $c->documentSource();

        return [
            'ref' => $c->uuid,
            'nature' => $c->nature,
            'nature_libelle' => CadrageBudgetaire::NATURES[$c->nature] ?? $c->nature,
            'titre' => $c->titre,
            'date_source' => $c->date_source?->toDateString(),
            'source' => $doc ? [
                'titre' => $doc->titre,
                'type' => $doc instanceof \App\Models\ProgrammeDocument ? 'programme' : $doc->type,
                'date' => $doc instanceof \App\Models\IngestionDocument ? optional($doc->date_publication)->toDateString() : null,
                'url' => $this->url($doc->url),
                'porteur' => $this->porteur($doc),
            ] : null,
            'rubriques' => $this->rubriquesCadrage($c, array_keys(CadrageLigne::GRILLE)),
            'postes' => $this->lignesAffichees($c, CadrageLigne::POSTE),
            'a_verifier' => $c->lignes->reject->estAffichee()->count(),
            'note_methodologique' => $c->note_methodologique,
        ];
    }

    /**
     * État de chaque case de la grille : `publie` (au moins une ligne vérifiée),
     * `en_verification` (des lignes existent, aucune n'est vérifiée : dire « non précisé »
     * serait faux, et aucune valeur ne fuit), `non_precise` (la source ne le dit pas).
     */
    private function rubriquesCadrage(CadrageBudgetaire $c, array $cles): array
    {
        $rubriques = [];
        foreach ($cles as $cle) {
            $lignes = $this->lignesAffichees($c, $cle);
            $rubriques[$cle] = $lignes
                ? ['etat' => 'publie', 'lignes' => $lignes]
                : ['etat' => $c->lignes->where('rubrique', $cle)->isNotEmpty() ? 'en_verification' : 'non_precise'];
        }

        return $rubriques;
    }

    private function lignesAffichees(CadrageBudgetaire $c, string $rubrique): array
    {
        return $c->lignes
            ->filter(fn (CadrageLigne $l) => $l->rubrique === $rubrique && $l->estAffichee())
            ->map(fn (CadrageLigne $l) => $this->exportLigne($l, $c))
            ->values()->all();
    }

    private function exportLigne(CadrageLigne $l, CadrageBudgetaire $c): array
    {
        $reference = $c->candidat_id === null;
        $doc = $l->document ?? $c->documentSource();
        $docPropre = ! $reference && $l->ingestion_document_id && $l->ingestion_document_id !== $c->ingestion_document_id;

        return [
            'libelle' => $l->libelle,
            // Arrondi à deux décimales, en nombre : la forme ne dépend que de la valeur saisie.
            'montant' => $l->montant === null ? null : round((float) $l->montant, 2),
            'montant_max' => $l->montant_max === null ? null : round((float) $l->montant_max, 2),
            'unite' => $l->unite,
            'qualificatif' => $l->qualificatif,
            'portee' => $l->portee,
            'annee' => $l->annee,
            'valeur_texte' => $l->valeur_texte,
            'citation' => $this->tronqueMots((string) $l->citation, 40),
            'reperage' => $l->reperage,
            'url' => $reference ? $this->url($l->source_url) : $this->lienHorodate($this->url($doc?->url), $l->reperage),
            'locuteur' => $l->locuteur,
            'role_locuteur' => $l->role_locuteur,
            'qualite_locuteur' => $l->qualite_locuteur,
            'source' => $reference
                ? [
                    'producteur' => $l->source_producteur,
                    'titre' => $l->source_titre,
                    'url' => $this->url($l->source_url),
                    'date' => $l->source_date?->toDateString(),
                ]
                : ($docPropre && $doc ? [
                    'titre' => $doc->titre,
                    'url' => $this->url($doc->url),
                    'date' => optional($doc->date_publication)->toDateString(),
                    'porteur' => $this->porteur($doc),
                ] : null),
        ];
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

            // Même périmètre que les prises de parole : le relu, du candidat et de son équipe.
            $nbParCandidat = $e->ingestion_document_id
                ? $this->duCandidat(IngestionProposition::where('document_id', $e->ingestion_document_id)->publique())
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
        $ecrits[] = $this->put("{$dir}/reperes.json", $data['reperes']);
        $ecrits[] = $this->put("{$dir}/budgets.json", $data['budgets']);

        foreach ($data['candidats'] as $slug => $candidat) {
            $ecrits[] = $this->put("{$dir}/candidats/{$slug}.json", $candidat);
        }

        // Index des candidats (léger) pour la grille /candidats
        $index = array_map(fn ($c) => [
            'slug' => $c['slug'],
            'nom_complet' => $c['nom_complet'],
            'nom' => $c['nom'],
            'prenom' => $c['prenom'],
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
