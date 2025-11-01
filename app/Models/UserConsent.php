<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consentement utilisateur RGPD
 * 
 * Conforme RGPD Art. 7 (conditions applicables au consentement)
 * 
 * @property int $id
 * @property int $user_id
 * @property string $consent_type data_processing|cookies|notifications|franceconnect_data|analytics
 * @property bool $is_granted
 * @property string $policy_version Version politique acceptée
 * @property string|null $consent_proof IP, user-agent, timestamp JSON
 * @property \Illuminate\Support\Carbon|null $granted_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class UserConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consent_type',
        'is_granted',
        'policy_version',
        'consent_proof',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Types de consentement disponibles
     */
    public const TYPE_DATA_PROCESSING = 'data_processing';
    public const TYPE_COOKIES = 'cookies';
    public const TYPE_NOTIFICATIONS = 'notifications';
    public const TYPE_FRANCECONNECT_DATA = 'franceconnect_data';
    public const TYPE_ANALYTICS = 'analytics';

    public const TYPES = [
        self::TYPE_DATA_PROCESSING,
        self::TYPE_COOKIES,
        self::TYPE_NOTIFICATIONS,
        self::TYPE_FRANCECONNECT_DATA,
        self::TYPE_ANALYTICS,
    ];

    /**
     * Utilisateur associé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accorder un consentement
     */
    public function grant(string $policyVersion, ?array $proofData = null): void
    {
        $this->update([
            'is_granted' => true,
            'policy_version' => $policyVersion,
            'consent_proof' => $proofData ? json_encode($proofData) : null,
            'granted_at' => now(),
            'revoked_at' => null,
        ]);
    }

    /**
     * Révoquer un consentement
     */
    public function revoke(): void
    {
        $this->update([
            'is_granted' => false,
            'revoked_at' => now(),
        ]);
    }

    /**
     * Vérifie si le consentement est actif
     */
    public function isActive(): bool
    {
        return $this->is_granted && !$this->revoked_at;
    }

    /**
     * Scope : consentements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_granted', true)
            ->whereNull('revoked_at');
    }

    /**
     * Scope : par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    /**
     * Scope : révoqués
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Crée une preuve de consentement (RGPD Art. 7.1)
     */
    public static function createProof(): array
    {
        return [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'url' => request()->fullUrl(),
        ];
    }
}
