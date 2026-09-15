<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Une position proposée au choix. Elle ne porte pas de candidat : ce sont les MESURES
 * rattachées qui en portent, ce qui permet à une même position d'en créditer plusieurs.
 */
class QuizOption extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'question_id', 'libelle', 'ordre'];

    protected $casts = ['ordre' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $o) {
            $o->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }

    public function mesures(): BelongsToMany
    {
        return $this->belongsToMany(ProgrammeMesure::class, 'quiz_option_mesure', 'option_id', 'mesure_id')
            ->withTimestamps();
    }
}
