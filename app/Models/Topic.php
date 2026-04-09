<?php

namespace App\Models;

use App\Traits\Taggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

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
    use HasFactory, Searchable, SoftDeletes, Taggable;

    /**
     * Get the route key for the model (utilise le slug au lieu de l'id).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Retrieve the model for a bound value.
     * Supporte à la fois le slug ET l'id numérique.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        // Si c'est un ID numérique, chercher par ID
        if (is_numeric($value)) {
            return $this->where('id', $value)->first();
        }

        // Sinon chercher par slug
        return $this->where('slug', $value)->first();
    }

    // Types d'idées citoyennes
    public const IDEA_TYPES = [
        'question' => [
            'label' => 'Question',
            'icon' => '❓',
            'color' => 'sky',
            'restricted' => false,
            'description' => 'Posez une question à la communauté',
            'requires' => ['category'],
        ],
        'poll' => [
            'label' => 'Sondage',
            'icon' => '📊',
            'color' => 'indigo',
            'restricted' => false,
            'description' => 'Mesurez l\'opinion avec des choix de réponse',
            'requires' => ['category', 'poll_options'],
        ],
        'discussion' => [
            'label' => 'Discussion',
            'icon' => '💬',
            'color' => 'slate',
            'restricted' => true,
            'description' => 'Ouvrez un sujet de société pour échanger',
            'requires' => ['category'],
        ],
        'proposal' => [
            'label' => 'Proposition',
            'icon' => '💡',
            'color' => 'emerald',
            'restricted' => false,
            'description' => 'Proposez une idée concrète',
            'requires' => ['category'],
        ],
        'debate' => [
            'label' => 'Débat',
            'icon' => '⚔️',
            'color' => 'amber',
            'restricted' => false,
            'description' => 'Lancez un débat Pour/Contre structuré',
            'requires' => ['category'],
        ],
        'interpellation' => [
            'label' => 'Interpellation',
            'icon' => '📣',
            'color' => 'rose',
            'restricted' => false,
            'description' => 'Posez une question directe à un élu',
            'requires' => ['category', 'elus'],
        ],
        'petition' => [
            'label' => 'Pétition',
            'icon' => '✍️',
            'color' => 'violet',
            'restricted' => false,
            'description' => 'Mobilisez pour une cause, collectez des signatures',
            'requires' => ['category'],
        ],
    ];

    // Types avec restrictions (pas de liens externes, pas d'images)
    public const RESTRICTED_TYPES = ['discussion'];

    /**
     * Vérifie si ce topic a des restrictions de contenu
     */
    public function hasContentRestrictions(): bool
    {
        return in_array($this->idea_type, self::RESTRICTED_TYPES);
    }

    // Scopes géographiques
    public const SCOPES = [
        'national' => ['label' => 'National', 'icon' => '🇫🇷'],
        'regional' => ['label' => 'Régional', 'icon' => '🗺️'],
        'departemental' => ['label' => 'Départemental', 'icon' => '📍'],
        'communal' => ['label' => 'Communal', 'icon' => '🏘️'],
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'category_id',
        'scope',
        'region_id',
        'department_id',
        'type',
        'idea_type',
        'loi_cod',
        'status',
        'author_id',
        'has_ballot',
        'voting_opens_at',
        'voting_deadline_at',
        'ballot_type',
        'ballot_options',
        'votes_pour',
        'votes_contre',
        'score',
        'published_at',
        'views_count',
        'rejection_reason',
        // Sondages
        'poll_type',
        'poll_max_choices',
        'poll_show_results_before_vote',
        'poll_allow_change_vote',
        'poll_ends_at',
        // Débat
        'debate_mode',
    ];

    protected $casts = [
        'has_ballot' => 'boolean',
        'voting_opens_at' => 'datetime',
        'voting_deadline_at' => 'datetime',
        'ballot_options' => 'array',
        'published_at' => 'datetime',
        'votes_pour' => 'integer',
        'votes_contre' => 'integer',
        'score' => 'integer',
        'views_count' => 'integer',
        // Sondages
        'poll_max_choices' => 'integer',
        'poll_show_results_before_vote' => 'boolean',
        'poll_allow_change_vote' => 'boolean',
        'poll_ends_at' => 'datetime',
        'debate_mode' => 'boolean',
    ];

    protected $appends = ['idea_type_info', 'scope_info', 'url'];

    /**
     * Catégorie du topic
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TopicCategory::class, 'category_id');
    }

    /**
     * Auteur du topic (legislator/admin)
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Loi associée (optionnel)
     */
    public function loi(): BelongsTo
    {
        return $this->belongsTo(Loi::class, 'loi_cod', 'loicod');
    }

    /**
     * Scope pour les topics liés à une loi
     */
    public function scopeForLoi($query, string $loiCod)
    {
        return $query->where('loi_cod', $loiCod);
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
     * Options de sondage (pour les topics de type 'poll')
     */
    public function pollOptions(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('position');
    }

    /**
     * Vérifie si c'est un sondage
     */
    public function isPoll(): bool
    {
        return $this->idea_type === 'poll';
    }

    /**
     * Vérifie si le sondage est actif (pas encore terminé)
     */
    public function isPollActive(): bool
    {
        if (! $this->isPoll()) {
            return false;
        }

        if ($this->poll_ends_at && $this->poll_ends_at->isPast()) {
            return false;
        }

        return $this->status === 'open';
    }

    /**
     * Total des votes du sondage
     */
    public function totalPollVotes(): int
    {
        return $this->pollOptions()->sum('votes_count');
    }

    /**
     * Vérifie si un utilisateur a déjà voté dans ce sondage
     */
    public function hasUserVotedInPoll(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return PollVote::whereIn('poll_option_id', $this->pollOptions()->pluck('id'))
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Obtenir les votes d'un utilisateur dans ce sondage
     */
    public function getUserPollVotes(int $userId): array
    {
        return PollVote::whereIn('poll_option_id', $this->pollOptions()->pluck('id'))
            ->where('user_id', $userId)
            ->pluck('poll_option_id')
            ->toArray();
    }

    /**
     * Vérifie si le mode débat est activé
     */
    public function isDebateMode(): bool
    {
        return $this->debate_mode || $this->idea_type === 'debate';
    }

    /**
     * Compter les arguments par position (pour le mode débat)
     */
    public function getDebateCounts(): array
    {
        $counts = $this->posts()
            ->whereNotNull('debate_position')
            ->selectRaw('debate_position, count(*) as count')
            ->groupBy('debate_position')
            ->pluck('count', 'debate_position')
            ->toArray();

        return [
            'for' => $counts['for'] ?? 0,
            'against' => $counts['against'] ?? 0,
            'neutral' => $counts['neutral'] ?? 0,
            'total' => array_sum($counts),
        ];
    }

    /**
     * Arguments "Pour"
     */
    public function forArguments()
    {
        return $this->posts()->where('debate_position', 'for');
    }

    /**
     * Arguments "Contre"
     */
    public function againstArguments()
    {
        return $this->posts()->where('debate_position', 'against');
    }

    /**
     * Bulletins de vote
     */
    public function ballots(): HasMany
    {
        return $this->hasMany(TopicBallot::class);
    }

    /**
     * Tags thématiques associés (nouveau système)
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_topic');
    }

    /**
     * Tags via table topic_tags
     */
    public function topicTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'topic_tags');
    }

    /**
     * Élus liés (interpellations, mentions)
     */
    public function elus(): HasMany
    {
        return $this->hasMany(TopicElu::class);
    }

    /**
     * Interpellations uniquement
     */
    public function interpellations(): HasMany
    {
        return $this->hasMany(TopicElu::class)->where('is_interpellation', true);
    }

    /**
     * Votes citoyens sur ce topic
     */
    public function topicVotes(): HasMany
    {
        return $this->hasMany(TopicVote::class);
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
        if (! $this->has_ballot) {
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
        if (! $this->has_ballot) {
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

    /**
     * Scope: par type d'idée
     */
    public function scopeByIdeaType($query, string $ideaType)
    {
        return $query->where('idea_type', $ideaType);
    }

    /**
     * Scope: propositions citoyennes
     */
    public function scopeProposals($query)
    {
        return $query->where('idea_type', 'proposal');
    }

    /**
     * Scope: interpellations
     */
    public function scopeInterpellations($query)
    {
        return $query->where('idea_type', 'interpellation');
    }

    /**
     * Scope: pétitions
     */
    public function scopePetitions($query)
    {
        return $query->where('idea_type', 'petition');
    }

    /**
     * Scope: publiés
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    /**
     * Scope: trending (basé sur le Wilson score)
     */
    public function scopeTrending($query)
    {
        return $query->published()
            ->orderByDesc('score')
            ->orderByDesc('votes_pour');
    }

    /**
     * Scope: récents
     */
    public function scopeRecent($query)
    {
        return $query->published()
            ->orderByDesc('published_at');
    }

    /**
     * Scope: avec élus liés
     */
    public function scopeWithElus($query)
    {
        return $query->whereHas('elus');
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Infos sur le type d'idée
     */
    public function getIdeaTypeInfoAttribute(): array
    {
        return self::IDEA_TYPES[$this->idea_type] ?? self::IDEA_TYPES['debate'];
    }

    /**
     * Infos sur le scope
     */
    public function getScopeInfoAttribute(): array
    {
        return self::SCOPES[$this->scope] ?? self::SCOPES['national'];
    }

    /**
     * URL du topic
     */
    public function getUrlAttribute(): string
    {
        return route('topics.show', $this->slug ?: $this->id);
    }

    /**
     * Total des votes
     */
    public function getTotalVotesAttribute(): int
    {
        return $this->votes_pour + $this->votes_contre;
    }

    /**
     * Pourcentage pour
     */
    public function getPctPourAttribute(): float
    {
        $total = $this->total_votes;

        return $total > 0 ? round(($this->votes_pour / $total) * 100, 1) : 0;
    }

    /**
     * Pourcentage contre
     */
    public function getPctContreAttribute(): float
    {
        $total = $this->total_votes;

        return $total > 0 ? round(($this->votes_contre / $total) * 100, 1) : 0;
    }

    /**
     * Générer un slug à partir du titre
     */
    public static function generateSlug(string $title): string
    {
        $slug = \Illuminate\Support\Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    // ========================================================================
    // CONTENT RESTRICTIONS (Discussions)
    // ========================================================================

    /**
     * Domaines autorisés dans le contenu
     */
    protected static array $allowedDomains = [
        'objectif2027.fr',
        'demo.objectif2027.fr',
        'civis-consilium.eu',
        'localhost',
    ];

    /**
     * Vérifie si un texte contient des liens externes
     */
    public static function containsExternalLinks(string $content): bool
    {
        // Trouver toutes les URLs
        if (preg_match_all('/https?:\/\/([^\s<>\[\]\/\)\"\']+)/i', $content, $matches)) {
            foreach ($matches[1] as $domain) {
                $domain = strtolower(preg_replace('/:\d+$/', '', $domain)); // Enlever le port
                $isAllowed = false;
                foreach (static::$allowedDomains as $allowed) {
                    if ($domain === $allowed || str_ends_with($domain, '.'.$allowed)) {
                        $isAllowed = true;
                        break;
                    }
                }
                if (! $isAllowed) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vérifie si un texte contient des médias (images, vidéos, iframes)
     */
    public static function containsMedia(string $content): bool
    {
        $mediaPatterns = [
            // Images markdown
            '/!\[.*?\]\(.*?\)/i',
            // Images HTML
            '/<img[^>]*>/i',
            // iframes
            '/<iframe[^>]*>/i',
            // Embeds
            '/<embed[^>]*>/i',
            '/<object[^>]*>/i',
            '/<video[^>]*>/i',
            '/<audio[^>]*>/i',
            // Base64 images
            '/data:image\/[^;]+;base64,/i',
        ];

        foreach ($mediaPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nettoie un contenu en supprimant les liens externes et médias
     * (garde uniquement le texte)
     */
    public static function sanitizeRestrictedContent(string $content): string
    {
        // Supprimer les images markdown
        $content = preg_replace('/!\[.*?\]\(.*?\)/i', '[image supprimée]', $content);

        // Supprimer les balises HTML non autorisées
        $content = strip_tags($content, '<p><br><strong><em><ul><ol><li><blockquote><h1><h2><h3><h4><h5><h6><a>');

        // Supprimer les liens externes dans les balises <a>
        $content = preg_replace_callback(
            '/<a[^>]*href\s*=\s*["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/i',
            function ($matches) {
                $url = $matches[1];
                $text = $matches[2];

                // Vérifier si c'est un lien interne
                if (preg_match('/^(\/|#|mailto:|tel:)/i', $url)) {
                    return $matches[0]; // Garder les liens internes relatifs
                }

                if (preg_match('/https?:\/\/([^\s<>\[\]\/]+)/i', $url, $urlMatch)) {
                    $domain = strtolower(preg_replace('/:\d+$/', '', $urlMatch[1]));
                    foreach (static::$allowedDomains as $allowed) {
                        if ($domain === $allowed || str_ends_with($domain, '.'.$allowed)) {
                            return $matches[0]; // Garder le lien
                        }
                    }
                }

                return $text.' [lien externe supprimé]';
            },
            $content
        );

        return $content;
    }

    // ========================================================================
    // BOOT
    // ========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($topic) {
            if (empty($topic->slug)) {
                $topic->slug = static::generateSlug($topic->title);
            }
            if (empty($topic->idea_type)) {
                $topic->idea_type = 'debate';
            }
        });

        static::updating(function ($topic) {
            if ($topic->isDirty('title') && $topic->getOriginal('slug') === \Illuminate\Support\Str::slug($topic->getOriginal('title'))) {
                $topic->slug = static::generateSlug($topic->title);
            }
        });
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
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'idea_type' => $this->idea_type,
            'scope' => $this->scope,
            'status' => $this->status,
            'region_id' => $this->region_id,
            'department_id' => $this->department_id,
            'author_name' => $this->author?->name,
            'votes_pour' => $this->votes_pour,
            'votes_contre' => $this->votes_contre,
            'score' => $this->score,
            'created_at' => $this->created_at?->timestamp,
            'published_at' => $this->published_at?->timestamp,
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
