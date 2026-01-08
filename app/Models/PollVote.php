<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote d'un utilisateur sur une option de sondage
 * 
 * @property int $id
 * @property int $poll_option_id
 * @property int $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class PollVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_option_id',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Option de sondage
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }

    /**
     * Utilisateur qui a voté
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Topic du sondage (via l'option)
     */
    public function topic(): ?Topic
    {
        return $this->option?->topic;
    }

    /**
     * Créer un vote avec les métadonnées de la requête
     */
    public static function createFromRequest(
        int $optionId,
        int $userId,
        ?\Illuminate\Http\Request $request = null
    ): self {
        return self::create([
            'poll_option_id' => $optionId,
            'user_id' => $userId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? substr($request->userAgent(), 0, 500) : null,
        ]);
    }
}
