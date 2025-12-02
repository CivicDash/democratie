<?php

namespace App\Console\Commands;

use App\Services\AssembleeNationale\XmlDownloader;
use Illuminate\Console\Command;

class DownloadAnDataCommand extends Command
{
    protected $signature = 'an:download 
                            {source? : Source à télécharger (scrutins, deputes_actifs, amendements, etc.)}
                            {--legislature=17 : Numéro de la législature}
                            {--force : Force le téléchargement même si le cache est valide}
                            {--list : Liste les sources disponibles}
                            {--status : Affiche le statut du cache}
                            {--cleanup : Supprime les fichiers téléchargés}';

    protected $description = 'Télécharge les données XML de l\'Assemblée Nationale';

    public function handle(): int
    {
        $legislature = (int) $this->option('legislature');
        $downloader = new XmlDownloader($legislature);

        // Mode liste
        if ($this->option('list')) {
            return $this->listSources();
        }

        // Mode statut
        if ($this->option('status')) {
            return $this->showStatus($downloader);
        }

        // Mode cleanup
        if ($this->option('cleanup')) {
            return $this->cleanup($downloader, $this->argument('source'));
        }

        $this->info("🏛️  Téléchargement des données AN - Législature {$legislature}");
        $this->newLine();

        $source = $this->argument('source');
        $force = $this->option('force');

        try {
            if ($source) {
                // Télécharger une source spécifique
                $result = $this->downloadSource($downloader, $source, $force);
                return $result ? self::SUCCESS : self::FAILURE;
            } else {
                // Télécharger toutes les sources
                return $this->downloadAll($downloader, $force);
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur : {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Télécharge une source spécifique
     */
    protected function downloadSource(XmlDownloader $downloader, string $source, bool $force): bool
    {
        $sources = config('assemblee-nationale.sources');
        
        if (!isset($sources[$source])) {
            $this->error("❌ Source inconnue : {$source}");
            $this->info("Utilisez --list pour voir les sources disponibles");
            return false;
        }

        $this->info("📥 Téléchargement de : {$source}");
        $this->info("   {$sources[$source]['description']}");
        $this->newLine();

        $result = $downloader->download($source, $force);

        if ($result['status'] === 'cached') {
            $this->info("✅ Cache valide, téléchargement ignoré");
            $this->info("   Utilisez --force pour forcer le téléchargement");
        } elseif ($result['status'] === 'downloaded') {
            $this->info("✅ Téléchargement terminé");
            $this->info("   📁 Fichiers : " . count($result['files']));
            $this->info("   💾 Taille : " . $this->formatBytes($result['size']));
            $this->info("   📂 Chemin : {$result['xml_path']}");
        } else {
            $this->error("❌ Erreur : " . ($result['error'] ?? 'Erreur inconnue'));
            return false;
        }

        return true;
    }

    /**
     * Télécharge toutes les sources
     */
    protected function downloadAll(XmlDownloader $downloader, bool $force): int
    {
        $sources = config('assemblee-nationale.sources');
        
        $this->info("📥 Téléchargement de " . count($sources) . " sources...");
        $this->newLine();

        $results = $downloader->downloadAll($force);

        $success = 0;
        $cached = 0;
        $errors = 0;

        $this->table(
            ['Source', 'Statut', 'Fichiers', 'Taille'],
            collect($results)->map(function ($result, $key) use (&$success, &$cached, &$errors) {
                $status = match ($result['status']) {
                    'downloaded' => '✅ Téléchargé',
                    'cached' => '📦 Cache',
                    'error' => '❌ Erreur',
                    default => '❓ Inconnu',
                };

                if ($result['status'] === 'downloaded') $success++;
                elseif ($result['status'] === 'cached') $cached++;
                else $errors++;

                return [
                    $key,
                    $status,
                    isset($result['files']) ? count($result['files']) : '-',
                    isset($result['size']) ? $this->formatBytes($result['size']) : '-',
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info("📊 Résumé : {$success} téléchargés, {$cached} en cache, {$errors} erreurs");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Liste les sources disponibles
     */
    protected function listSources(): int
    {
        $sources = config('assemblee-nationale.sources');

        $this->info("📋 Sources de données disponibles :");
        $this->newLine();

        $this->table(
            ['Clé', 'Description', 'Priorité', 'Modèle'],
            collect($sources)->map(function ($source, $key) {
                return [
                    $key,
                    $source['description'],
                    $source['priority'] ?? '-',
                    class_basename($source['model']),
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info("Usage : php artisan an:download {source} --legislature=17");

        return self::SUCCESS;
    }

    /**
     * Affiche le statut du cache
     */
    protected function showStatus(XmlDownloader $downloader): int
    {
        $this->info("📊 Statut du cache - Législature {$downloader->getLegislature()}");
        $this->newLine();

        $cacheInfo = $downloader->getCacheInfo();

        $this->table(
            ['Source', 'En cache', 'Âge', 'Fichiers XML'],
            collect($cacheInfo)->map(function ($info, $key) {
                $age = $info['age'] ? $this->formatDuration($info['age']) : '-';
                
                return [
                    $key,
                    $info['cached'] ? '✅ Oui' : '❌ Non',
                    $age,
                    $info['xml_exists'] ? '✅ Présents' : '❌ Absents',
                ];
            })->toArray()
        );

        return self::SUCCESS;
    }

    /**
     * Nettoie les fichiers téléchargés
     */
    protected function cleanup(XmlDownloader $downloader, ?string $source): int
    {
        if ($source) {
            $this->warn("🗑️  Suppression des fichiers pour : {$source}");
            $downloader->cleanup($source);
            $this->info("✅ Nettoyage terminé");
        } else {
            if (!$this->confirm('Supprimer TOUS les fichiers téléchargés ?')) {
                $this->info('Opération annulée');
                return self::SUCCESS;
            }
            
            $this->warn("🗑️  Suppression de tous les fichiers...");
            $downloader->cleanup();
            $this->info("✅ Nettoyage terminé");
        }

        return self::SUCCESS;
    }

    /**
     * Formate une taille en bytes
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Formate une durée en secondes
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes}m";
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "{$hours}h {$minutes}m";
    }
}

