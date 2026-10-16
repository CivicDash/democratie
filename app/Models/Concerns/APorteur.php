<?php

namespace App\Models\Concerns;

use App\Support\Porteur;
use App\Support\UrlSource;

/**
 * Porteur d'une source (prise de parole ou référentiel de programme).
 *
 * Avant la migration, les colonnes n'existent pas : tout se lit comme « candidat », ce qui
 * est exactement l'état de l'existant.
 */
trait APorteur
{
    public function porteurType(): string
    {
        return $this->porteur_type ?? 'candidat';
    }

    public function porteurEstEntourage(): bool
    {
        return Porteur::estEntourage($this->porteurType());
    }

    /** @return array{type: string, libelle: ?string, endossement: ?array{url: ?string, date: ?string, note: ?string}} */
    public function porteurExport(): array
    {
        $type = $this->porteurType();

        return [
            'type' => $type,
            'libelle' => $this->porteur_libelle,
            'endossement' => $type === 'candidat' ? null : [
                'url' => UrlSource::nettoyer($this->endossement_url),
                'date' => $this->endossement_date?->toDateString(),
                'note' => $this->endossement_note,
            ],
        ];
    }
}
