<?php

namespace App\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Qui parle, et de quel côté — en un seul endroit, pour l'import des propositions comme
 * pour celui du cadrage budgétaire.
 *
 * Trois qualités : le candidat lui-même (pas de locuteur), son équipe de campagne
 * (`equipe_campagne` : du côté du candidat, attribution affichée), un parti (`parti` :
 * une autre vision, jamais une mesure du candidat).
 */
final class Locuteur
{
    public const EQUIPE = 'equipe_campagne';

    public const PARTI = 'parti';

    public const QUALITES = [self::EQUIPE, self::PARTI];

    public const LIBELLES = [
        self::EQUIPE => 'Équipe de campagne',
        self::PARTI => 'Parti',
    ];

    /**
     * Normalise les clés `locuteur`, `role_locuteur` et `qualite_locuteur` d'une entrée
     * d'import.
     *
     *  - un locuteur qui EST le candidat (même slug) redevient « le candidat » ;
     *  - sans `qualite_locuteur`, la qualité suit le porteur du document : équipe de
     *    campagne pour un document du candidat ou de son équipe, parti pour un document de
     *    parti ou de groupe parlementaire ;
     *  - une qualité inconnue, ou une qualité sans locuteur, lève : rien ne doit être écrit.
     *
     * @param  array<string, mixed>  $entree
     * @return array{locuteur: ?string, role_locuteur: ?string, qualite_locuteur: ?string, avertissements: list<string>}
     *
     * @throws InvalidArgumentException
     */
    public static function normaliser(array $entree, ?string $candidatSlug, ?string $porteurType): array
    {
        $locuteur = self::texte($entree['locuteur'] ?? null, 150);
        $role = self::texte($entree['role_locuteur'] ?? null, 200);
        $qualite = self::texte($entree['qualite_locuteur'] ?? null, 30);
        $avertissements = [];

        if ($qualite !== null && $qualite !== 'candidat' && ! in_array($qualite, self::QUALITES, true)) {
            throw new InvalidArgumentException("qualite_locuteur inconnue : « {$qualite} » (attendu : candidat, ".implode(', ', self::QUALITES).')');
        }

        $candidat = ['locuteur' => null, 'role_locuteur' => null, 'qualite_locuteur' => null];

        if ($locuteur === null) {
            if ($qualite !== null && $qualite !== 'candidat') {
                throw new InvalidArgumentException("qualite_locuteur « {$qualite} » sans locuteur : impossible d'attribuer le propos");
            }

            return $candidat + ['avertissements' => $avertissements];
        }

        if ($candidatSlug && Str::slug($locuteur) === $candidatSlug) {
            if ($qualite !== null && $qualite !== 'candidat') {
                $avertissements[] = "« {$locuteur} » est le candidat : qualite_locuteur « {$qualite} » ignorée";
            }

            return $candidat + ['avertissements' => $avertissements];
        }

        if ($qualite === 'candidat') {
            throw new InvalidArgumentException("« {$locuteur} » n'est pas le candidat : qualite_locuteur « candidat » impossible");
        }

        $qualite ??= Porteur::estEntourage($porteurType) ? self::PARTI : self::EQUIPE;

        if ($role === null) {
            $avertissements[] = "locuteur « {$locuteur} » sans role_locuteur : non publiable tant que son rôle n'est pas renseigné";
        }

        return ['locuteur' => $locuteur, 'role_locuteur' => $role, 'qualite_locuteur' => $qualite, 'avertissements' => $avertissements];
    }

    private static function texte(mixed $v, int $max): ?string
    {
        $v = trim((string) ($v ?? ''));

        return $v === '' ? null : mb_substr($v, 0, $max);
    }
}
