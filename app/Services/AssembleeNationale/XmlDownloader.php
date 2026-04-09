<?php

namespace App\Services\AssembleeNationale;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class XmlDownloader
{
    protected string $baseUrl;

    protected int $legislature;

    protected array $storage;

    protected array $cacheConfig;

    // Configuration des téléchargements
    protected int $timeout = 600; // 10 minutes

    protected int $connectTimeout = 30;

    protected int $maxRetries = 3;

    protected int $retryDelay = 5; // secondes

    public function __construct(?int $legislature = null)
    {
        $this->baseUrl = config('assemblee-nationale.base_url');
        $this->legislature = $legislature ?? config('assemblee-nationale.legislature');
        $this->storage = config('assemblee-nationale.storage');
        $this->cacheConfig = config('assemblee-nationale.cache');

        $this->ensureDirectoriesExist();
    }

    /**
     * Télécharge une source de données
     */
    public function download(string $sourceKey, bool $force = false): array
    {
        $source = config("assemblee-nationale.sources.{$sourceKey}");

        if (! $source) {
            throw new \InvalidArgumentException("Source inconnue : {$sourceKey}");
        }

        $url = $this->buildUrl($source['path']);
        $zipPath = $this->getZipPath($sourceKey);
        $xmlPath = $this->getXmlPath($sourceKey);

        // Vérifier le cache
        if (! $force && $this->isCacheValid($sourceKey, $url)) {
            Log::channel('an-sync')->info("Cache valide pour {$sourceKey}, téléchargement ignoré");

            return [
                'status' => 'cached',
                'source' => $sourceKey,
                'xml_path' => $xmlPath,
            ];
        }

        Log::channel('an-sync')->info("Téléchargement de {$sourceKey} depuis {$url}");

        // Télécharger avec streaming et retries
        $etag = $this->downloadWithRetry($url, $zipPath);

        // Vérifier que le fichier a été téléchargé
        if (! File::exists($zipPath) || File::size($zipPath) === 0) {
            throw new \RuntimeException("Le fichier téléchargé est vide ou n'existe pas");
        }

        $fileSize = File::size($zipPath);
        Log::channel('an-sync')->info('Fichier téléchargé : '.$this->formatBytes($fileSize));

        // Extraire le ZIP
        $extractedFiles = $this->extractZip($zipPath, $sourceKey);

        // Mettre à jour le cache
        $this->updateCache($sourceKey, $url, $etag);

        Log::channel('an-sync')->info('Téléchargement terminé : '.count($extractedFiles).' fichiers extraits');

        return [
            'status' => 'downloaded',
            'source' => $sourceKey,
            'url' => $url,
            'zip_path' => $zipPath,
            'xml_path' => $xmlPath,
            'files' => $extractedFiles,
            'size' => $fileSize,
        ];
    }

