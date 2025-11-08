<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Question au gouvernement (écrite ou orale)
 */
class QuestionGouvernement extends Model
{
    use HasFactory;

    protected $table = 'questions_gouvernement';

    protected $fillable = [
        'depute_senateur_id',
        'type',
        'numero',
        'date_depot',
        'date_reponse',
        'ministere',
        'titre',
        'question',
        'reponse',
        'statut',
        'url',
    ];

    protected $casts = [
        'date_depot' => 'date',
        'date_reponse' => 'date',
    ];

    public function deputeSenateur(): BelongsTo
    {
        return $this->belongsTo(DeputeSenateur::class, 'depute_senateur_id');
    }

    public function scopeEcrites($query)
    {
        return $query->where('type', 'ecrite');
    }

    public function scopeOrales($query)
    {
        return $query->where('type', 'orale');
    }

    public function scopeRepondues($query)
    {
        return $query->where('statut', 'repondu');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function getDelaiReponseJoursAttribute(): ?int
    {
        if (!$this->date_reponse) {
            return null;
        }
        return $this->date_depot->diffInDays($this->date_reponse);
    }
}

