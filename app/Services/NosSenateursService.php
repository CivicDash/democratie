<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class NosSenateursService
{
    private const API_BASE_URL = 'https://www.nosenateurs.fr/api';
    private const CACHE_TTL = 3600; // 1 heure

    /**
     * Récupère la liste des scrutins
     * 
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getScrutins(int $page = 1, int $limit = 50): array
    {
        $cacheKey = "nosenateurs_scrutins_{$page}_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($page, $limit) {
            $response = Http::timeout(30)
                ->get(self::API_BASE_URL . '/scrutins', [
                    'page' => $page,
                    'limit' => $limit,
                ]);

            if (!$response->successful()) {
                throw new \Exception("Erreur API NosSénateurs.fr: " . $response->status());
            }

            return $response->json();
        });
    }

    /**
     * Récupère les détails d'un scrutin
     * 
     * @param string $scrutinId
     * @return array
     */
    public function getScrutin(string $scrutinId): array
    {
        $cacheKey = "nosenateurs_scrutin_{$scrutinId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($scrutinId) {
            $response = Http::timeout(30)
                ->get(self::API_BASE_URL . "/scrutins/{$scrutinId}");

            if (!$response->successful()) {
                throw new \Exception("Scrutin introuvable: {$scrutinId}");
            }

            return $response->json();
        });
    }

    /**
     * Récupère les votes d'un sénateur
     * 
     * @param string $senateurSlug
     * @return array
     */
    public function getVotesSenateur(string $senateurSlug): array
    {
        $cacheKey = "nosenateurs_votes_{$senateurSlug}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($senateurSlug) {
            $response = Http::timeout(30)
                ->get(self::API_BASE_URL . "/senateurs/{$senateurSlug}/votes");

            if (!$response->successful()) {
                return [];
            }

            return $response->json()['votes'] ?? [];
        });
    }

    /**
     * Récupère le profil d'un sénateur
     * 
     * @param string $senateurSlug
     * @return array|null
     */
    public function getSenateur(string $senateurSlug): ?array
    {
        $cacheKey = "nosenateurs_senateur_{$senateurSlug}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($senateurSlug) {
            $response = Http::timeout(30)
                ->get(self::API_BASE_URL . "/senateurs/{$senateurSlug}");

            if (!$response->successful()) {
                return null;
            }

            return $response->json();
        });
    }

    /**
     * Vide le cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}

