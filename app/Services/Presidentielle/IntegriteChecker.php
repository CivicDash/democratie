<?php

namespace App\Services\Presidentielle;

use App\Models\CandidatPresidentielle;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;

/**
 * Contrôle d'intégrité éditoriale avant export (plan §5 / §8).
 * Distingue les VIOLATIONS bloquantes (interdisent l'export) des ALERTES de symétrie.
 *
 * Violations bloquantes (sur le contenu publié uniquement) :
 *  - mesure publiée sans source officielle ;
 *  - mesure publiée sans >= 1 argument "pour" ET >= 1 "contre" validés, chacun
 *    avec au moins une source fiable (haute/moyenne) ;
 *  - lien mesure↔scrutin affiché sans explication rédigée.
 *
 * Alertes (non bloquantes par défaut) :
 *  - couverture de thème asymétrique entre candidats ;
 *  - écart de volume de mesures publiées supérieur au seuil.
 */
class IntegriteChecker
{
    /** Seuil d'écart de volume déclenchant une alerte de symétrie. */
    public const SEUIL_ECART_VOLUME = 3.0;

    /** @return array{violations: array<int,array>, alertes: array<int,array>} */
    public function analyser(string $election = '2027'): array
    {
        $violations = [];
        $alertes = [];

        $candidats = CandidatPresidentielle::publie()
            ->where('election', $election)
            ->with(['personnePolitique', 'mesures' => fn ($q) => $q->publie()->with([
                'liens' => fn ($l) => $l->publie()->with(['argument' => fn ($a) => $a->with('sources')]),
                'scrutinLiens',
            ])])
            ->get();

        $volumesParCandidat = [];
        $themesParCandidat = [];

        foreach ($candidats as $candidat) {
            $label = $candidat->personnePolitique?->nom_complet ?? "candidat #{$candidat->id}";

            if ($candidat->photo_url && (blank($candidat->photo_credit) || blank($candidat->photo_licence))) {
                $violations[] = ['type' => 'photo_sans_credit', 'message' => "[{$label}] photo publiée sans crédit et/ou licence."];
            }

            $volumesParCandidat[$candidat->id] = $candidat->mesures->count();
            $themesParCandidat[$candidat->id] = $candidat->mesures->pluck('theme_id')->unique()->all();

            foreach ($candidat->mesures as $mesure) {
                $this->verifierMesure($mesure, $label, $violations);
            }
        }

        // Alerte symétrie : écart de volume entre le plus riche et le plus pauvre.
        if (count($volumesParCandidat) >= 2) {
            $max = max($volumesParCandidat);
            $min = min($volumesParCandidat);
            if ($min > 0 && ($max / $min) > self::SEUIL_ECART_VOLUME) {
                $alertes[] = [
                    'type' => 'ecart_volume',
                    'message' => "Écart de volume de mesures publiées élevé (min {$min}, max {$max}) — vérifier la symétrie de traitement.",
                ];
            } elseif ($min === 0 && $max > 0) {
                $alertes[] = [
                    'type' => 'candidat_sans_mesure',
                    'message' => "Un candidat publié n'a aucune mesure publiée alors qu'un autre en a {$max}.",
                ];
            }
        }

        // Alerte symétrie : thème couvert pour certains candidats mais pas d'autres.
        if (count($themesParCandidat) >= 2) {
            $tousThemes = collect($themesParCandidat)->flatten()->unique();
            foreach ($tousThemes as $themeId) {
                $couverts = collect($themesParCandidat)->filter(fn ($t) => in_array($themeId, $t, true))->count();
                $total = count($themesParCandidat);
                if ($couverts > 0 && $couverts < $total) {
                    $nomTheme = ProgrammeTheme::find($themeId)?->nom ?? "thème #{$themeId}";
                    $alertes[] = [
                        'type' => 'couverture_theme',
                        'message' => "Thème « {$nomTheme} » couvert pour {$couverts}/{$total} candidats publiés.",
                    ];
                }
            }
        }

        return ['violations' => $violations, 'alertes' => $alertes];
    }

    private function verifierMesure(ProgrammeMesure $mesure, string $candidat, array &$violations): void
    {
        $ref = "[{$candidat}] mesure « ".mb_strimwidth($mesure->titre, 0, 60, '…').' »';

        if (blank($mesure->source_officielle_url)) {
            $violations[] = ['type' => 'mesure_sans_source', 'message' => "{$ref} : aucune source officielle."];
        } elseif (! $this->estUrlValide($mesure->source_officielle_url)) {
            $violations[] = ['type' => 'mesure_source_invalide', 'message' => "{$ref} : URL de source invalide ou placeholder (A_COMPLETER)."];
        }

        // Symétrie via les liaisons publiées : le sens est porté par la liaison, la fiabilité
        // par l'argument (fait sourcé). Une liaison ne compte que si son argument est publié,
        // sourcé fiablement, et qu'elle porte une note contextuelle.
        $liensFiables = $mesure->liens->filter(fn ($l) => $l->argument
            && $l->argument->affiche_publiquement
            && filled($l->note_contextuelle)
            && $this->argumentAvecSourceFiable($l->argument));
        $pour = $liensFiables->where('sens', 'pour');
        $contre = $liensFiables->where('sens', 'contre');

        if ($pour->isEmpty()) {
            $violations[] = ['type' => 'mesure_sans_pour', 'message' => "{$ref} : aucun argument « pour » validé et sourcé."];
        }
        if ($contre->isEmpty()) {
            $violations[] = ['type' => 'mesure_sans_contre', 'message' => "{$ref} : aucun argument « contre » validé et sourcé (symétrie obligatoire)."];
        }

        foreach ($mesure->scrutinLiens->where('affiche_publiquement', true) as $lien) {
            if (blank($lien->explication)) {
                $violations[] = ['type' => 'lien_sans_explication', 'message' => "{$ref} : lien scrutin affiché sans explication rédigée."];
            }
        }
    }

    private function argumentAvecSourceFiable($argument): bool
    {
        return $argument->sources->contains(
            fn ($s) => in_array($s->fiabilite, ['haute', 'moyenne'], true) && $this->estUrlValide($s->url)
        );
    }

    /** URL publique valide : absolue http(s) et sans placeholder. */
    private function estUrlValide(?string $u): bool
    {
        return $u && ! str_contains($u, 'A_COMPLETER') && (bool) preg_match('#^https?://#i', $u);
    }
}
