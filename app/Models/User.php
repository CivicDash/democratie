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
}
