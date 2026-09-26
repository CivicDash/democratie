<?php

namespace App\Services\Presidentielle\Eurostat;

use App\Models\EurostatIndicateur;
use App\Models\User;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Extraction des comparaisons européennes (annexe D.1), portage de fetch_eurostat.py.
 *
 * Les calculs reproduisent le script à l'identique — mêmes opérations dans le même ordre,
 * mêmes arrondis (sprintf arrondit la valeur binaire exacte, comme `round` en Python) —
 * pour que les deux sorties se comparent valeur par valeur.
 *
 * Une extraction ne modifie jamais ce que le site montre : elle dépose ce qu'elle trouve
 * à côté de la série publiée, avec la différence, et attend qu'un humain valide.
 */
class ExtractionEurostat
{
    /** @var array<string, array> lectures déjà faites pendant cette extraction, par URL */
    private array $lectures = [];

    public function __construct(private ClientEurostat $client) {}

    /**
     * Interroge Eurostat pour tout le catalogue. N'écrit rien.
     *
     * @return array{meta: array, indicateurs: list<array>} même forme que eurostat_comparaisons.json
     */
    public function extraire(): array
    {
        $this->lectures = [];
        $indicateurs = [];

        foreach (config('eurostat.indicateurs') as $def) {
            $num = $this->lire($def['jeu'], $def['filtres']);
            $sources = [['code' => $def['jeu'], 'url' => $num['url']]];
            $calcul = $def['calcul'];

            $series = match ($calcul['type']) {
                'brut' => $num['series'],
                'ratio' => (function () use ($num, $calcul, $def, &$sources) {
                    $den = $this->lire($calcul['jeu'], $calcul['filtres']);
                    if ($calcul['jeu'] !== $def['jeu']) {
                        $sources[] = ['code' => $calcul['jeu'], 'url' => $den['url']];
                    }

                    return $this->ratio($num['series'], $den['series'], $calcul['facteur']);
                })(),
                'base100' => $this->base100($num['series'], (string) $calcul['annee']),
                default => throw new RuntimeException("{$def['code']} : calcul inconnu « {$calcul['type']} »"),
            };

            $indicateurs[] = [
                'id' => $def['code'],
                'fiche' => $def['fiche'],
                'titre' => $def['titre'],
                'unite' => $def['unite'],
                'sources' => $sources,
                'note_methodo' => $def['note'],
                'pertinence' => $def['pertinence'],
                'series' => $this->versJson($series),
            ];
        }

        return [
            'meta' => [
                'producteur' => 'Eurostat',
                'extraction' => now()->toDateString(),
                'panel' => config('eurostat.panel'),
                'depuis' => (int) config('eurostat.depuis'),
                'legende_statuts' => config('eurostat.legende_statuts'),
                'statut_validation' => 'detecte',
            ],
            'indicateurs' => $indicateurs,
        ];
    }

