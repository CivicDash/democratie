<?php

namespace App\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RateLimitService
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    /**
     * Limites par défaut
     */
    const LIMIT_API_GENERAL = 60;           // 60 req/min
    const LIMIT_API_AUTHENTICATED = 120;    // 120 req/min (authentifié)
    const LIMIT_LOGIN = 5;                  // 5 tentatives/min
    const LIMIT_REGISTER = 3;               // 3 inscriptions/heure
    const LIMIT_VOTE = 10;                  // 10 votes/heure
    const LIMIT_POST = 20;                  // 20 posts/heure
    const LIMIT_REPORT = 10;                // 10 signalements/heure
    const LIMIT_DOCUMENT_UPLOAD = 5;        // 5 uploads/heure
    const LIMIT_BUDGET_ALLOCATE = 30;       // 30 allocations/heure

    /**
     * Vérifier si une action est rate limitée
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->limiter->tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Incrémenter le compteur d'une action
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        return $this->limiter->hit($key, $decaySeconds);
    }

    /**
     * Obtenir le nombre de tentatives restantes
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        return $this->limiter->remaining($key, $maxAttempts);
    }

    /**
     * Obtenir le nombre de secondes avant réinitialisation
     */
    public function availableIn(string $key): int
    {
        return $this->limiter->availableIn($key);
    }

    /**
     * Réinitialiser le compteur
     */
    public function clear(string $key): void
    {
        $this->limiter->clear($key);
    }

    /**
     * Clé pour les tentatives de login
     */
    public function loginKey(Request $request): string
    {
        return 'login:' . Str::lower($request->input('email')) . '|' . $request->ip();
    }

    /**
     * Clé pour les inscriptions
     */
    public function registerKey(Request $request): string
    {
        return 'register:' . $request->ip();
    }

    /**
     * Clé pour les votes
     */
    public function voteKey(Request $request, int $topicId): string
    {
        $user = $request->user();
        return 'vote:' . ($user ? $user->id : $request->ip()) . ':' . $topicId;
    }

    /**
     * Clé pour les posts
     */
    public function postKey(Request $request): string
    {
        $user = $request->user();
        return 'post:' . ($user ? $user->id : $request->ip());
    }

    /**
     * Clé pour les signalements
     */
    public function reportKey(Request $request): string
    {
        $user = $request->user();
        return 'report:' . ($user ? $user->id : $request->ip());
    }

    /**
     * Clé pour les uploads de documents
     */
    public function documentUploadKey(Request $request): string
    {
        $user = $request->user();
        return 'document:upload:' . ($user ? $user->id : $request->ip());
    }

    /**
     * Clé pour les allocations budget
     */
    public function budgetAllocationKey(Request $request): string
    {
        return 'budget:allocate:' . $request->user()->id;
    }

    /**
     * Clé pour l'API générale
     */
    public function apiKey(Request $request): string
    {
        $user = $request->user();
        return 'api:' . ($user ? 'user:' . $user->id : 'ip:' . $request->ip());
    }

    /**
     * Vérifier et bloquer si trop de tentatives (avec exception)
     */
    public function ensureNotRateLimited(string $key, int $maxAttempts, string $message = 'Trop de tentatives. Veuillez réessayer plus tard.'): void
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->availableIn($key);
            $minutes = ceil($seconds / 60);
            
            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(
                $message . " Réessayez dans {$minutes} minute(s)."
            );
        }

        $this->hit($key);
    }

    /**
     * Appliquer un rate limit avec décroissance personnalisée
     */
    public function attempt(string $key, int $maxAttempts, callable $callback, int $decaySeconds = 60): mixed
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $this->hit($key, $decaySeconds);

        return $callback();
    }

    /**
     * Rate limit pour login
     */
    public function checkLoginLimit(Request $request): void
    {
        $key = $this->loginKey($request);
        $this->ensureNotRateLimited(
            $key,
            self::LIMIT_LOGIN,
            'Trop de tentatives de connexion.'
        );
    }

    /**
     * Rate limit pour inscription
     */
    public function checkRegisterLimit(Request $request): void
    {
        $key = $this->registerKey($request);
        $this->ensureNotRateLimited(
            $key,
            self::LIMIT_REGISTER,
            'Trop d\'inscriptions depuis cette IP.'
        );
    }

    /**
     * Rate limit pour vote
     */
    public function checkVoteLimit(Request $request, int $topicId): void
    {
        $key = $this->voteKey($request, $topicId);
        $this->ensureNotRateLimited(
            $key,
            self::LIMIT_VOTE,
            'Vous votez trop rapidement.'
        );
    }

    /**
     * Rate limit pour post
     */
    public function checkPostLimit(Request $request): void
    {
        $key = $this->postKey($request);
        $this->ensureNotRateLimited(
            $key,
            self::LIMIT_POST,
            'Vous publiez trop de messages.'
        );
    }

    /**
     * Rate limit pour signalement
     */
    public function checkReportLimit(Request $request): void
    {
        $key = $this->reportKey($request);
        $this->ensureNotRateLimited(
            $key,
            self::LIMIT_REPORT,
            'Vous signalez trop de contenu.'
        );
    }

    /**
     * Rate limit pour upload de document
     */
    public function checkDocumentUploadLimit(Request $request): void
    {
        $key = $this->documentUploadKey($request);
        $this->ensureNotRateLimited(
            $key,
            self::LIMIT_DOCUMENT_UPLOAD,
            'Vous téléversez trop de documents.'
        );
    }

    /**
     * Rate limit pour allocation budget
     */
    public function checkBudgetAllocationLimit(Request $request): void
    {
        $key = $this->budgetAllocationKey($request);
        $this->ensureNotRateLimited(
            $key,
            self::LIMIT_BUDGET_ALLOCATE,
            'Vous modifiez votre budget trop fréquemment.'
        );
    }

    /**
     * Obtenir les headers de rate limit pour la réponse
     */
    public function getRateLimitHeaders(string $key, int $maxAttempts): array
    {
        return [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $this->remaining($key, $maxAttempts)),
            'X-RateLimit-Reset' => now()->addSeconds($this->availableIn($key))->timestamp,
        ];
    }
}

