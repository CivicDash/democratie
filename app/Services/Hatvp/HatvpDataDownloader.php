<?php

namespace App\Services\Hatvp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de téléchargement des données HATVP
 *
 * Haute Autorité pour la Transparence de la Vie Publique
 */
class HatvpDataDownloader
{
    private string $storagePath;

    private string $xmlPath;

    private string $cachePath;

    private int $timeout;

    private bool $cacheEnabled;

    private int $cacheDuration;

    public function __construct()
    {
        $config = config('hatvp.storage', []);
        $this->storagePath = $config['path'] ?? storage_path('app/hatvp-data');
        $this->xmlPath = $config['xml_path'] ?? storage_path('app/hatvp-data/xml');
        $this->cachePath = $config['cache_path'] ?? storage_path('app/hatvp-data/cache');

        $importConfig = config('hatvp.import', []);
        $this->timeout = $importConfig['timeout'] ?? 300;

        $cacheConfig = config('hatvp.cache', []);
        $this->cacheEnabled = $cacheConfig['enabled'] ?? true;
        $this->cacheDuration = $cacheConfig['duration'] ?? 86400;

        $this->ensureDirectoriesExist();
    }

    /**
     * Crée les répertoires nécessaires
     */
    private function ensureDirectoriesExist(): void
    {
        foreach ([$this->storagePath, $this->xmlPath, $this->cachePath] as $path) {
            if (! file_exists($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Télécharge le fichier complet des déclarations
     */
    public function downloadAllDeclarations(bool $forceRefresh = false): ?string
    {
        $url = config('hatvp.sources.declarations.url');
        $localFile = "{$this->xmlPath}/declarations.xml";

        // Vérifier le cache
        if (! $forceRefresh && $this->isCacheValid($localFile)) {
            Log::info('[HatvpDataDownloader] Cache valide pour declarations.xml');

            return $localFile;
        }

        Log::info("[HatvpDataDownloader] Téléchargement de declarations.xml depuis {$url}");

        try {
            $response = Http::timeout($this->timeout)
                ->withOptions(['sink' => $localFile])
                ->get($url);

            if (! $response->successful()) {
                Log::error("[HatvpDataDownloader] Erreur HTTP {$response->status()}");

                return null;
            }

            $size = filesize($localFile);
            $sizeMB = round($size / 1024 / 1024, 2);
            Log::info("[HatvpDataDownloader] Téléchargé declarations.xml ({$sizeMB} Mo)");

            return $localFile;

        } catch (\Exception $e) {
            Log::error('[HatvpDataDownloader] Erreur téléchargement : '.$e->getMessage());

            return null;
        }
    }

    /**
     * Télécharge une déclaration individuelle
     */
    public function downloadDeclaration(string $slug, bool $forceRefresh = false): ?string
    {
        $baseUrl = config('hatvp.sources.dossiers.url');
        $url = $baseUrl.$slug.'.xml';
        $localFile = "{$this->xmlPath}/dossiers/{$slug}.xml";

        // Créer le sous-répertoire si nécessaire
        if (! file_exists(dirname($localFile))) {
            mkdir(dirname($localFile), 0755, true);
        }

        // Vérifier le cache
        if (! $forceRefresh && $this->isCacheValid($localFile)) {
            return $localFile;
        }

        Log::info("[HatvpDataDownloader] Téléchargement déclaration : {$slug}");

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning("[HatvpDataDownloader] Déclaration non disponible : {$slug}");

                return null;
            }

            file_put_contents($localFile, $response->body());

            return $localFile;

        } catch (\Exception $e) {
            Log::error("[HatvpDataDownloader] Erreur téléchargement {$slug} : ".$e->getMessage());

            return null;
        }
    }

    /**
     * Construit le slug d'une déclaration
     */
    public function buildDeclarationSlug(
        string $nom,
        string $prenom,
        string $typeDeclaration,
        string $id,
        string $typeMandat,
        string $codeDepartement
    ): string {
        $slug = strtolower($nom).'-'.strtolower($prenom);
        $slug = $this->slugify($slug);

        $type = strtolower(substr($typeDeclaration, 0, 3)); // DIA -> dia, DSP -> dsp

        return "{$slug}-{$type}{$id}-{$typeMandat}-{$codeDepartement}";
    }

    /**
     * Parse le fichier global pour extraire les déclarations
     */
    public function parseDeclarationsIndex(string $xmlFile): array
    {
        if (! file_exists($xmlFile)) {
            return [];
        }

        Log::info("[HatvpDataDownloader] Parsing de l'index des déclarations...");

        // Utiliser XMLReader pour les gros fichiers
        $reader = new \XMLReader;
        $reader->open($xmlFile);

        $declarations = [];
        $count = 0;

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'declaration') {
                $node = $reader->readOuterXml();

                // Extraire les infos de base sans parser tout
                $info = $this->extractBasicInfo($node);

                if ($info) {
                    $declarations[] = $info;
                    $count++;

                    if ($count % 100 === 0) {
                        Log::info("[HatvpDataDownloader] {$count} déclarations parsées...");
                    }
                }
            }
        }

        $reader->close();

        Log::info("[HatvpDataDownloader] Total : {$count} déclarations");

        return $declarations;
    }

