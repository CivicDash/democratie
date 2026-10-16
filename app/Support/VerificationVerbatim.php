<?php

namespace App\Support;

/**
 * Vérification d'une citation contre la transcription de sa source.
 *
 * Sortie de PresidentielleImportPropositions, sans changement de comportement, pour servir
 * aussi à l'import du cadrage budgétaire. Trois issues :
 *   - exacte, ou score ≥ SEUIL_VERIFIE      -> vérifiée ;
 *   - SEUIL_A_VERIFIER ≤ score < SEUIL_VERIFIE -> à vérifier par un humain ;
 *   - score < SEUIL_A_VERIFIER             -> absente (citation probablement hallucinée).
 */
final class VerificationVerbatim
{
    public const SEUIL_VERIFIE = 0.85;

    public const SEUIL_A_VERIFIER = 0.55;

    /**
     * Normalise une transcription pour la comparaison verbatim :
     * retire timecodes SRT/VTT, numéros de blocs, uniformise espaces et casse.
     */
    public static function normaliser(string $s): string
    {
        // Timecodes SRT/VTT : 00:00:00,000 --> 00:00:00,000
        $s = preg_replace('/\d{2}:\d{2}:\d{2}[,.]\d{3}\s*-->\s*\d{2}:\d{2}:\d{2}[,.]\d{3}/u', ' ', $s);
        // Numéros de bloc SRT (ligne seule)
        $s = preg_replace('/^\s*\d+\s*$/m', ' ', $s);
        // Timecodes courts résiduels (01:10, 00:43:21)
        $s = preg_replace('/\b\d{1,2}:\d{2}(:\d{2})?\b/u', ' ', $s);
        $s = mb_strtolower($s, 'UTF-8');
        // Uniformise apostrophes/espaces
        $s = str_replace(['’', "\u{2019}", '«', '»'], ["'", "'", ' ', ' '], $s);
        $s = preg_replace('/\s+/u', ' ', $s);

        return trim((string) $s);
    }

    /**
     * Similarité d'une citation (déjà normalisée) vis-à-vis de la source normalisée.
     * Cherche la meilleure fenêtre de mots dans la source et renvoie la proportion
     * des mots de la citation qu'elle contient (multiset). Robuste aux micro-écarts
     * des sous-titres automatiques (ponctuation, un mot manquant çà et là).
     * Renvoie un score dans [0, 1].
     */
    public static function similariteMots(string $citation, string $source): float
    {
        $motsCitation = array_values(array_filter(explode(' ', $citation)));
        $motsSource = array_values(array_filter(explode(' ', $source)));
        $m = count($motsCitation);
        $n = count($motsSource);
        if ($m === 0 || $n < $m) {
            return 0.0;
        }

        // Fenêtre un peu plus large que la citation pour absorber les mots intercalés.
        $fenetre = (int) ceil($m * 1.4);
        $besoin = [];
        foreach ($motsCitation as $mot) {
            $besoin[$mot] = ($besoin[$mot] ?? 0) + 1;
        }

        $meilleur = 0;
        for ($debut = 0; $debut <= $n - $m; $debut++) {
            $dispo = [];
            $fin = min($n, $debut + $fenetre);
            for ($k = $debut; $k < $fin; $k++) {
                $mot = $motsSource[$k];
                $dispo[$mot] = ($dispo[$mot] ?? 0) + 1;
            }
            $couverts = 0;
            foreach ($besoin as $mot => $nb) {
                $couverts += min($nb, $dispo[$mot] ?? 0);
            }
            if ($couverts > $meilleur) {
                $meilleur = $couverts;
                if ($meilleur === $m) {
                    break;
                }
            }
        }

        return $meilleur / $m;
    }

    /**
     * Verdict sur une citation brute face à une source déjà normalisée.
     *
     * @return array{issue: 'verifiee'|'a_verifier'|'absente', score: float, exacte: bool}
     */
    public static function verifier(string $citation, string $sourceNormalisee): array
    {
        $citationNorm = self::normaliser($citation);
        if ($citation !== '' && str_contains($sourceNormalisee, $citationNorm)) {
            return ['issue' => 'verifiee', 'score' => 1.0, 'exacte' => true];
        }

        $score = $citationNorm === '' ? 0.0 : self::similariteMots($citationNorm, $sourceNormalisee);

        return [
            'issue' => $score >= self::SEUIL_VERIFIE ? 'verifiee' : ($score >= self::SEUIL_A_VERIFIER ? 'a_verifier' : 'absente'),
            'score' => $score,
            'exacte' => false,
        ];
    }
}
