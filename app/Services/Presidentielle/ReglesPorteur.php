<?php

namespace App\Services\Presidentielle;

use App\Support\Porteur;
use App\Support\UrlSource;
use Illuminate\Database\Eloquent\Model;

/**
 * Ce qui rend publiable le porteur d'une source — écrit une seule fois.
 *
 * Deux appelants, comme ReglesAffirmation : ModerationService::raisonsNonPubliable() (le
 * bouton « Publier ») et IntegriteChecker (l'export). Le contrôle d'intégrité ne doit
 * jamais être plus strict que le bouton, sinon publier figerait le site.
 *
 * Tout porteur autre que le candidat exige la preuve d'endossement : un libellé, l'URL où
 * l'endossement se constate, sa date et une note qui dit en quoi il consiste.
 */
class ReglesPorteur
{
    /** @return list<string> vide = conforme */
    public function raisons(Model $document): array
    {
        $type = $document->porteur_type ?? 'candidat';
        if (! in_array($type, Porteur::TYPES, true)) {
            return ["porteur inconnu « {$type} »"];
        }
        if ($type === 'candidat') {
            return [];
        }

        $raisons = [];
        $nom = Porteur::LIBELLES[$type];
        if (blank($document->porteur_libelle)) {
            $raisons[] = "porteur « {$nom} » sans libellé (qui, précisément ?)";
        }
        if (! UrlSource::estValide($document->endossement_url)) {
            $raisons[] = "porteur « {$nom} » sans URL d'endossement valide";
        }
        if (blank($document->endossement_date)) {
            $raisons[] = "porteur « {$nom} » sans date d'endossement";
        }
        if (blank($document->endossement_note)) {
            $raisons[] = "porteur « {$nom} » sans note d'endossement";
        }

        return $raisons;
    }
}