    /**
     * Archive l'extraction (annexe D.1.5 : un graphique publié doit pouvoir être régénéré
     * à l'identique) et dépose les séries qui diffèrent de ce qui est publié.
     *
     * @return array{nouveaux: int, revisions: int, inchanges: int, archive: string}
     */
    public function enregistrer(array $resultat): array
    {
        $dir = storage_path('app/eurostat');
        File::ensureDirectoryExists($dir);
        $archive = $dir.'/'.now()->format('Y-m-d_His').'.json';
        File::put($archive, json_encode($resultat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $bilan = ['nouveaux' => 0, 'revisions' => 0, 'inchanges' => 0, 'archive' => $archive];
        $date = $resultat['meta']['extraction'];

        foreach ($resultat['indicateurs'] as $ind) {
            $modele = EurostatIndicateur::firstOrNew(['code' => $ind['id']]);
            $modele->fill([
                'titre' => $ind['titre'],
                'unite' => $ind['unite'],
                'note_methodo' => $ind['note_methodo'],
                'pertinence' => $ind['pertinence'],
                'sources' => $ind['sources'],
            ]);

            if ($modele->series_publiees !== null && self::memesSeries($modele->series_publiees, $ind['series'])) {
                // Rien de nouveau : une révision précédemment détectée puis annulée par
                // Eurostat n'a plus lieu d'attendre.
                $modele->fill(['series_detectees' => null, 'extraction_detectee' => null, 'diff' => null]);
                $bilan['inchanges']++;
            } else {
                $modele->fill([
                    'series_detectees' => $ind['series'],
                    'extraction_detectee' => $date,
                    'diff' => self::difference($modele->series_publiees ?? [], $ind['series']),
                ]);
                $modele->series_publiees === null ? $bilan['nouveaux']++ : $bilan['revisions']++;
            }

            $modele->save();
        }

        return $bilan;
    }

    /** La série détectée devient celle que le site montre. */
    public function valider(EurostatIndicateur $indicateur, User $user): void
    {
        if ($indicateur->series_detectees === null) {
            return;
        }

        $indicateur->update([
            'series_publiees' => $indicateur->series_detectees,
            'extraction_publiee' => $indicateur->extraction_detectee,
            'series_detectees' => null,
            'extraction_detectee' => null,
            'diff' => null,
            'valide_par' => $user->id,
            'valide_at' => now(),
        ]);
    }

    /**
     * Années nouvelles, valeurs révisées, statuts changés, années disparues.
     *
     * @return array{nouvelles: list<array>, revisions: list<array>, statuts: list<array>, disparues: list<array>}
     */
    public static function difference(array $avant, array $apres): array
    {
        $diff = ['nouvelles' => [], 'revisions' => [], 'statuts' => [], 'disparues' => []];
        $indexer = fn (array $series) => collect($series)->map(fn ($points) => collect($points)->keyBy('annee')->all())->all();
        $a = $indexer($avant);
        $b = $indexer($apres);

        foreach ($b as $pays => $points) {
            foreach ($points as $annee => $p) {
                $ancien = $a[$pays][$annee] ?? null;
                if ($ancien === null) {
                    $diff['nouvelles'][] = ['pays' => $pays, 'annee' => (int) $annee, 'valeur' => $p['valeur'], 'statut' => $p['statut']];
                } elseif ((string) $ancien['valeur'] !== (string) $p['valeur']) {
                    $diff['revisions'][] = ['pays' => $pays, 'annee' => (int) $annee, 'avant' => $ancien['valeur'], 'apres' => $p['valeur']];
                } elseif ($ancien['statut'] !== $p['statut']) {
                    $diff['statuts'][] = ['pays' => $pays, 'annee' => (int) $annee, 'avant' => $ancien['statut'], 'apres' => $p['statut']];
                }
            }
        }
        foreach ($a as $pays => $points) {
            foreach ($points as $annee => $p) {
                if (! isset($b[$pays][$annee])) {
                    $diff['disparues'][] = ['pays' => $pays, 'annee' => (int) $annee, 'valeur' => $p['valeur']];
                }
            }
        }

        return $diff;
    }

    public static function memesSeries(array $a, array $b): bool
    {
        $d = self::difference($a, $b);

        return ! $d['nouvelles'] && ! $d['revisions'] && ! $d['statuts'] && ! $d['disparues'];
    }

    /** @param array<string, string> $filtres */
    private function lire(string $jeu, array $filtres): array
    {
        $url = $this->client->url($jeu, $filtres);

        return $this->lectures[$url] ??= $this->client->lire($jeu, $filtres);
    }

    /** Série / dénominateur × facteur, année par année ; statuts fusionnés, triés, dédoublonnés. */
    private function ratio(array $a, array $b, int $facteur): array
    {
        $res = [];
        foreach ($a as $pays => $annees) {
            foreach ($annees as $annee => [$va, $sa]) {
                if (! isset($b[$pays][$annee]) || (float) $b[$pays][$annee][0] == 0.0) {
                    continue;
                }
                [$vb, $sb] = $b[$pays][$annee];
                $statuts = array_unique(str_split($sa.$sb));
                sort($statuts);
                $res[$pays][$annee] = [self::arrondi($va / $vb * $facteur, 2), implode('', array_filter($statuts, fn ($c) => $c !== ''))];
            }
        }

        return $res;
    }

    /** Indice rebasé (100 l'année de base), années postérieures seulement. */
    private function base100(array $series, string $base): array
    {
        $res = [];
        foreach ($series as $pays => $annees) {
            if (! isset($annees[$base]) || (float) $annees[$base][0] == 0.0) {
                continue;
            }
            $b = $annees[$base][0];
            foreach ($annees as $annee => [$v, $s]) {
                if ((string) $annee >= $base) {
                    $res[$pays][$annee] = [self::arrondi($v / $b * 100, 1), $s];
                }
            }
        }

        return $res;
    }

    /** @return array<string, list<array{annee: int, valeur: int|float, statut: string}>> */
    private function versJson(array $series): array
    {
        $sortie = [];
        foreach ($series as $pays => $annees) {
            ksort($annees, SORT_STRING);
            foreach ($annees as $annee => [$v, $s]) {
                $sortie[$pays][] = ['annee' => (int) $annee, 'valeur' => $v, 'statut' => $s];
            }
        }

        return $sortie;
    }

    /**
     * Arrondi de la valeur binaire exacte, demi-pair sur les égalités — celui de `round`
     * en Python. `round()` en PHP pré-arrondit (2,675 → 2,68 au lieu de 2,67) : la sortie
     * ne se comparerait plus à celle du script d'origine.
     */
    private static function arrondi(float $x, int $decimales): float
    {
        return (float) sprintf('%.'.$decimales.'f', $x);
    }
}
