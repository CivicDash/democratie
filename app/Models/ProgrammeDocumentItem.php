<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgrammeDocumentItem extends Model
{
    use HasFactory;

    protected $table = 'programme_document_items';

    protected $fillable = [
        'document_id', 'chapitre_numero', 'chapitre_titre', 'type', 'numero',
        'titre', 'texte_court', 'url_ancre', 'ordre',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ProgrammeDocument::class, 'document_id');
    }
}
