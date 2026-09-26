<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Série Eurostat d'une comparaison européenne. Deux états coexistent : ce que le site
 * montre (`series_publiees`, relue) et ce que la dernière extraction a trouvé
 * (`series_detectees`, en attente). Une révision mensuelle n'atteint jamais le site sans
 * qu'un humain ait relu la différence — et les phrases qui citent ces chiffres.
 */
class EurostatIndicateur extends Model
{
    protected $fillable = [
        'code', 'titre', 'unite', 'note_methodo', 'pertinence', 'sources',
        'series_publiees', 'extraction_publiee', 'series_detectees', 'extraction_detectee',
        'diff', 'valide_par', 'valide_at',
    ];

    protected $casts = [
        'sources' => 'array',
        'series_publiees' => 'array',
        'series_detectees' => 'array',
        'diff' => 'array',
        'extraction_publiee' => 'date',
        'extraction_detectee' => 'date',
        'valide_at' => 'datetime',
    ];

    /** `a_jour`, `revision` (une série publiée a changé) ou `nouveau` (jamais publié). */
    public function etat(): string
    {
        if ($this->series_detectees === null) {
            return 'a_jour';
        }

        return $this->series_publiees === null ? 'nouveau' : 'revision';
    }

    public function estPublie(): bool
    {
        return $this->series_publiees !== null;
    }
}
