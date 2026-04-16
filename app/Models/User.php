<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'date_of_birth',
        'password',
        'profile_photo_path',
        'franceconnect_sub',
        'is_public_profile',
        'elu_bio',
        'twitter_handle',
        'facebook_url',
        'website_url',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'two_factor_enabled',
        'email_visible_to_admin',
        // Modération photo de profil
        'profile_photo_status',
        'profile_photo_rejection_reason',
        'profile_photo_submitted_at',
        'profile_photo_moderated_at',
        'profile_photo_moderated_by',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'is_public_profile' => 'boolean',
            'is_verified_elu' => 'boolean',
            'verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'is_association_member' => 'boolean',
            'association_member_since' => 'datetime',
            'member_since' => 'date',
            'member_until' => 'date',
            'email_visible_to_admin' => 'boolean',
            'profile_photo_submitted_at' => 'datetime',
            'profile_photo_moderated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
        ];
    }

    /**
     * Get the URL of the user's profile photo.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo_path) {
            return asset('storage/'.$this->profile_photo_path);
        }

        // Gravatar fallback
        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }

    /**
     * Types de membres de l'association
     */
    public const MEMBER_TYPES = [
        'adherent' => 'Adhérent',
        'bienfaiteur' => 'Membre bienfaiteur',
        'fondateur' => 'Membre fondateur',
        'honneur' => 'Membre d\'honneur',
    ];

    /**
     * Vérifie si le membre est actif (cotisation à jour)
     */
    public function isActiveMember(): bool
    {
        if (! $this->is_association_member) {
            return false;
        }

        // Membre à vie (member_until = null)
        if ($this->member_until === null) {
            return true;
        }

        return $this->member_until->isFuture();
    }

    /**
     * Label du type de membre
     */
    public function getMemberTypeLabelAttribute(): ?string
    {
        return self::MEMBER_TYPES[$this->member_type] ?? null;
    }

    /**
     * Vérifie si c'est un compte de démonstration
     */
    public function isDemoAccount(): bool
    {
        $demoPatterns = [
            'demo@civicdash.fr',
            'demo-elu@civicdash.fr',
            '@demo.civicdash.fr',
            '@demo.assemblee-nationale.fr',
            '@demo.senat.fr',
        ];

        foreach ($demoPatterns as $pattern) {
            if (str_contains($this->email, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut activer la 2FA
     */
    public function canEnableTwoFactor(): bool
    {
        // Les comptes démo ne peuvent pas activer la 2FA
        if ($this->isDemoAccount()) {
            return false;
        }

        // Les comptes FranceConnect n'ont pas besoin de 2FA
        if ($this->franceconnect_sub !== null) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si la 2FA est activée et confirmée
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Vérifie si la 2FA devrait être recommandée (élu ou admin sans 2FA)
     */
    public function shouldEnableTwoFactor(): bool
    {
        if ($this->two_factor_enabled) {
            return false;
        }

        // Les comptes démo ne peuvent pas activer la 2FA
        if ($this->isDemoAccount()) {
            return false;
        }

        // FranceConnect users don't need 2FA (already secure)
        if ($this->franceconnect_sub !== null) {
            return false;
        }

        return $this->is_verified_elu || $this->hasRole('admin') || $this->hasRole('super-admin');
    }

    // ==================== Élu Relations ====================

    /**
     * Données de l'élu selon le type
     */
    public function getEluDataAttribute()
    {
        if (! $this->elu_type || ! $this->elu_ref) {
            return null;
        }

        return match ($this->elu_type) {
            'depute' => ActeurAN::find($this->elu_ref),
            'senateur' => Senateur::where('matricule', $this->elu_ref)->first(),
            'maire' => Maire::find($this->elu_ref),
            default => null,
        };
    }

    /**
     * Interpellations reçues par cet élu
     */
    public function interpellationsReceived(): HasMany
    {
        return $this->hasMany(TopicElu::class, 'elu_id', 'elu_ref')
            ->where('elu_type', $this->elu_type)
            ->where('is_interpellation', true);
    }

    /**
     * Vérifie si l'utilisateur est un élu vérifié
     */
    public function isVerifiedElu(): bool
    {
        return $this->is_verified_elu && $this->elu_type !== null;
    }

    /**
     * Vérifie si l'utilisateur peut répondre aux interpellations
     */
    public function canRespondToInterpellations(): bool
    {
        return $this->isVerifiedElu();
    }

    /**
     * Scope: élus vérifiés uniquement
     */
    public function scopeVerifiedElus($query)
    {
        return $query->where('is_verified_elu', true)
            ->whereNotNull('elu_type');
    }

    /**
     * Scope: élus avec profil public
     */
    public function scopePublicElus($query)
    {
        return $query->verifiedElus()->where('is_public_profile', true);
    }

    // ==================== Relations ====================

    /**
     * Profil citoyen (1:1)
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Consentements RGPD
     */
    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    /**
     * Topics créés (author)
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'author_id');
    }

    /**
     * Posts créés
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Votes sur posts
     */
    public function postVotes(): HasMany
    {
        return $this->hasMany(PostVote::class);
    }

    /**
     * Signalements émis
     */
    public function reportsCreated(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * Signalements traités (modérateur)
     */
    public function reportsHandled(): HasMany
    {
        return $this->hasMany(Report::class, 'moderator_id');
    }

    /**
     * Sanctions reçues
     */
    public function sanctions(): HasMany
    {
        return $this->hasMany(Sanction::class);
    }

    /**
     * Sanctions données (modérateur)
     */
    public function sanctionsGiven(): HasMany
    {
        return $this->hasMany(Sanction::class, 'moderator_id');
    }

    /**
     * Jetons de vote
     */
    public function ballotTokens(): HasMany
    {
        return $this->hasMany(BallotToken::class);
    }

    /**
     * Allocations budgétaires
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(UserAllocation::class);
    }

    /**
     * Documents uploadés
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploader_id');
    }

    /**
     * Vérifications effectuées (journalist/ong)
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class, 'verifier_id');
    }

    // ==================== Helper Methods ====================

    /**
     * Vérifie si l'user est mute
     */
    public function isMuted(): bool
    {
        return $this->sanctions()
            ->mutes()
            ->active()
            ->exists();
    }

    /**
     * Vérifie si l'user est banni
     */
    public function isBanned(): bool
    {
        return $this->sanctions()
            ->bans()
            ->active()
            ->exists();
    }

    /**
     * Vérifie si l'user peut poster (bloqué pour démo)
     */
    public function canPost(): bool
    {
        if ($this->isDemoAccount()) {
            return false;
        }

        return ! $this->isMuted() && ! $this->isBanned();
    }

    /**
     * Vérifie si l'user peut voter (bloqué pour démo)
     */
    public function canVote(): bool
    {
        if ($this->isDemoAccount()) {
            return false;
        }

        return ! $this->isMuted() && ! $this->isBanned();
    }

    /**
     * Vérifie si l'user peut commenter (bloqué pour démo)
     */
    public function canComment(): bool
    {
        if ($this->isDemoAccount()) {
            return false;
        }

        return ! $this->isMuted() && ! $this->isBanned();
    }

    /**
     * Vérifie si l'user est en lecture seule
     */
    public function isReadOnly(): bool
    {
        return $this->isDemoAccount();
    }

    /**
     * Retourne le rôle principal de l'utilisateur
     */
    public function getPrimaryRoleAttribute(): string
    {
        // Priorité : admin > moderator > legislator > citizen
        if ($this->hasRole('admin') || $this->hasRole('super-admin')) {
            return 'admin';
        }
        if ($this->hasRole('moderator')) {
            return 'moderator';
        }
        if ($this->hasRole('legislator') || $this->is_verified_elu) {
            return 'elu';
        }

        return 'citizen';
    }

    /**
     * Retourne le label du rôle principal
     */
    public function getPrimaryRoleLabelAttribute(): string
    {
        return match ($this->primary_role) {
            'admin' => 'Administrateur',
            'moderator' => 'Modérateur',
            'elu' => 'Élu',
            'citizen' => 'Citoyen',
            default => 'Citoyen',
        };
    }

    /**
     * Vérifie si l'utilisateur peut modérer
     */
    public function canModerate(): bool
    {
        return $this->hasRole(['admin', 'super-admin', 'moderator']);
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'super-admin']);
    }

    /**
     * Vérifie si l'user peut voter sur un scrutin
     */
    public function canVoteOn(Topic $topic): bool
    {
        if (! $topic->has_ballot || ! $topic->isVotingOpen()) {
            return false;
        }

        // Vérifie si l'user a déjà un token pour ce topic
        $token = $this->ballotTokens()
            ->forTopic($topic->id)
            ->first();

        if (! $token) {
            return true; // Peut obtenir un token
        }

        return $token->isValid(); // Peut voter si token valide
    }

    /**
     * Vérifie si l'user a déjà voté sur un scrutin
     */
    public function hasVotedOn(Topic $topic): bool
    {
        return $this->ballotTokens()
            ->forTopic($topic->id)
            ->consumed()
            ->exists();
    }

    /**
     * Compte les signalements actifs créés par l'user
     */
    public function activeReportsCount(): int
    {
        return Report::where('reporter_id', $this->id)
            ->whereIn('status', ['pending', 'reviewing'])
            ->count();
    }

    /**
     * Vérifie si l'user a complété son allocation budgétaire
     */
    public function hasCompletedBudgetAllocation(): bool
    {
        return UserAllocation::validateUserTotal($this->id);
    }

    /**
     * Scope: users avec un profil
     */
    public function scopeWithProfile($query)
    {
        return $query->has('profile');
    }

    /**
     * Scope: users vérifiés
     */
    public function scopeVerified($query)
    {
        return $query->whereHas('profile', function ($q) {
            $q->where('is_verified', true);
        });
    }

    /**
     * Scope: users actifs (non bannis)
     */
    public function scopeActive($query)
    {
        return $query->whereDoesntHave('sanctions', function ($q) {
            $q->bans()->active();
        });
    }

    // ==================== RGPD Methods ====================

    /**
     * Vérifie si l'utilisateur a donné son consentement pour un type donné
     */
    public function hasConsent(string $type): bool
    {
        return $this->consents()
            ->ofType($type)
            ->active()
            ->exists();
    }

    /**
     * Accorde un consentement RGPD
     */
    public function grantConsent(string $type, string $policyVersion): void
    {
        $consent = $this->consents()->ofType($type)->first();

        if (! $consent) {
            $consent = $this->consents()->create([
                'consent_type' => $type,
                'is_granted' => false,
                'policy_version' => $policyVersion,
            ]);
        }

        $consent->grant($policyVersion, UserConsent::createProof());
    }

    /**
     * Révoque un consentement RGPD
     */
    public function revokeConsent(string $type): void
    {
        $this->consents()
            ->ofType($type)
            ->active()
            ->each(fn ($consent) => $consent->revoke());
    }

    /**
     * Récupère le nom d'affichage public (anonyme ou réel selon is_public_figure)
     */
    public function getDisplayNameAttribute(): string
    {
        if (! $this->profile) {
            return $this->name;
        }

        // Si compte public (journaliste, personnalité), afficher nom réel
        if ($this->profile->is_public_figure) {
            return $this->name;
        }

        // Sinon, afficher pseudonyme anonyme
        return $this->profile->display_name;
    }

    /**
     * Vérifie si l'utilisateur est un compte public (transparent)
     */
    public function isPublicFigure(): bool
    {
        return $this->profile && $this->profile->is_public_figure;
    }

    /**
     * Vérifie si l'utilisateur est anonyme (citoyen standard)
     */
    public function isAnonymous(): bool
    {
        return ! $this->isPublicFigure();
    }

    /**
     * Exportation données RGPD (Art. 20 - Portabilité)
     */
    public function exportPersonalData(): array
    {
        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'email_verified_at' => $this->email_verified_at,
                'created_at' => $this->created_at,
            ],
            'profile' => $this->profile ? [
                'display_name' => $this->profile->display_name,
                'scope' => $this->profile->scope,
                'is_verified' => $this->profile->is_verified,
                'is_public_figure' => $this->profile->is_public_figure,
            ] : null,
            'consents' => $this->consents->map(fn ($consent) => [
                'type' => $consent->consent_type,
                'granted' => $consent->is_granted,
                'granted_at' => $consent->granted_at,
            ]),
            'topics' => $this->topics->pluck('title'),
            'posts_count' => $this->posts()->count(),
            'votes_count' => $this->postVotes()->count(),
        ];
    }
}
