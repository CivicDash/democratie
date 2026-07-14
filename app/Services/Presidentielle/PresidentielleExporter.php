<?php

namespace App\Services\Presidentielle;

use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeTheme;
use Illuminate\Support\Facades\File;

/**
 * Génère l'export JSON statique consommé par le front Astro (plan §6).
 * NE SORT QUE le contenu `statut_validation = valide AND affiche_publiquement = true`.
 * `build()` produit la structure (testable) ; `write()` l'écrit sur disque.
 * Le hash de contenu (`meta.content_hash`) est déterministe (indépendant de la date).
 */
class PresidentielleExporter
{
    public function __construct(private IntegriteChecker $integrite) {}

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
                'mesures' => fn ($q) => $q->publie()->orderBy('ordre')->with([
                    'theme',
                    'arguments' => fn ($a) => $a->publie()->orderBy('ordre')->with('sources'),
                    'scrutinLiens' => fn ($l) => $l->publie(),
                ]),
                'propositions' => fn ($p) => $p->whereIn('statut', ['detecte', 'validee', 'rattachee'])->with(['theme', 'document']),
            ])
            ->orderBy('ordre_affichage')
            ->get();

        $candidatsExport = [];
        foreach ($candidats as $candidat) {
            $slug = $candidat->personnePolitique?->slug ?? $candidat->uuid;
            $candidatsExport[$slug] = $this->exportCandidat($candidat, $themesExport);
        }

        $comparateur = $this->buildComparateur($candidatsExport, $themesExport);

        $contenu = [
            'election' => $election,
            'themes' => $themesExport,
            'candidats' => $candidatsExport,
            'comparateur' => $comparateur,
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
        foreach ($candidat->mesures as $mesure) {
            $themeSlug = $mesure->theme?->slug ?? 'autre';
            $mesuresParTheme[$themeSlug][] = [
                'titre' => $mesure->titre,
                'resume' => $mesure->resume,
                'chiffrage' => $mesure->chiffrage_annonce,
                'source_url' => $this->url($mesure->source_officielle_url),
                'statut' => $mesure->statut_mesure,
                'date_annonce' => optional($mesure->date_annonce)->toDateString(),
                'mise_en_avant' => (bool) $mesure->est_mise_en_avant,
                'arguments' => [
                    'pour' => $this->exportArguments($mesure->arguments->where('sens', 'pour')),
                    'contre' => $this->exportArguments($mesure->arguments->where('sens', 'contre')),
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

        // État honnête par thème sur TOUT le référentiel (design spec §1.3).
        $etatsParTheme = [];
        foreach ($themes as $t) {
            $slug = $t['slug'];
            if (! empty($mesuresParTheme[$slug])) {
                $etatsParTheme[$slug] = ['etat' => 'publie', 'nb_mesures' => count($mesuresParTheme[$slug])];
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
            'prises_de_parole' => $this->exportPrisesDeParole($candidat),
            'affaires' => $this->exportAffaires($candidat->personnePolitique),
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
    private function exportAffaires(?PersonnePolitique $personne): array
    {
        if (! $personne) {
            return ['publiees' => [], 'en_verification' => false];
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

        return ['publiees' => $publiees, 'en_verification' => $enVerification];
    }

    /**
     * Chronologie des prises de parole (documents d'ingestion) + citations verbatim
     * avec timecode vérifiable (deep-link vidéo &t=Ns). Le différenciateur du site :
     * chaque citation est vérifiable à la source en un clic.
     */
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

    private function tronqueMots(string $txt, int $max): string
    {
        $mots = preg_split('/\s+/', trim($txt));

        return count($mots) <= $max ? $txt : implode(' ', array_slice($mots, 0, $max)).' …';
    }

    private function exportArguments($arguments): array
    {
        return $arguments->map(fn ($a) => [
            'titre' => $a->titre,
            'contenu' => $a->contenu,
            'type' => $a->type_argument,
            'sources' => $a->sources->map(fn ($s) => [
                'type' => $s->type_source,
                'titre' => $s->titre,
                'url' => $this->url($s->url),
                'media' => $s->media,
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
