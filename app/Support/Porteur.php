<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Le porteur d'une source — qui parle dans ce document, au nom de qui — en un seul endroit.
 *
 * Quatre porteurs. Le candidat et son équipe de campagne parlent pour le projet
 * présidentiel : leurs documents peuvent donner des mesures et un cadrage budgétaire. Un
 * parti ou un groupe parlementaire porte sa propre vision, qui peut différer de celle du
 * candidat : ses documents ne paraissent que dans « Autour du candidat ».
 *
 * Pour tout porteur autre que le candidat, la preuve d'endossement (URL, date, note) est
 * exigée avant publication — voir ReglesPorteur.
 */
final class Porteur
{
    public const TYPES = ['candidat', 'equipe_campagne', 'parti', 'groupe_parlementaire'];

    public const LIBELLES = [
        'candidat' => 'Le candidat',
        'equipe_campagne' => 'Équipe de campagne',
        'parti' => 'Parti',
        'groupe_parlementaire' => 'Groupe parlementaire',
    ];

    /** Documents rangés dans « Autour du candidat », jamais dans son programme. */
    public const ENTOURAGE = ['parti', 'groupe_parlementaire'];

    public static function estEntourage(?string $type): bool
    {
        return in_array($type, self::ENTOURAGE, true);
    }

    /**
     * Lit le bloc `porteur` d'un JSON d'import.
     *
     * Formes acceptées : absent (le candidat), une chaîne (« parti »), ou un objet
     * `{type, libelle, endossement: {url, date, note}}`. Un type inconnu lève : classer du
     * contenu de parti comme celui du candidat serait la pire erreur, on échoue en fermé.
     * Un endossement incomplet n'empêche pas l'import (c'est une file de modération) :
     * il produit un avertissement, et la publication restera refusée.
     *
     * @return array{0: array<string, mixed>, 1: list<string>} colonnes, avertissements
     *
     * @throws InvalidArgumentException type inconnu ou bloc illisible
     */
    public static function depuisJson(mixed $bloc): array
    {
        $colonnes = [
            'porteur_type' => 'candidat',
            'porteur_libelle' => null,
            'endossement_url' => null,
            'endossement_date' => null,
            'endossement_note' => null,
        ];

        if ($bloc === null || $bloc === '' || $bloc === []) {
            return [$colonnes, []];
        }
        if (is_string($bloc)) {
            $bloc = ['type' => $bloc];
        }
        if (! is_array($bloc)) {
            throw new InvalidArgumentException('bloc « porteur » illisible : objet {type, libelle, endossement} attendu');
        }

        $type = trim((string) ($bloc['type'] ?? 'candidat'));
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("porteur inconnu : « {$type} » (attendu : ".implode(', ', self::TYPES).')');
        }

        $endossement = is_array($bloc['endossement'] ?? null) ? $bloc['endossement'] : [];
        $url = $endossement['url'] ?? $bloc['endossement_url'] ?? null;
        $date = $endossement['date'] ?? $bloc['endossement_date'] ?? null;
        $note = $endossement['note'] ?? $bloc['endossement_note'] ?? null;
        $avertissements = [];

        if ($url !== null && $url !== '' && ! UrlSource::estValide($url)) {
            $avertissements[] = "URL d'endossement invalide ou provisoire (« {$url} ») : ignorée";
            $url = null;
        }
        if ($date !== null && $date !== '' && ! self::dateIso($date)) {
            $avertissements[] = "date d'endossement illisible (« {$date} ») : ignorée, format AAAA-MM-JJ attendu";
            $date = null;
        }

        $colonnes = [
            'porteur_type' => $type,
            'porteur_libelle' => self::texte($bloc['libelle'] ?? null, 255),
            'endossement_url' => UrlSource::nettoyer($url),
            'endossement_date' => $date ?: null,
            'endossement_note' => self::texte($note, 5000),
        ];

        if ($type !== 'candidat') {
            $manque = array_keys(array_filter([
                'libellé' => $colonnes['porteur_libelle'],
                'URL' => $colonnes['endossement_url'],
                'date' => $colonnes['endossement_date'],
                'note' => $colonnes['endossement_note'],
            ], fn ($v) => blank($v)));
            if ($manque) {
                $avertissements[] = 'porteur « '.self::LIBELLES[$type].' » : publication refusée tant que manque(nt) '.implode(', ', $manque).' de l\'endossement';
            }
        }

        return [$colonnes, $avertissements];
    }

    private static function dateIso(mixed $d): bool
    {
        return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4));
    }

    private static function texte(mixed $v, int $max): ?string
    {
        $v = trim((string) ($v ?? ''));

        return $v === '' ? null : mb_substr($v, 0, $max);
    }
}
