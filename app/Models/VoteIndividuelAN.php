<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteIndividuelAN extends Model
{
    use HasFactory;

    protected $table = 'votes_individuels_an';

    protected $fillable = [
        'scrutin_ref',
        'acteur_ref',
        'mandat_ref',
        'groupe_ref',
        'position',
        'position_groupe',
        'numero_place',
        'par_delegation',
        'cause_non_vote',
    ];

    protected $casts = [
        'par_delegation' => 'boolean',
    ];

    /**
     * Relations
     */
    public function scrutin(): BelongsTo
    {
        return $this->belongsTo(ScrutinAN::class, 'scrutin_ref', 'uid');
    }

    public function acteur(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'acteur_ref', 'uid');
    }

    public function groupe(): BelongsTo
    {
        return $this->belongsTo(OrganeAN::class, 'groupe_ref', 'uid');
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

    public function scopeParActeur($query, string $acteurUid)
    {
        return $query->where('acteur_ref', $acteurUid);
    }

    public function scopeParGroupe($query, string $groupeUid)
    {
        return $query->where('groupe_ref', $groupeUid);
    }

    /**
     * Accessors
     */
    public function getAVoteAttribute(): bool
    {
        return $this->position !== 'non_votant';
    }

    public function getEstRebelle(): bool
    {
        // Un député est "rebelle" si sa position diffère de celle de son groupe
        if (!$this->position_groupe || $this->position_groupe === 'mixte') {
            return false;
        }
        
        return $this->position !== $this->position_groupe;
    }
}

