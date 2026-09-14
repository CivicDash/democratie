<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Confronte, pour chaque route d'écriture, les clés validées aux colonnes réelles.
 *
 * C'est le contrôle que personne ne faisait, et son absence a produit la même panne
 * cinq fois : douze champs d'adhésion, cinq champs de suspension, quarante-cinq
 * champs de statistiques, l'édition d'un sénateur et celle d'un député, tous
 * silencieusement perdus derrière un message de succès.
 *
 * L'analyse est statique : on lit le corps de la méthode pour y trouver la
 * validation et le modèle écrit. Elle ne prétend pas tout couvrir — ce qu'elle ne
 * sait pas lire est déclaré « indéterminé » plutôt que passé sous silence, pour que
 * les angles morts se comptent.
 */
final class AuditEcritures
{
    /** Verbes qui écrivent. */
    private const VERBES = ['POST', 'PUT', 'PATCH'];

    /**
     * @return array<int, array{
     *   route: string, action: string, modele: ?string, table: ?string,
     *   cles: array<int,string>, inconnues: array<int,string>,
     *   non_fillable: array<int,string>, statut: string
     * }>
     */
    public static function analyser(string $prefixeNom = 'admin.'): array
    {
        $resultats = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $nom = $route->getName();
            if (! $nom || ! Str::startsWith($nom, $prefixeNom)) {
                continue;
            }
            if (! array_intersect($route->methods(), self::VERBES)) {
                continue;
            }

            $action = $route->getActionName();
            if (! Str::contains($action, '@')) {
                continue;   // closure
            }

            [$classe, $methode] = explode('@', $action);
            if (! class_exists($classe) || ! method_exists($classe, $methode)) {
                continue;
            }

            $source = self::source(new ReflectionMethod($classe, $methode));
            $valides = self::clesValidees($source);

            if ($valides === []) {
                continue;   // pas de validation : rien à confronter
            }

            $ecritures = self::ecritures($source, $classe, $methode, $valides);

            if ($ecritures === []) {
                $resultats[] = self::ligne($nom, $action, null, null, $valides, [], [], 'indetermine');

                continue;
            }

            // Une méthode peut écrire plusieurs modèles — un candidat, c'est une
            // personne politique ET une candidature. Chaque site est jugé séparément,
            // sinon les clés de l'un passent pour des colonnes manquantes de l'autre.
            foreach ($ecritures as [$modele, $cles]) {
                $instance = new $modele;
                $table = $instance->getTable();
                $colonnes = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
                $fillable = $instance->getFillable();

                $inconnues = array_values(array_diff($cles, $colonnes));
                $nonFillable = $fillable === []
                    ? []
                    : array_values(array_diff(array_intersect($cles, $colonnes), $fillable));

                $resultats[] = self::ligne(
                    $nom, $action, $modele, $table, $cles, $inconnues, $nonFillable,
                    ($inconnues || $nonFillable) ? 'divergent' : 'conforme',
                );
            }
        }

        usort($resultats, fn ($a, $b) => [$a['statut'], $a['route']] <=> [$b['statut'], $b['route']]);

        return $resultats;
    }

    private static function ligne(string $route, string $action, ?string $modele, ?string $table,
        array $cles, array $inconnues, array $nonFillable, string $statut): array
    {
        return compact('route', 'action', 'modele', 'table', 'cles', 'inconnues', 'nonFillable', 'statut')
            + ['non_fillable' => $nonFillable];
    }

    private static function source(ReflectionMethod $m): string
    {
        $lignes = file($m->getFileName());

        return implode('', array_slice($lignes, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));
    }

    /**
     * Les clés de premier niveau d'un validate([...]) littéral.
     *
     * Les clés imbriquées (`sources.*.url`) décrivent la forme de la requête, pas des
     * colonnes : elles sont écartées.
     *
     * @return array<int, string>
     */
    private static function clesValidees(string $source): array
    {
        if (! preg_match('/validate\(\s*\[(.*?)\n\s*\]\s*\)/s', $source, $m)) {
            return [];
        }

        preg_match_all("/'([A-Za-z0-9_.*]+)'\s*=>/", $m[1], $cles);

        return array_values(array_unique(array_filter(
            $cles[1],
            fn (string $c) => ! Str::contains($c, ['.', '*']),
        )));
    }

    /**
     * Les sites d'écriture de la méthode : couples (modèle, clés réellement écrites).
     *
     * @return array<int, array{0: class-string<Model>, 1: array<int, string>}>
     */
    private static function ecritures(string $source, string $classe, string $methode, array $valides): array
    {
        $sites = [];

        // Écritures statiques : Foo::create([...]) / Foo::updateOrCreate([...], ...)
        preg_match_all(
            '/(\w+)::(create|updateOrCreate|firstOrCreate)\s*\((.{0,4000}?\n\s*\]\s*\))/s',
            $source, $appels, PREG_SET_ORDER,
        );

        foreach ($appels as $appel) {
            $modele = self::resoudre($appel[1], $classe);
            if (! $modele) {
                continue;
            }

            $args = $appel[3];

            // updateOrCreate($cles, $validated) : c'est tout $validated qui part.
            if (preg_match('/,\s*\$validated/', $args)) {
                $sites[] = [$modele, $valides];

                continue;
            }

            preg_match_all('/\[(.*?)\n\s*\]/s', $args, $blocs);
            $charge = $blocs[1] === [] ? $args : end($blocs[1]);
            preg_match_all("/'([A-Za-z0-9_]+)'\s*=>/", self::sansImbrication($charge), $cles);

            if ($cles[1] !== []) {
                $sites[] = [$modele, array_values(array_unique($cles[1]))];
            }
        }

        // Écriture sur un paramètre typé : $foo->update($validated)
        if (preg_match_all('/\$(\w+)->(?:update|fill)\s*\(\s*\$validated/', $source, $m)) {
            foreach (array_unique($m[1]) as $variable) {
                foreach ((new ReflectionMethod($classe, $methode))->getParameters() as $param) {
                    if ($param->getName() !== $variable) {
                        continue;
                    }
                    $type = $param->getType();
                    if ($type instanceof ReflectionNamedType
                        && class_exists($type->getName())
                        && is_subclass_of($type->getName(), Model::class)) {
                        $sites[] = [$type->getName(), $valides];
                    }
                }
            }
        }

        return $sites;
    }

    /**
     * Vide les tableaux imbriqués d'une charge : leurs clés décrivent un JSON, pas des
     * colonnes. Sans cela, `'detection_raw_data' => ['source_declaration_url' => …]`
     * ferait passer une clé de payload pour une colonne manquante.
     */
    private static function sansImbrication(string $charge): string
    {
        do {
            $avant = $charge;
            // Le jeton de remplacement ne doit contenir aucun crochet, sinon la boucle
            // ne peut plus réduire le tableau parent.
            $charge = preg_replace('/\[[^\[\]]*\]/', 'IMBRIQUE', $charge);
        } while ($charge !== $avant);

        return $charge;
    }

    /** Résout un nom court en classe, via les `use` du fichier du contrôleur. */
    private static function resoudre(string $court, string $classe): ?string
    {
        if (is_subclass_of("App\\Models\\{$court}", Model::class)) {
            return "App\\Models\\{$court}";
        }

        $fichier = (new \ReflectionClass($classe))->getFileName();
        preg_match_all('/^use\s+([\w\\\\]+);/m', (string) file_get_contents($fichier), $uses);

        foreach ($uses[1] as $fqcn) {
            if (class_basename($fqcn) === $court && is_subclass_of($fqcn, Model::class)) {
                return $fqcn;
            }
        }

        return null;
    }
}
