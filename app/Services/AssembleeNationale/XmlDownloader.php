<?php

namespace App\Services\AssembleeNationale;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use ZipArchive;

class XmlDownloader
{
    protected string $baseUrl;
    protected int $legislature;
    protected array $storage;
    protected array $cacheConfig;

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
        
        if (!$source) {
            throw new \InvalidArgumentException("Source inconnue : {$sourceKey}");
        }

        $url = $this->buildUrl($source['path']);
        $zipPath = $this->getZipPath($sourceKey);
        $xmlPath = $this->getXmlPath($sourceKey);

        // Vérifier le cache
        if (!$force && $this->isCacheValid($sourceKey, $url)) {
            Log::channel('an-sync')->info("Cache valide pour {$sourceKey}, téléchargement ignoré");
            return [
                'status' => 'cached',
                'source' => $sourceKey,
                'xml_path' => $xmlPath,
            ];
        }

        Log::channel('an-sync')->info("Téléchargement de {$sourceKey} depuis {$url}");

        // Télécharger le fichier ZIP
        $response = Http::timeout(300)->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Échec du téléchargement : HTTP {$response->status()}");
        }

        // Sauvegarder le ZIP
        File::put($zipPath, $response->body());
        
        // Extraire le ZIP
        $extractedFiles = $this->extractZip($zipPath, $sourceKey);

        // Mettre à jour le cache
        $this->updateCache($sourceKey, $url, $response->header('ETag'));

        Log::channel('an-sync')->info("Téléchargement terminé : " . count($extractedFiles) . " fichiers extraits");

        return [
            'status' => 'downloaded',
            'source' => $sourceKey,
            'url' => $url,
            'zip_path' => $zipPath,
            'xml_path' => $xmlPath,
            'files' => $extractedFiles,
            'size' => strlen($response->body()),
        ];
    }

    /**
     * Télécharge toutes les sources
     */
    public function downloadAll(bool $force = false): array
    {
        $sources = config('assemblee-nationale.sources');
        $results = [];

        // Trier par priorité
        uasort($sources, fn($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));

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
        $extractPath = $this->storage['xml_path'] . '/' . $sourceKey;
        
        // Nettoyer le répertoire d'extraction
        if (File::isDirectory($extractPath)) {
            File::deleteDirectory($extractPath);
        }
        File::makeDirectory($extractPath, 0755, true);

        $zip = new ZipArchive();
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
        if (!$this->cacheConfig['enabled']) {
            return false;
        }

        $cacheKey = "an_download_{$sourceKey}";
        $cached = Cache::get($cacheKey);

        if (!$cached) {
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
        if (!File::isDirectory($xmlPath)) {
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
        return $this->storage['zip_path'] . "/{$sourceKey}_L{$this->legislature}.zip";
    }

    /**
     * Retourne le chemin des fichiers XML extraits
     */
    public function getXmlPath(string $sourceKey): string
    {
        return $this->storage['xml_path'] . '/' . $sourceKey;
    }

    /**
     * S'assure que les répertoires de stockage existent
     */
    protected function ensureDirectoriesExist(): void
    {
        foreach ($this->storage as $path) {
            if (!File::isDirectory($path)) {
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

