<?php

namespace App\Services\Presidentielle\Eurostat;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lecture de l'API de diffusion Eurostat (JSON-stat 2.0), sur le panel configuré.
 *
 * Portage de `fetch()` dans fetch_eurostat.py : même URL (paramètres dans le même ordre,
 * pour qu'une URL archivée se compare à l'identique), même décodage, et la même garde —
 * une dimension autre que pays et année doit être filtrée sur une seule valeur, sinon
 * les valeurs lues seraient un mélange de catégories.
 */
class ClientEurostat
{
    /**
     * @param  array<string, string>  $filtres
     * @return array{series: array<string, array<string, array{0: int|float, 1: string}>>, url: string}
     *                                                                                                  series[pays][année] = [valeur, statut]
     */
    public function lire(string $jeu, array $filtres): array
    {
        $url = $this->url($jeu, $filtres);

        $reponse = Http::timeout(120)->retry(2, 5000, throw: false)->acceptJson()->get($url);
        if ($reponse->failed()) {
            throw new RuntimeException("{$jeu} : HTTP {$reponse->status()}");
        }

        return ['series' => $this->decoder($jeu, $reponse->json() ?? []), 'url' => $url];
    }

    /** @param array<string, string> $filtres */
    public function url(string $jeu, array $filtres): string
    {
        $params = [
            ['sinceTimePeriod', (string) config('eurostat.depuis')],
            ['lang', 'fr'],
        ];
        foreach (config('eurostat.panel') as $pays) {
            $params[] = ['geo', $pays];
        }
        foreach ($filtres as $cle => $valeur) {
            $params[] = [$cle, $valeur];
        }

        return config('eurostat.api').$jeu.'?'.implode('&', array_map(
            fn ($p) => urlencode($p[0]).'='.urlencode($p[1]),
            $params
        ));
    }

    /**
     * JSON-stat : les valeurs sont indexées à plat, dans l'ordre des dimensions `id`, avec
     * des pas calculés depuis `size`.
     *
     * @return array<string, array<string, array{0: int|float, 1: string}>>
     */
    public function decoder(string $jeu, array $d): array
    {
        $ids = $d['id'] ?? null;
        $tailles = $d['size'] ?? null;
        if (! is_array($ids) || ! is_array($tailles)) {
            throw new RuntimeException("{$jeu} : réponse sans dimensions (id/size)");
        }

        foreach ($ids as $k => $dim) {
            if (! in_array($dim, ['geo', 'time'], true) && $tailles[$k] !== 1) {
                throw new RuntimeException("{$jeu} : dimension {$dim} non filtrée ({$tailles[$k]} valeurs)");
            }
        }

        $pas = array_fill(0, count($ids), 1);
        for ($i = count($ids) - 2; $i >= 0; $i--) {
            $pas[$i] = $pas[$i + 1] * $tailles[$i + 1];
        }
        $rang = array_flip($ids);

        $valeurs = $d['value'] ?? [];
        $statuts = $d['status'] ?? [];
        $sortie = [];

        foreach ($d['dimension']['geo']['category']['index'] as $pays => $gi) {
            foreach ($d['dimension']['time']['category']['index'] as $annee => $ti) {
                $plat = $gi * $pas[$rang['geo']] + $ti * $pas[$rang['time']];
                $v = $valeurs[$plat] ?? $valeurs[(string) $plat] ?? null;
                if ($v !== null) {
                    $sortie[$pays][(string) $annee] = [$v, (string) ($statuts[$plat] ?? $statuts[(string) $plat] ?? '')];
                }
            }
        }

        return $sortie;
    }
}
