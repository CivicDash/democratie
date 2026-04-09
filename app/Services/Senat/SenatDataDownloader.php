<?php

namespace App\Services\Senat;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * Service de téléchargement des données du Sénat
 *
 * Gère le téléchargement et l'extraction des bases SQL PostgreSQL
 * ainsi que des flux XML Akoma Ntoso.
 */
class SenatDataDownloader
{
    private string $storagePath;

    private string $zipPath;

    private string $sqlPath;

    private string $xmlPath;

    private int $timeout;

    private bool $cacheEnabled;

    private int $cacheDuration;

    public function __construct()
    {
        $config = config('senat.storage', []);
        $this->storagePath = $config['path'] ?? storage_path('app/senat-data');
        $this->zipPath = $config['zip_path'] ?? storage_path('app/senat-data/zip');
        $this->sqlPath = $config['sql_path'] ?? storage_path('app/senat-data/sql');
        $this->xmlPath = $config['xml_path'] ?? storage_path('app/senat-data/xml');

        $importConfig = config('senat.import', []);
        $this->timeout = $importConfig['timeout'] ?? 600;

        $cacheConfig = config('senat.cache', []);
        $this->cacheEnabled = $cacheConfig['enabled'] ?? true;
        $this->cacheDuration = $cacheConfig['duration'] ?? 86400;

        $this->ensureDirectoriesExist();
    }

