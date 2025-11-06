<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use App\Traits\Taggable;

/**
 * Sujet de débat, projet de loi ou référendum
 * 
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $scope national|region|dept
 * @property int|null $region_id
 * @property int|null $department_id
 * @property string $type debate|bill|referendum
 * @property string $status draft|open|closed|archived
 * @property int $author_id
 * @property bool $has_ballot
 * @property \Illuminate\Support\Carbon|null $voting_opens_at
 * @property \Illuminate\Support\Carbon|null $voting_deadline_at
 * @property string|null $ballot_type yes_no|multiple_choice|preferential
 * @property array|null $ballot_options
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Topic extends Model
{
    use HasFactory, SoftDeletes, Searchable, Taggable;

    protected $fillable = [
        'title',
        'description',
        'scope',
        'region_id',
        'department_id',
        'type',
        'status',
        'author_id',
        'has_ballot',
        'voting_opens_at',
        'voting_deadline_at',
        'ballot_type',
        'ballot_options',
    ];

    protected $casts = [
        'has_ballot' => 'boolean',
        'voting_opens_at' => 'datetime',
        'voting_deadline_at' => 'datetime',
        'ballot_options' => 'array',
    ];

    /**
     * Auteur du topic (legislator/admin)
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Région (si scope region ou dept)
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(TerritoryRegion::class, 'region_id');
    }

    /**
     * Département (si scope dept)
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(TerritoryDepartment::class, 'department_id');
    }

    /**
     * Posts du topic
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Jetons de vote
     */
    public function ballotTokens(): HasMany
    {
        return $this->hasMany(BallotToken::class);
    }

    /**
     * Bulletins de vote
     */
    public function ballots(): HasMany
    {
        return $this->hasMany(TopicBallot::class);
    }

    /**
     * Documents attachés
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Signalements
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Vues du topic
     */
    public function views(): MorphMany
    {
        return $this->morphMany(View::class, 'viewable');
    }

    /**
     * Vérifie si le scrutin est ouvert
     */
    public function isVotingOpen(): bool
    {
        if (!$this->has_ballot) {
            return false;
        }

        $now = now();
        return $now->gte($this->voting_opens_at) && $now->lt($this->voting_deadline_at);
    }

    /**
     * Vérifie si le scrutin est terminé
     */
    public function isVotingClosed(): bool
    {
        if (!$this->has_ballot) {
            return false;
        }

        return now()->gte($this->voting_deadline_at);
    }

    /**
     * Vérifie si les résultats peuvent être révélés
     */
    public function canRevealResults(): bool
    {
        return $this->has_ballot && $this->isVotingClosed();
    }

    /**
     * Scope: topics ouverts
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope: topics par scope territorial
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope: topics avec scrutin actif
     */
    public function scopeWithActiveVoting($query)
    {
        return $query->where('has_ballot', true)
            ->where('voting_opens_at', '<=', now())
            ->where('voting_deadline_at', '>', now());
    }

    /**
     * Scope: topics par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: débats uniquement
     */
    public function scopeDebates($query)
    {
        return $query->where('type', 'debate');
    }

    /**
     * Scope: projets de loi
     */
    public function scopeBills($query)
    {
        return $query->where('type', 'bill');
    }

    /**
     * Scope: référendums
     */
    public function scopeReferendums($query)
    {
        return $query->where('type', 'referendum');
    }

    // ========================================================================
    // SCOUT / MEILISEARCH
    // ========================================================================

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'scope' => $this->scope,
            'status' => $this->status,
            'region_id' => $this->region_id,
            'department_id' => $this->department_id,
            'author_name' => $this->author?->name,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'topics_index';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }
}