    /**
     * Extrait les informations de base d'une déclaration (parsing léger)
     */
    private function extractBasicInfo(string $xmlContent): ?array
    {
        $xml = @simplexml_load_string($xmlContent);

        if ($xml === false) {
            return null;
        }

        $general = $xml->general;
        $declarant = $general->declarant;

        return [
            'uuid' => (string) $xml->uuid,
            'date_depot' => (string) $xml->dateDepot,
            'type_declaration' => (string) $general->typeDeclaration->id,
            'type_mandat' => (string) $general->qualiteMandat->typeMandat,
            'code_type_mandat' => (string) $general->qualiteMandat->codTypeMandatFichier,
            'code_departement' => (string) $general->organe->codeOrgane,
            'nom' => (string) $declarant->nom,
            'prenom' => (string) $declarant->prenom,
            'date_naissance' => (string) $declarant->dateNaissance,
            'date_debut_mandat' => (string) $general->dateDebutMandat,
        ];
    }

    /**
     * Filtre les déclarations pour ne garder que les parlementaires
     */
    public function filterParlementaires(array $declarations): array
    {
        $filtres = config('hatvp.filtres_parlementaires', ['senateur', 'depute']);

        return array_filter($declarations, function ($decl) use ($filtres) {
            $type = strtolower($decl['code_type_mandat'] ?? '');

            return in_array($type, $filtres);
        });
    }

    /**
     * Vérifie si le cache est valide
     */
    private function isCacheValid(string $file): bool
    {
        if (! $this->cacheEnabled) {
            return false;
        }

        if (! file_exists($file)) {
            return false;
        }

        $mtime = filemtime($file);

        return (time() - $mtime) < $this->cacheDuration;
    }

    /**
     * Convertit une chaîne en slug
     */
    private function slugify(string $text): string
    {
        // Remplacer les caractères accentués
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        // Convertir en minuscules
        $text = strtolower($text);

        // Remplacer les espaces et caractères spéciaux par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Supprimer les tirets en début et fin
        $text = trim($text, '-');

        return $text;
    }

    /**
     * Retourne les statistiques du cache
     */
    public function getCacheStats(): array
    {
        $declarationsFile = "{$this->xmlPath}/declarations.xml";

        $stats = [
            'declarations' => [
                'exists' => file_exists($declarationsFile),
                'size' => 0,
                'size_mb' => 0,
                'modified' => null,
                'age_hours' => null,
                'cache_valid' => false,
            ],
            'dossiers_count' => 0,
        ];

        if (file_exists($declarationsFile)) {
            $stats['declarations']['size'] = filesize($declarationsFile);
            $stats['declarations']['size_mb'] = round(filesize($declarationsFile) / 1024 / 1024, 2);
            $stats['declarations']['modified'] = date('Y-m-d H:i:s', filemtime($declarationsFile));
            $stats['declarations']['age_hours'] = round((time() - filemtime($declarationsFile)) / 3600, 1);
            $stats['declarations']['cache_valid'] = $this->isCacheValid($declarationsFile);
        }

        // Compter les dossiers individuels
        $dossiersPath = "{$this->xmlPath}/dossiers";
        if (file_exists($dossiersPath)) {
            $files = glob("{$dossiersPath}/*.xml");
            $stats['dossiers_count'] = count($files);
        }

        return $stats;
    }

    /**
     * Nettoie le cache
     */
    public function clearCache(): void
    {
        $this->deleteDirectory($this->xmlPath);
        $this->deleteDirectory($this->cachePath);
        $this->ensureDirectoriesExist();

        Log::info('[HatvpDataDownloader] Cache nettoyé');
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
}
