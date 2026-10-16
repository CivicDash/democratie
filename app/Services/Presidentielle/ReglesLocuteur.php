<?php

namespace App\Services\Presidentielle;

use App\Models\IngestionProposition;
use App\Support\Locuteur;
use App\Support\Porteur;

/**
 * Ce qu'il faut pour qu'une proposition paraisse du côté du candidat — écrit une seule fois.
 *
 * Une proposition validée ou rattachée paraît aussitôt dans les prises de parole du
 * candidat ; rattachée, elle devient une mesure. Il faut donc qu'elle soit bien du côté
 * du candidat (lui-même ou son équipe de campagne, jamais un parti), et que son locuteur,
 * s'il y en a un, soit nommé avec son rôle : l'attribution s'affiche partout.
 *
 * Appelée par ModerationService (refus des boutons « Valider → mesure » et « Valider sans
 * mesure ») et par IntegriteChecker (refus de l'export).
 */
class ReglesLocuteur
{
    /** @return list<string> vide = peut paraître du côté du candidat */
    public function raisons(IngestionProposition $p): array
    {
        $raisons = [];

        if ($p->qualite_locuteur === Locuteur::PARTI) {
            $raisons[] = 'propos de '.($p->locuteur ?? 'un responsable de parti')
                .' (parti) : une proposition de l\'entourage ne paraît jamais du côté du candidat — utilisez « Valider pour Autour du candidat »';
        }

        $type = $p->document?->porteur_type;
        if (Porteur::estEntourage($type)) {
            $raisons[] = 'document porté par '.($p->document->porteur_libelle ?: Porteur::LIBELLES[$type])
                .' : ses propositions ne paraissent que dans « Autour du candidat »';
        } elseif ($type !== null && $type !== 'candidat') {
            // Un document de l'équipe de campagne parle pour le candidat : il doit le prouver.
            foreach (app(ReglesPorteur::class)->raisons($p->document) as $r) {
                $raisons[] = 'document : '.$r;
            }
        }

        if (filled($p->locuteur) && blank($p->role_locuteur)) {
            $raisons[] = "locuteur « {$p->locuteur} » sans rôle : renseignez son rôle au moment des propos";
        }

        return $raisons;
    }
}