    /**
     * Télécharge un fichier avec streaming et retries automatiques
     */
    protected function downloadWithRetry(string $url, string $destinationPath): ?string
    {
        $lastException = null;
        $etag = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                Log::channel('an-sync')->info("Tentative {$attempt}/{$this->maxRetries}...");

                $etag = $this->streamDownload($url, $destinationPath);

                // Succès
                return $etag;

            } catch (\Exception $e) {
                $lastException = $e;
                Log::channel('an-sync')->warning("Tentative {$attempt} échouée : {$e->getMessage()}");

                if ($attempt < $this->maxRetries) {
                    $delay = $this->retryDelay * $attempt; // Backoff exponentiel
                    Log::channel('an-sync')->info("Nouvelle tentative dans {$delay}s...");
                    sleep($delay);
                }
            }
        }

        throw new \RuntimeException(
            "Échec du téléchargement après {$this->maxRetries} tentatives : ".
            ($lastException ? $lastException->getMessage() : 'Erreur inconnue')
        );
    }

    /**
     * Télécharge un fichier en streaming (économise la mémoire pour les gros fichiers)
     */
    protected function streamDownload(string $url, string $destinationPath): ?string
    {
        // Utiliser cURL directement pour un meilleur contrôle
        $ch = curl_init($url);

        // Ouvrir le fichier de destination
        $fp = fopen($destinationPath, 'w');
        if ($fp === false) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier de destination : {$destinationPath}");
        }

        $etag = null;
        $headers = [];

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_FAILONERROR => true,
            // Forcer HTTP/1.1 pour éviter les erreurs HTTP/2 PROTOCOL_ERROR
            // Les serveurs gouvernementaux ont parfois des problèmes avec HTTP/2
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => 'CivicDash/1.0 (Demoscratos)',
            // Capturer les headers
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$headers, &$etag) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) >= 2) {
                    $name = strtolower(trim($header[0]));
                    $value = trim($header[1]);
                    $headers[$name] = $value;
                    if ($name === 'etag') {
                        $etag = $value;
                    }
                }

                return $len;
            },
            // Options pour les gros fichiers
            CURLOPT_LOW_SPEED_LIMIT => 1000, // 1 KB/s minimum
            CURLOPT_LOW_SPEED_TIME => 60,    // pendant 60 secondes max (augmenté pour les serveurs lents)
            // Options supplémentaires pour la robustesse
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',          // Accepter tous les encodages (gzip, deflate)
            CURLOPT_TCP_KEEPALIVE => 1,      // Garder la connexion TCP active
            CURLOPT_TCP_KEEPIDLE => 30,      // Délai avant d'envoyer des keepalive
            CURLOPT_TCP_KEEPINTVL => 15,     // Intervalle entre les keepalive
        ]);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        curl_close($ch);
        fclose($fp);

        if (! $success || $errno !== 0) {
            // Supprimer le fichier partiel
            if (File::exists($destinationPath)) {
                File::delete($destinationPath);
            }
            throw new \RuntimeException("cURL error {$errno}: {$error}");
        }

        if ($httpCode >= 400) {
            if (File::exists($destinationPath)) {
                File::delete($destinationPath);
            }
            throw new \RuntimeException("HTTP error {$httpCode}");
        }

        return $etag;
    }

    /**
     * Formate une taille en bytes de manière lisible
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Télécharge toutes les sources
     */
    public function downloadAll(bool $force = false): array
    {
        $sources = config('assemblee-nationale.sources');
        $results = [];

        // Trier par priorité
        uasort($sources, fn ($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));

        foreach (array_keys($sources) as $sourceKey) {
            try {
                $results[$sourceKey] = $this->download($sourceKey, $force);
            } catch (\Exception $e) {
                Log::channel('an-sync')->error("Erreur téléchargement {$sourceKey}: {$e->getMessage()}");
                $results[$sourceKey] = [
                    'status' => 'error',
                    'source' => $sourceKey,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Construit l'URL complète
     */
    protected function buildUrl(string $path): string
    {
        $path = str_replace('{legislature}', $this->legislature, $path);

        return "{$this->baseUrl}/{$path}";
    }

    /**
     * Extrait un fichier ZIP
     */
    protected function extractZip(string $zipPath, string $sourceKey): array
    {
        $extractPath = $this->storage['xml_path'].'/'.$sourceKey;

        // Nettoyer le répertoire d'extraction
        if (File::isDirectory($extractPath)) {
            File::deleteDirectory($extractPath);
        }
        File::makeDirectory($extractPath, 0755, true);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier ZIP : {$zipPath}");
        }

        $zip->extractTo($extractPath);
        $extractedFiles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $extractedFiles[] = $zip->getNameIndex($i);
        }

        $zip->close();

        return $extractedFiles;
    }

    /**
     * Vérifie si le cache est valide
     */
    protected function isCacheValid(string $sourceKey, string $url): bool
    {
        if (! $this->cacheConfig['enabled']) {
            return false;
        }

        $cacheKey = "an_download_{$sourceKey}";
        $cached = Cache::get($cacheKey);

        if (! $cached) {
            return false;
        }

        // Vérifier le timestamp
        $age = time() - $cached['timestamp'];
        if ($age > $this->cacheConfig['duration']) {
            return false;
        }

        // Vérifier le ETag si activé
        if ($this->cacheConfig['check_etag'] && isset($cached['etag'])) {
            try {
                $response = Http::head($url);
                $currentEtag = $response->header('ETag');

                if ($currentEtag && $currentEtag !== $cached['etag']) {
                    return false;
                }
            } catch (\Exception $e) {
                // En cas d'erreur, on considère le cache comme invalide
                return false;
            }
        }

        // Vérifier que les fichiers existent toujours
        $xmlPath = $this->getXmlPath($sourceKey);
        if (! File::isDirectory($xmlPath)) {
            return false;
        }

        return true;
    }

    /**
     * Met à jour le cache
     */
    protected function updateCache(string $sourceKey, string $url, ?string $etag): void
    {
        $cacheKey = "an_download_{$sourceKey}";

        Cache::put($cacheKey, [
            'url' => $url,
            'timestamp' => time(),
            'etag' => $etag,
            'legislature' => $this->legislature,
        ], $this->cacheConfig['duration']);
    }

    /**
     * Retourne le chemin du fichier ZIP
     */
    public function getZipPath(string $sourceKey): string
    {
        return $this->storage['zip_path']."/{$sourceKey}_L{$this->legislature}.zip";
    }

    /**
     * Retourne le chemin des fichiers XML extraits
     */
    public function getXmlPath(string $sourceKey): string
    {
        return $this->storage['xml_path'].'/'.$sourceKey;
    }

    /**
     * S'assure que les répertoires de stockage existent
     */
    protected function ensureDirectoriesExist(): void
    {
        foreach ($this->storage as $path) {
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }

    /**
     * Nettoie les fichiers téléchargés
     */
    public function cleanup(?string $sourceKey = null): void
    {
        if ($sourceKey) {
            $zipPath = $this->getZipPath($sourceKey);
            $xmlPath = $this->getXmlPath($sourceKey);

            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }
            if (File::isDirectory($xmlPath)) {
                File::deleteDirectory($xmlPath);
            }

            Cache::forget("an_download_{$sourceKey}");
        } else {
            // Nettoyer tout
            foreach (array_keys(config('assemblee-nationale.sources')) as $key) {
                $this->cleanup($key);
            }
        }
    }

    /**
     * Retourne les informations de cache
     */
    public function getCacheInfo(): array
    {
        $info = [];

        foreach (array_keys(config('assemblee-nationale.sources')) as $sourceKey) {
            $cacheKey = "an_download_{$sourceKey}";
            $cached = Cache::get($cacheKey);

            $info[$sourceKey] = [
                'cached' => $cached !== null,
                'timestamp' => $cached['timestamp'] ?? null,
                'age' => $cached ? (time() - $cached['timestamp']) : null,
                'etag' => $cached['etag'] ?? null,
                'xml_exists' => File::isDirectory($this->getXmlPath($sourceKey)),
            ];
        }

        return $info;
    }

    /**
     * Change la législature
     */
    public function setLegislature(int $legislature): self
    {
        $this->legislature = $legislature;

        return $this;
    }

    /**
     * Retourne la législature courante
     */
    public function getLegislature(): int
    {
        return $this->legislature;
    }
}
