<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteSenat extends Model
{
    protected $table = 'votes_senat';

    // Note: Cette table est une VUE SQL en lecture seule
    protected $fillable = [];

    protected $casts = [
        'date_vote' => 'date',
    ];

    /**
     * Relations
     */
    public function scrutin(): BelongsTo
    {
        return $this->belongsTo(ScrutinSenat::class, 'scrutin_id', 'id');
    }

    public function senateur(): BelongsTo
    {
        // senateur_matricule dans la vue correspond au matricule du sénateur
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }

    /**
     * Scopes
     */
    public function scopePour($query)
    {
        return $query->where('position', 'pour');
    }

    public function scopeContre($query)
    {
        return $query->where('position', 'contre');
    }

    public function scopeAbstention($query)
    {
        return $query->where('position', 'abstention');
    }

    public function scopeNonVotant($query)
    {
        return $query->where('position', 'non_votant');
    }
}
