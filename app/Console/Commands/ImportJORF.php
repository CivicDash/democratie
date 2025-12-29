<?php

namespace App\Console\Commands;

use App\Models\TexteJO;
use App\Models\ArticleJO;
use App\Models\Loi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportJORF extends Command
{
    protected $signature = 'import:jorf 
                            {--date= : Date spécifique (YYYYMMDD)}
                            {--days=1 : Nombre de jours à importer}
                            {--with-articles : Importer aussi le contenu des articles}
                            {--lois-only : Importer uniquement les LOI, DECRET, ORDONNANCE}';

    protected $description = 'Importe les textes du Journal Officiel depuis les exports DILA (data.gouv.fr)';

    protected string $baseUrl = 'https://echanges.dila.gouv.fr/OPENDATA/JORF/';
    protected string $tempDir;
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected int $linked = 0;

    public function handle(): int
    {
        $this->tempDir = storage_path('app/opendata/jorf_temp');

        // Nettoyer au démarrage
        $this->cleanup();

        $days = (int) $this->option('days');
        $date = $this->option('date');

        if ($date) {
            $this->importDate($date);
        } else {
            // Importer les N derniers jours
            for ($i = 0; $i < $days; $i++) {
                $targetDate = now()->subDays($i)->format('Ymd');
                $this->importDate($targetDate);
            }
        }

        $this->newLine();
        $this->info("✅ Import terminé !");
        $this->table(
            ['Créés', 'Mis à jour', 'Ignorés', 'Liés aux lois'],
            [[$this->created, $this->updated, $this->skipped, $this->linked]]
        );

        // Nettoyer à la fin
        $this->cleanup();

        return Command::SUCCESS;
    }

    protected function importDate(string $date): void
    {
        $this->info("📅 Import du {$date}...");

        // Trouver le fichier pour cette date
        $archiveUrl = $this->findArchiveForDate($date);

        if (!$archiveUrl) {
            $this->warn("  Aucun export trouvé pour le {$date}");
            return;
        }

        // Télécharger
        $archivePath = $this->downloadArchive($archiveUrl);
        if (!$archivePath) {
            return;
        }

        // Extraire
        $extractDir = $this->extractArchive($archivePath);
        if (!$extractDir) {
            return;
        }

        // Parser les textes
        $this->parseTextes($extractDir);

        // Nettoyer
        $this->cleanup();
    }

    protected function findArchiveForDate(string $date): ?string
    {
        $response = Http::get($this->baseUrl);

        if (!$response->successful()) {
            $this->error("Impossible d'accéder au serveur DILA");
            return null;
        }

        // Chercher le fichier correspondant à la date
        preg_match_all('/JORF_' . $date . '-\d+\.tar\.gz/', $response->body(), $matches);

        if (empty($matches[0])) {
            return null;
        }

        // Prendre le dernier (le plus récent de la journée)
        $filename = end($matches[0]);
        return $this->baseUrl . $filename;
    }

    protected function downloadArchive(string $url): ?string
    {
        $filename = basename($url);
        $this->line("  ⬇️  Téléchargement de {$filename}...");

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        $archivePath = $this->tempDir . '/' . $filename;

        $response = Http::timeout(120)->get($url);

        if (!$response->successful()) {
            $this->error("  Échec du téléchargement");
            return null;
        }

        file_put_contents($archivePath, $response->body());
        $size = round(filesize($archivePath) / 1024 / 1024, 1);
        $this->line("  ✓ Téléchargé ({$size} MB)");

        return $archivePath;
    }

    protected function extractArchive(string $archivePath): ?string
    {
        $this->line("  📦 Extraction...");

        $extractDir = $this->tempDir . '/extracted';
        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }

        $command = "tar -xzf " . escapeshellarg($archivePath) . " -C " . escapeshellarg($extractDir) . " 2>&1";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("  Échec de l'extraction");
            return null;
        }

        // Supprimer l'archive immédiatement pour économiser l'espace
        unlink($archivePath);

        return $extractDir;
    }

    protected function parseTextes(string $extractDir): void
    {
        $this->line("  🔍 Analyse des textes...");

        $loisOnly = $this->option('lois-only');
        $withArticles = $this->option('with-articles');

        // Trouver tous les fichiers texte
        $textesDir = $this->findDirectory($extractDir, 'texte/struct');

        if (!$textesDir) {
            $this->warn("  Aucun répertoire de textes trouvé");
            return;
        }

        $files = $this->findXmlFiles($textesDir);
        $this->line("  Trouvé " . count($files) . " fichiers texte");

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $this->processTexteFile($file, $withArticles, $loisOnly, $extractDir);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function processTexteFile(string $file, bool $withArticles, bool $loisOnly, string $extractDir): void
    {
        $content = @file_get_contents($file);
        if (!$content) {
            $this->skipped++;
            return;
        }

        $xml = @simplexml_load_string($content);
        if (!$xml) {
            $this->skipped++;
            return;
        }

        // Extraire les métadonnées
        $jorfId = (string) $xml->META->META_COMMUN->ID;
        $nature = (string) $xml->META->META_COMMUN->NATURE;
        $eliUrl = (string) $xml->META->META_COMMUN->ID_ELI;

        // Filtrer si lois-only
        if ($loisOnly && !in_array($nature, ['LOI', 'DECRET', 'ORDONNANCE'])) {
            $this->skipped++;
            return;
        }

        $chronicle = $xml->META->META_SPEC->META_TEXTE_CHRONICLE ?? null;
        if (!$chronicle) {
            $this->skipped++;
            return;
        }

        $nor = (string) $chronicle->NOR;
        $datePubli = (string) $chronicle->DATE_PUBLI;
        $dateTexte = (string) $chronicle->DATE_TEXTE;
        $numParution = (string) $chronicle->NUM_PARUTION;
        $numero = (string) $chronicle->NUM;

        // Chercher le titre dans le fichier de version ou dans la structure
        $titre = $this->extractTitre($xml, $jorfId, $extractDir);

        if (empty($titre)) {
            $titre = "Texte {$jorfId}";
        }

        // Compter les articles
        $nbArticles = count($xml->STRUCT->LIEN_ART ?? []);

        // Créer ou mettre à jour
        $texte = TexteJO::updateOrCreate(
            ['jorf_id' => $jorfId],
            [
                'eli_url' => $eliUrl ?: null,
                'nor' => $nor ?: null,
                'nature' => $nature,
                'numero' => $numero ?: null,
                'titre' => Str::limit($titre, 1000),
                'titre_court' => Str::limit($titre, 200),
                'date_signature' => $dateTexte ? date('Y-m-d', strtotime($dateTexte)) : null,
                'date_publication' => $datePubli ? date('Y-m-d', strtotime($datePubli)) : null,
                'num_parution_jo' => $numParution ?: null,
                'nb_articles' => $nbArticles,
            ]
        );

        if ($texte->wasRecentlyCreated) {
            $this->created++;
        } else {
            $this->updated++;
        }

        // Lier à une loi existante si possible
        if ($nature === 'LOI' && $numero) {
            $this->linkToLoi($texte, $numero);
        }

        // Importer les articles si demandé
        if ($withArticles && $nbArticles > 0) {
            $this->importArticles($texte, $xml, $extractDir);
        }
    }

    protected function extractTitre(\SimpleXMLElement $xml, string $jorfId, string $extractDir): string
    {
        // Chercher dans les articles (le contexte contient souvent le titre)
        $articlesDir = $this->findDirectory($extractDir, 'article');
        if ($articlesDir) {
            $articleFile = $this->findFileById($articlesDir, str_replace('TEXT', 'ARTI', $jorfId));
            if ($articleFile) {
                $articleXml = @simplexml_load_file($articleFile);
                if ($articleXml && isset($articleXml->CONTEXTE->TEXTE->TITRE_TXT)) {
                    return (string) $articleXml->CONTEXTE->TEXTE->TITRE_TXT;
                }
            }
        }

        return '';
    }

    protected function linkToLoi(TexteJO $texte, string $numero): void
    {
        // Chercher une loi avec ce numéro
        $loi = Loi::where('numero', 'LIKE', "%{$numero}%")->first();

        if ($loi) {
            $texte->update(['loi_loicod' => $loi->loicod]);
            $this->linked++;
        }
    }

    protected function importArticles(TexteJO $texte, \SimpleXMLElement $xml, string $extractDir): void
    {
        $articlesDir = $this->findDirectory($extractDir, 'article');
        if (!$articlesDir) {
            return;
        }

        foreach ($xml->STRUCT->LIEN_ART ?? [] as $lienArt) {
            $articleId = (string) $lienArt['id'];
            $numero = (string) $lienArt['num'];

            $articleFile = $this->findFileById($articlesDir, $articleId);
            if (!$articleFile) {
                continue;
            }

            $articleXml = @simplexml_load_file($articleFile);
            if (!$articleXml) {
                continue;
            }

            $contenu = '';
            if (isset($articleXml->BLOC_TEXTUEL->CONTENU)) {
                $contenu = strip_tags($articleXml->BLOC_TEXTUEL->CONTENU->asXML());
                $contenu = html_entity_decode($contenu);
                $contenu = trim(preg_replace('/\s+/', ' ', $contenu));
            }

            $type = (string) ($articleXml->META->META_SPEC->META_ARTICLE->TYPE ?? '');

            ArticleJO::updateOrCreate(
                ['jorf_article_id' => $articleId],
                [
                    'texte_jo_id' => $texte->id,
                    'numero' => $numero ?: null,
                    'type' => $type ?: null,
                    'contenu' => Str::limit($contenu, 65000),
                ]
            );
        }
    }

    protected function findDirectory(string $baseDir, string $pattern): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && str_contains($file->getPathname(), $pattern)) {
                return $file->getPathname();
            }
        }

        return null;
    }

    protected function findXmlFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'xml') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    protected function findFileById(string $dir, string $id): ?string
    {
        $pattern = $dir . '/**/' . $id . '.xml';
        $files = glob($pattern, GLOB_BRACE);

        if (!empty($files)) {
            return $files[0];
        }

        // Recherche récursive
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getBasename('.xml') === $id) {
                return $file->getPathname();
            }
        }

        return null;
    }

    protected function cleanup(): void
    {
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        // Utiliser rm -rf qui gère les symlinks
        exec('rm -rf ' . escapeshellarg($dir) . ' 2>/dev/null');
    }
}

