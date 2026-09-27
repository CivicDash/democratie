<?php

namespace App\Services\Presidentielle;

use App\Models\Affirmation;
use App\Models\AffirmationConstat;
use App\Models\AffirmationGraphique;
use App\Models\EurostatIndicateur;
use App\Support\UrlSource;

/**
 * Ce qui rend une fiche « Ce qu'on entend » publiable — écrit une seule fois.
 *
 * Deux appelants : ModerationService::raisonsNonPubliable(), qui refuse le bouton
 * « Publier », et IntegriteChecker, dont une violation refuse l'export et fige donc
 * objectif2027.fr. Pour le quiz et les mesures, la règle existe en deux copies qu'il faut
 * garder identiques ; celle des sources d'arguments avait divergé. Ici, un seul code.
 */
class ReglesAffirmation
{
    /** @return list<string> vide = publiable */
    public function raisons(Affirmation $a): array
    {
        $a->loadMissing(['theme', 'verdicts', 'constats.sources', 'graphiques']);
        $raisons = [];

        // 1. Verdict(s). Plusieurs verdicts sans portée seraient illisibles : lequel porte sur quoi ?
        if ($a->verdicts->isEmpty()) {
            $raisons[] = 'aucun verdict';
        }
        foreach ($a->verdicts as $v) {
            if (! isset(Affirmation::VERDICTS[$v->verdict])) {
                $raisons[] = "verdict inconnu « {$v->verdict} »";
            }
        }
        if ($a->verdicts->count() >= 2 && $a->verdicts->contains(fn ($v) => blank($v->portee))) {
            $raisons[] = 'plusieurs verdicts : chacun doit dire sur quoi il porte';
        }

        // Tout ce qui suit porte sur ce qui PARAÎT : une phrase non vérifiée reste masquée,
        // et la fiche dit combien il en reste. On ne vérifie donc pas les sources d'une
        // phrase que personne ne lira encore.
        $affiches = $a->constatsAffiches();

        // 2. Ce que disent les chiffres, et ce qu'ils ne disent pas.
        if ($affiches->where('section', 'chiffres')->isEmpty()) {
            $raisons[] = 'aucun constat « Ce que disent les chiffres » vérifié';
        }
        if ($a->constats->where('section', 'limites')->isEmpty()) {
            $raisons[] = 'aucune limite : la fiche doit dire ce que les chiffres ne disent pas';
        }

        // 3. Les réserves paraissent toutes, ou la fiche ne paraît pas. Masquer un chiffre
        //    non sourcé retire une preuve ; masquer une réserve durcit la conclusion.
        foreach (AffirmationConstat::SECTIONS_INTEGRALES as $section) {
            $masquees = $a->constats->where('section', $section)->reject->estAffiche()->count();
            if ($masquees > 0) {
                $raisons[] = "{$masquees} phrase(s) « ".AffirmationConstat::SECTIONS[$section].' » à vérifier : une fiche ne paraît jamais sans toutes ses réserves';
            }
        }

        // 4. Un chiffre affiché cite sa source.
        $sansSource = $affiches
            ->filter(fn ($c) => in_array($c->section, AffirmationConstat::SECTIONS_SOURCEES, true) && $c->sources->isEmpty())
            ->count();
        if ($sansSource > 0) {
            $raisons[] = "{$sansSource} constat(s) chiffré(s) vérifié(s) sans source";
        }

        // 5 et 6. Chaque source affichée est consultable, et hors des domaines exclus.
        $citees = $affiches->flatMap->sources->unique('id');
        $sansUrl = $citees->reject(fn ($s) => UrlSource::estValide($s->url))->pluck('cle');
        if ($sansUrl->isNotEmpty()) {
            $raisons[] = 'source(s) sans URL valide : '.$sansUrl->implode(', ');
        }
        $exclues = $citees->filter(fn ($s) => self::domaineExclu($s->url))->pluck('cle');
        if ($exclues->isNotEmpty()) {
            $raisons[] = 'source(s) d\'un domaine exclu par le cadre éditorial : '.$exclues->implode(', ');
        }

        // 7. Graphiques : accompagnés d'une phrase, bien formés, et sur des séries relues.
        $raisons = [...$raisons, ...$this->raisonsGraphiques($a)];

        // 8. Thème principal actif.
        if (! $a->theme || ! $a->theme->actif) {
            $raisons[] = 'thème principal absent ou inactif';
        }

        return $raisons;
    }

    /** @return list<string> */
    private function raisonsGraphiques(Affirmation $a): array
    {
        if ($a->graphiques->isEmpty()) {
            return [];
        }

        $raisons = [];
        $idsConstats = $a->constats->pluck('id')->all();
        foreach ($a->graphiques as $g) {
            if (! $g->constat_id || ! in_array($g->constat_id, $idsConstats, true)) {
                $raisons[] = "graphique « {$g->titre} » : rattaché à aucune phrase de la fiche — un graphique n'est jamais le seul support d'une conclusion";
            }
        }

        // Un graphique dont la phrase n'est pas encore vérifiée ne paraît pas : ses séries
        // n'ont pas à être relues pour que le reste de la fiche soit publié.
        $affiches = $a->graphiquesAffiches();
        $codes = $affiches->flatMap(fn ($g) => (array) $g->indicateurs)->unique()->values();
        $publies = EurostatIndicateur::whereIn('code', $codes)->whereNotNull('series_publiees')->pluck('code')->all();

        foreach ($affiches as $g) {
            $nom = "graphique « {$g->titre} »";
            if (! isset(AffirmationGraphique::TYPES[$g->type])) {
                $raisons[] = "{$nom} : type inconnu « {$g->type} »";

                continue;
            }
            [$min, $max] = AffirmationGraphique::NB_INDICATEURS[$g->type];
            $n = count((array) $g->indicateurs);
            if ($n < $min || $n > $max) {
                $raisons[] = "{$nom} : {$n} indicateur(s), le type en attend ".($min === $max ? $min : "de {$min} à {$max}");
            }
            $nonRelus = array_values(array_diff((array) $g->indicateurs, $publies));
            if ($nonRelus) {
                $raisons[] = "{$nom} : série Eurostat non validée (".implode(', ', $nonRelus).')';
            }
        }

        return $raisons;
    }

    /** L'URL relève-t-elle d'un domaine exclu (sous-domaines compris) ? */
    public static function domaineExclu(?string $url): bool
    {
        $hote = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
        if ($hote === '') {
            return false;
        }

        foreach ((array) config('presidentielle.sources_exclues', []) as $domaine) {
            $domaine = strtolower($domaine);
            if ($hote === $domaine || str_ends_with($hote, '.'.$domaine)) {
                return true;
            }
        }

        return false;
    }
}
