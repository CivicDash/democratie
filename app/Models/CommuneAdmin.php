<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommuneAdmin extends Model
{
    protected $fillable = [
        'commune_page_id',
        'user_id',
        'role',
        'peut_publier_actus',
        'peut_gerer_evenements',
        'peut_envoyer_notifications',
        'peut_modifier_page',
        'peut_deleguer',
        'delegue_par',
        'expire_le',
    ];

    protected $casts = [
        'peut_publier_actus' => 'boolean',
        'peut_gerer_evenements' => 'boolean',
        'peut_envoyer_notifications' => 'boolean',
        'peut_modifier_page' => 'boolean',
        'peut_deleguer' => 'boolean',
        'expire_le' => 'date',
    ];

    public const PERMISSIONS_PAR_ROLE = [
        'maire' => [
            'peut_publier_actus' => true,
            'peut_gerer_evenements' => true,
            'peut_envoyer_notifications' => true,
            'peut_modifier_page' => true,
            'peut_deleguer' => true,
        ],
        'adjoint' => [
            'peut_publier_actus' => true,
            'peut_gerer_evenements' => true,
            'peut_envoyer_notifications' => true,
            'peut_modifier_page' => false,
            'peut_deleguer' => false,
        ],
        'delegue' => [
            'peut_publier_actus' => true,
            'peut_gerer_evenements' => true,
            'peut_envoyer_notifications' => false,
            'peut_modifier_page' => false,
            'peut_deleguer' => false,
        ],
        'communication' => [
            'peut_publier_actus' => true,
            'peut_gerer_evenements' => false,
            'peut_envoyer_notifications' => false,
            'peut_modifier_page' => false,
            'peut_deleguer' => false,
        ],
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function communePage(): BelongsTo
    {
        return $this->belongsTo(CommunePage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delegateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegue_par');
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getEstExpireAttribute(): bool
    {
        return $this->expire_le && $this->expire_le->isPast();
    }

    public function getEstMaireAttribute(): bool
    {
        return $this->role === 'maire';
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'maire' => 'Maire',
            'adjoint' => 'Adjoint(e)',
            'delegue' => 'Délégué(e)',
            'communication' => 'Chargé(e) de communication',
            default => ucfirst($this->role),
        };
    }

    // ========================================================================
    // MÉTHODES
    // ========================================================================

    public function peutFaire(string $permission): bool
    {
        if ($this->est_expire) {
            return false;
        }

        return $this->$permission ?? false;
    }

    public static function creerAvecRole(CommunePage $page, User $user, string $role, ?User $delegateur = null): self
    {
        $permissions = self::PERMISSIONS_PAR_ROLE[$role] ?? self::PERMISSIONS_PAR_ROLE['delegue'];

        return static::create(array_merge([
            'commune_page_id' => $page->id,
            'user_id' => $user->id,
            'role' => $role,
            'delegue_par' => $delegateur?->id,
        ], $permissions));
    }
}
