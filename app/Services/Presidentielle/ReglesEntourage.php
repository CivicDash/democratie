<?php

namespace App\Services\Presidentielle;

use App\Models\IngestionProposition;
use App\Support\UrlSource;

/**
 * Ce qui rend publiable une proposition dans « Autour du candidat » — écrit une seule fois.
 *
 * La section affiche la parole d'un parti ou d'un groupe parlementaire, à côté de la fiche
 * du candidat et jamais dedans. Une citation y est attribuée à son locuteur réel, vérifiée
 * mot pour mot, et reliée à une source dont le porteur a prouvé son endossement.
 */
class ReglesEntourage
{
    public function __construct(private ReglesPorteur $porteur) {}

    /** @return list<string> vide = publiable « Autour du candidat » */
    public function raisons(IngestionProposition $p): array
    {
        $p->loadMissing('document');
        $raisons = [];

        if (! $p->estEntourage()) {
            $raisons[] = 'ni propos d\'un parti, ni document de parti ou de groupe : cette proposition relève du candidat';
        }
        if ($p->statut !== 'validee') {
            $raisons[] = 'proposition non validée pour « Autour du candidat » (statut '.$p->statut.')';
        }
        if (! $p->candidat_id) {
            $raisons[] = 'aucun candidat rattaché';
        }
        if (! $p->verbatim_verifie) {
            $raisons[] = 'citation non vérifiée mot pour mot contre la transcription';
        }
        if (filled($p->locuteur) && blank($p->role_locuteur)) {
            $raisons[] = "locuteur « {$p->locuteur} » sans rôle";
        }
        if (! UrlSource::estValide($p->source_url) && ! UrlSource::estValide($p->document?->url)) {
            $raisons[] = 'aucune URL de source valide';
        }
        if ($p->document) {
            $raisons = [...$raisons, ...$this->porteur->raisons($p->document)];
        }
        if ($p->mesure_id !== null) {
            $raisons[] = 'rattachée à une mesure : la parole d\'un parti n\'est jamais une mesure du candidat';
        }

        return $raisons;
    }
}
