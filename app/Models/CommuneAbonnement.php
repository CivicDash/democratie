<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommuneAbonnement extends Model
{
    protected $fillable = [
        'user_id',
        'commune_code_insee',
        'notif_actus',
        'notif_evenements',
        'notif_forum',
        'notif_email',
    ];

    protected $casts = [
        'notif_actus' => 'boolean',
        'notif_evenements' => 'boolean',
        'notif_forum' => 'boolean',
        'notif_email' => 'boolean',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function communePage()
    {
        return CommunePage::where('code_insee', $this->commune_code_insee)->first();
    }

    public function ville()
    {
        return Ville::where('code_insee', $this->commune_code_insee)->first();
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeParCommune($query, string $codeInsee)
    {
        return $query->where('commune_code_insee', $codeInsee);
    }

    public function scopeVeutActus($query)
    {
        return $query->where('notif_actus', true);
    }

    public function scopeVeutEvenements($query)
    {
        return $query->where('notif_evenements', true);
    }

    public function scopeVeutForum($query)
    {
        return $query->where('notif_forum', true);
    }

    public function scopeVeutEmail($query)
    {
        return $query->where('notif_email', true);
    }
}
