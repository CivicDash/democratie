<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Durées de cache (en secondes)
     */
    const CACHE_FOREVER = 0; // Permanent jusqu'à invalidation
    const CACHE_1_HOUR = 3600;
    const CACHE_4_HOURS = 14400;
    const CACHE_1_DAY = 86400;
    const CACHE_1_WEEK = 604800;

    /**
     * Préfixes des clés de cache
     */
    const PREFIX_VOTE_RESULTS = 'vote:results:';
    const PREFIX_BUDGET_STATS = 'budget:stats';
    const PREFIX_BUDGET_AVERAGES = 'budget:averages';
    const PREFIX_BUDGET_RANKING = 'budget:ranking';
    const PREFIX_MODERATION_STATS = 'moderation:stats';
    const PREFIX_DOCUMENT_STATS = 'documents:stats';
    const PREFIX_TOPIC_STATS = 'topic:stats:';
    const PREFIX_USER_ALLOCATIONS = 'user:allocations:';

    /**
     * Obtenir ou calculer une valeur cachée
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Obtenir une valeur du cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Stocker une valeur dans le cache
     */
    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        if ($ttl === null) {
            return Cache::forever($key, $value);
        }
        
        return Cache::put($key, $value, $ttl);
    }

    /**
     * Supprimer une clé du cache
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Supprimer plusieurs clés du cache (pattern)
     */
    public function forgetPattern(string $pattern): int
    {
        $keys = Redis::keys($pattern);
        
        if (empty($keys)) {
            return 0;
        }

        // Retirer le préfixe Redis si présent
        $keys = array_map(function ($key) {
            return str_replace(config('database.redis.options.prefix'), '', $key);
        }, $keys);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        return count($keys);
    }

    /**
     * Vider tout le cache
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Vérifier si une clé existe
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Incrémenter une valeur
     */
    public function increment(string $key, int $value = 1): int
    {
        return Cache::increment($key, $value);
    }

    /**
     * Décrémenter une valeur
     */
    public function decrement(string $key, int $value = 1): int
    {
        return Cache::decrement($key, $value);
    }

    // ==================== VOTE CACHE ====================

    /**
     * Cache des résultats de vote
     */
    public function cacheVoteResults(int $topicId, array $results): void
    {
        $key = self::PREFIX_VOTE_RESULTS . $topicId;
        $this->put($key, $results, self::CACHE_1_HOUR);
    }

    /**
     * Obtenir les résultats de vote cachés
     */
    public function getVoteResults(int $topicId): ?array
    {
        $key = self::PREFIX_VOTE_RESULTS . $topicId;
        return $this->get($key);
    }

    /**
     * Invalider le cache des résultats de vote
     */
    public function invalidateVoteResults(int $topicId): bool
    {
        $key = self::PREFIX_VOTE_RESULTS . $topicId;
        return $this->forget($key);
    }

    // ==================== BUDGET CACHE ====================

    /**
     * Cache des statistiques budget
     */
    public function cacheBudgetStats(array $stats): void
    {
        $this->put(self::PREFIX_BUDGET_STATS, $stats, self::CACHE_4_HOURS);
    }

    /**
     * Obtenir les stats budget cachées
     */
    public function getBudgetStats(): ?array
    {
        return $this->get(self::PREFIX_BUDGET_STATS);
    }

    /**
     * Cache des allocations moyennes
     */
    public function cacheBudgetAverages(array $averages): void
    {
        $this->put(self::PREFIX_BUDGET_AVERAGES, $averages, self::CACHE_4_HOURS);
    }

    /**
     * Obtenir les allocations moyennes cachées
     */
    public function getBudgetAverages(): ?array
    {
        return $this->get(self::PREFIX_BUDGET_AVERAGES);
    }

    /**
     * Cache du classement des secteurs
     */
    public function cacheBudgetRanking(array $ranking): void
    {
        $this->put(self::PREFIX_BUDGET_RANKING, $ranking, self::CACHE_4_HOURS);
    }

    /**
     * Obtenir le classement caché
     */
    public function getBudgetRanking(): ?array
    {
        return $this->get(self::PREFIX_BUDGET_RANKING);
    }

    /**
     * Invalider tout le cache budget
     */
    public function invalidateBudgetCache(): int
    {
        $count = 0;
        $count += $this->forget(self::PREFIX_BUDGET_STATS) ? 1 : 0;
        $count += $this->forget(self::PREFIX_BUDGET_AVERAGES) ? 1 : 0;
        $count += $this->forget(self::PREFIX_BUDGET_RANKING) ? 1 : 0;
        $count += $this->forgetPattern(self::PREFIX_USER_ALLOCATIONS . '*');
        return $count;
    }

    /**
     * Cache des allocations utilisateur
     */
    public function cacheUserAllocations(int $userId, array $allocations): void
    {
        $key = self::PREFIX_USER_ALLOCATIONS . $userId;
        $this->put($key, $allocations, self::CACHE_1_DAY);
    }

    /**
     * Obtenir les allocations utilisateur cachées
     */
    public function getUserAllocations(int $userId): ?array
    {
        $key = self::PREFIX_USER_ALLOCATIONS . $userId;
        return $this->get($key);
    }

    /**
     * Invalider les allocations d'un utilisateur
     */
    public function invalidateUserAllocations(int $userId): bool
    {
        $key = self::PREFIX_USER_ALLOCATIONS . $userId;
        return $this->forget($key);
    }

    // ==================== MODERATION CACHE ====================

    /**
     * Cache des statistiques de modération
     */
    public function cacheModerationStats(array $stats): void
    {
        $this->put(self::PREFIX_MODERATION_STATS, $stats, self::CACHE_1_HOUR);
    }

    /**
     * Obtenir les stats modération cachées
     */
    public function getModerationStats(): ?array
    {
        return $this->get(self::PREFIX_MODERATION_STATS);
    }

    /**
     * Invalider le cache modération
     */
    public function invalidateModerationCache(): bool
    {
        return $this->forget(self::PREFIX_MODERATION_STATS);
    }

    // ==================== DOCUMENT CACHE ====================

    /**
     * Cache des statistiques documents
     */
    public function cacheDocumentStats(array $stats): void
    {
        $this->put(self::PREFIX_DOCUMENT_STATS, $stats, self::CACHE_4_HOURS);
    }

    /**
     * Obtenir les stats documents cachées
     */
    public function getDocumentStats(): ?array
    {
        return $this->get(self::PREFIX_DOCUMENT_STATS);
    }

    /**
     * Invalider le cache documents
     */
    public function invalidateDocumentCache(): bool
    {
        return $this->forget(self::PREFIX_DOCUMENT_STATS);
    }

    // ==================== TOPIC CACHE ====================

    /**
     * Cache des statistiques d'un topic
     */
    public function cacheTopicStats(int $topicId, array $stats): void
    {
        $key = self::PREFIX_TOPIC_STATS . $topicId;
        $this->put($key, $stats, self::CACHE_1_HOUR);
    }

    /**
     * Obtenir les stats d'un topic cachées
     */
    public function getTopicStats(int $topicId): ?array
    {
        $key = self::PREFIX_TOPIC_STATS . $topicId;
        return $this->get($key);
    }

    /**
     * Invalider le cache d'un topic
     */
    public function invalidateTopicCache(int $topicId): bool
    {
        $key = self::PREFIX_TOPIC_STATS . $topicId;
        return $this->forget($key);
    }

    // ==================== TAGS SYSTEM ====================

    /**
     * Ajouter des tags à une clé de cache
     * (pour invalidation groupée)
     */
    public function tags(array $tags): self
    {
        // Laravel Cache tags avec Redis
        return $this;
    }

    /**
     * Invalider un groupe de clés par tag
     */
    public function invalidateTag(string $tag): bool
    {
        return Cache::tags([$tag])->flush();
    }
}

