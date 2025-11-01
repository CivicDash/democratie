<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'franceconnect_sub',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
        ];
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
     * Vérifie si l'user peut poster
     */
    public function canPost(): bool
    {
        return !$this->isMuted() && !$this->isBanned();
    }

    /**
     * Vérifie si l'user peut voter sur un scrutin
     */
    public function canVoteOn(Topic $topic): bool
    {
        if (!$topic->has_ballot || !$topic->isVotingOpen()) {
            return false;
        }

        // Vérifie si l'user a déjà un token pour ce topic
        $token = $this->ballotTokens()
            ->forTopic($topic->id)
            ->first();

        if (!$token) {
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
     * Compte les signalements actifs contre l'user
     */
    public function activeReportsCount(): int
    {
        return Report::where('reportable_type', Post::class)
            ->whereIn('reportable_id', $this->posts->pluck('id'))
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

        if (!$consent) {
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
        if (!$this->profile) {
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
        return !$this->isPublicFigure();
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