    /**
     * Crée les répertoires nécessaires
     */
    private function ensureDirectoriesExist(): void
    {
        foreach ([$this->storagePath, $this->zipPath, $this->sqlPath, $this->xmlPath] as $path) {
            if (! file_exists($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Télécharge une base SQL du Sénat
     */
    public function downloadDatabase(string $type, bool $forceRefresh = false): ?string
    {
        $databases = config('senat.databases', []);

        if (! isset($databases[$type])) {
            Log::error("[SenatDataDownloader] Type de base inconnu : {$type}");

            return null;
        }

        $config = $databases[$type];
        $url = $config['url'];
        $zipFile = "{$this->zipPath}/{$type}.zip";

        // Vérifier le cache
        if (! $forceRefresh && $this->isCacheValid($zipFile)) {
            Log::info("[SenatDataDownloader] Cache valide pour {$type}");

            return $zipFile;
        }

        Log::info("[SenatDataDownloader] Téléchargement de {$type} depuis {$url}");

        try {
            $response = Http::timeout($this->timeout)
                ->withOptions(['sink' => $zipFile])
                ->get($url);

            if (! $response->successful()) {
                Log::error("[SenatDataDownloader] Erreur HTTP {$response->status()} pour {$type}");

                return null;
            }

            $size = filesize($zipFile);
            $sizeMB = round($size / 1024 / 1024, 2);
            Log::info("[SenatDataDownloader] Téléchargé {$type} ({$sizeMB} Mo)");

            return $zipFile;

        } catch (\Exception $e) {
            Log::error("[SenatDataDownloader] Erreur téléchargement {$type} : ".$e->getMessage());

            return null;
        }
    }

    /**
     * Extrait un fichier ZIP et retourne les fichiers SQL
     */
    public function extractZip(string $zipFile, string $type): array
    {
        $extractPath = "{$this->sqlPath}/{$type}";

        // Nettoyer le répertoire existant
        if (file_exists($extractPath)) {
            $this->deleteDirectory($extractPath);
        }
        mkdir($extractPath, 0755, true);

        $zip = new ZipArchive;

        if ($zip->open($zipFile) !== true) {
            Log::error("[SenatDataDownloader] Impossible d'ouvrir le ZIP : {$zipFile}");

            return [];
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Lister les fichiers SQL
        $sqlFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'sql') {
                $sqlFiles[] = $file->getPathname();
            }
        }

        Log::info('[SenatDataDownloader] Extrait '.count($sqlFiles)." fichier(s) SQL pour {$type}");

        return $sqlFiles;
    }

    /**
     * Télécharge un flux XML Akoma Ntoso
     */
    public function downloadAkomaNtosoFeed(string $type, bool $forceRefresh = false): ?string
    {
        $feeds = config('senat.akoma_ntoso', []);

        if (! isset($feeds[$type])) {
            Log::error("[SenatDataDownloader] Type de flux Akoma Ntoso inconnu : {$type}");

            return null;
        }

        $url = $feeds[$type]['url'];
        $xmlFile = "{$this->xmlPath}/{$type}.xml";

        // Cache plus court pour les flux XML (1 heure)
        if (! $forceRefresh && $this->isCacheValid($xmlFile, 3600)) {
            Log::info("[SenatDataDownloader] Cache valide pour flux {$type}");

            return $xmlFile;
        }

        Log::info("[SenatDataDownloader] Téléchargement flux Akoma Ntoso {$type}");

        try {
            $response = Http::timeout(60)->get($url);

            if (! $response->successful()) {
                Log::error("[SenatDataDownloader] Erreur HTTP {$response->status()} pour flux {$type}");

                return null;
            }

            file_put_contents($xmlFile, $response->body());
            Log::info("[SenatDataDownloader] Téléchargé flux {$type}");

            return $xmlFile;

        } catch (\Exception $e) {
            Log::error("[SenatDataDownloader] Erreur téléchargement flux {$type} : ".$e->getMessage());

            return null;
        }
    }

    /**
     * Télécharge un texte Akoma Ntoso spécifique
     */
    public function downloadAkomaNtosoText(string $url): ?string
    {
        // Extraire le nom du fichier depuis l'URL
        $filename = basename($url);
        $localPath = "{$this->xmlPath}/textes/{$filename}";

        // Créer le sous-répertoire si nécessaire
        if (! file_exists(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }

        // Vérifier le cache (7 jours pour les textes)
        if ($this->isCacheValid($localPath, 604800)) {
            return $localPath;
        }

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning("[SenatDataDownloader] Texte non disponible : {$url}");

                return null;
            }

            file_put_contents($localPath, $response->body());

            return $localPath;

        } catch (\Exception $e) {
            Log::error('[SenatDataDownloader] Erreur téléchargement texte : '.$e->getMessage());

            return null;
        }
    }

    /**
     * Parse le flux des textes déposés/adoptés
     */
    public function parseAkomaNtosoFeed(string $xmlFile): array
    {
        if (! file_exists($xmlFile)) {
            return [];
        }

        $content = file_get_contents($xmlFile);
        $xml = @simplexml_load_string($content);

        if ($xml === false) {
            Log::error("[SenatDataDownloader] Erreur parsing XML : {$xmlFile}");

            return [];
        }

        $texts = [];

        foreach ($xml->text as $text) {
            $url = (string) $text->url;
            $lastModified = (string) $text->lastModifiedDateTime;

            // Extraire le type et le numéro depuis l'URL
            $filename = basename($url, '.akn.xml');
            preg_match('/^(ppl|pjl|ppr|pjr)(\d{2})-(\d+)$/', $filename, $matches);

            $texts[] = [
                'url' => $url,
                'filename' => $filename,
                'type' => $matches[1] ?? null,
                'year' => $matches[2] ?? null,
                'number' => $matches[3] ?? null,
                'last_modified' => $lastModified,
            ];
        }

        Log::info('[SenatDataDownloader] Parsé '.count($texts).' textes depuis le flux');

        return $texts;
    }

    /**
     * Vérifie si le cache est valide
     */
    private function isCacheValid(string $file, ?int $duration = null): bool
    {
        if (! $this->cacheEnabled) {
            return false;
        }

        if (! file_exists($file)) {
            return false;
        }

        $duration = $duration ?? $this->cacheDuration;
        $mtime = filemtime($file);

        return (time() - $mtime) < $duration;
    }

    /**
     * Supprime un répertoire récursivement
     */
    private function deleteDirectory(string $dir): void
    {
        if (! file_exists($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    /**
     * Retourne les statistiques de cache
     */
    public function getCacheStats(): array
    {
        $stats = [];

        foreach (config('senat.databases', []) as $type => $config) {
            $zipFile = "{$this->zipPath}/{$type}.zip";

            if (file_exists($zipFile)) {
                $stats[$type] = [
                    'exists' => true,
                    'size' => filesize($zipFile),
                    'size_mb' => round(filesize($zipFile) / 1024 / 1024, 2),
                    'modified' => date('Y-m-d H:i:s', filemtime($zipFile)),
                    'age_hours' => round((time() - filemtime($zipFile)) / 3600, 1),
                    'cache_valid' => $this->isCacheValid($zipFile),
                ];
            } else {
                $stats[$type] = [
                    'exists' => false,
                    'size' => 0,
                    'size_mb' => 0,
                    'modified' => null,
                    'age_hours' => null,
                    'cache_valid' => false,
                ];
            }
        }

        return $stats;
    }

    /**
     * Nettoie le cache
     */
    public function clearCache(?string $type = null): void
    {
        if ($type) {
            $zipFile = "{$this->zipPath}/{$type}.zip";
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }

            $sqlDir = "{$this->sqlPath}/{$type}";
            if (file_exists($sqlDir)) {
                $this->deleteDirectory($sqlDir);
            }
        } else {
            // Tout nettoyer
            $this->deleteDirectory($this->zipPath);
            $this->deleteDirectory($this->sqlPath);
            $this->deleteDirectory($this->xmlPath);
            $this->ensureDirectoriesExist();
        }

        Log::info('[SenatDataDownloader] Cache nettoyé'.($type ? " pour {$type}" : ''));
    }
}
