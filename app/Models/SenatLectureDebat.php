<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Liaison entre une lecture (examen d'un texte) et une séance de débat
 */
class SenatLectureDebat extends Model
{
    protected $table = 'senat_lectures_debats';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'lecture_id',
        'date_seance',
    ];

    protected $casts = [
        'date_seance' => 'datetime',
    ];

    /**
     * Relations
     */
    public function debat(): BelongsTo
    {
        return $this->belongsTo(SenatDebat::class, 'date_seance', 'date_seance');
    }

    /**
     * La lecture peut être liée à un dossier législatif via l'ID de lecture
     * Format: {session}{type}{numero} ex: "2024100138" pour le texte 138 de 2024
     */
    public function getLectureInfoAttribute(): array
    {
        $id = $this->lecture_id;
        
        // Essayer d'extraire les informations du format
        // Le format est généralement AAAA + type (1 ou 2 chiffres) + numéro
        if (preg_match('/^(\d{4})(\d{1,2})(\d+)$/', $id, $matches)) {
            return [
                'session' => $matches[1],
                'type' => $matches[2],
                'numero' => $matches[3],
            ];
        }
        
        return [
            'id' => $id,
        ];
    }
}
