<?php

namespace App\Console\Commands;

use App\Models\QuestionAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class ImportQuestionsAN extends Command
{
    protected $signature = 'import:questions-an
                            {--legislature=17 : Numéro de la législature}
                            {--fresh : Vider la table avant import}
                            {--limit= : Limite du nombre de questions (pour tests)}
                            {--force : Forcer le re-téléchargement}';

    protected $description = 'Importe les Questions au Gouvernement depuis data.assemblee-nationale.fr';

    private int $imported = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $legislature = $this->option('legislature');
        $fresh = $this->option('fresh');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $force = $this->option('force');

        $this->info("🏛️  Import des Questions au Gouvernement (L{$legislature})...");

        // URL du fichier ZIP
        $url = "https://data.assemblee-nationale.fr/static/openData/repository/{$legislature}/questions/questions_gouvernement/Questions_gouvernement.xml.zip";

        // Téléchargement
        $zipPath = $this->downloadZip($url, $force);
        if (! $zipPath) {
            $this->error('❌ Impossible de télécharger le fichier ZIP');

            return Command::FAILURE;
        }

        // Fresh mode
        if ($fresh) {
            $this->warn('⚠️  Mode --fresh : suppression des questions existantes...');
            QuestionAN::where('legislature', $legislature)->delete();
        }

        // Extraction et import
        $xmlFiles = $this->extractZip($zipPath);
        if (empty($xmlFiles)) {
            $this->error("❌ Aucun fichier XML trouvé dans l'archive");

            return Command::FAILURE;
        }

        $this->info("📄 {$this->countFiles($xmlFiles)} fichiers XML à traiter");

        if ($limit) {
            $this->warn("⚠️  Mode TEST : {$limit} questions maximum");
            $xmlFiles = array_slice($xmlFiles, 0, $limit);
        }

        $bar = $this->output->createProgressBar(count($xmlFiles));
        $bar->start();

        foreach ($xmlFiles as $xmlFile) {
            try {
                $this->processXmlFile($xmlFile);
            } catch (\Exception $e) {
                $this->errors++;
                $this->warn("\n⚠️  Erreur sur {$xmlFile}: ".$e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Nettoyage
        $this->cleanup($zipPath, $xmlFiles);

        // Résumé
        $this->displaySummary();

        return Command::SUCCESS;
    }

    private function downloadZip(string $url, bool $force): ?string
    {
        $storagePath = storage_path('app/an-data');
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $zipPath = $storagePath.'/questions_gouvernement.zip';

        // Vérifier si le fichier existe et n'est pas trop vieux (24h)
        if (! $force && file_exists($zipPath)) {
            $age = time() - filemtime($zipPath);
            if ($age < 86400) { // 24 heures
                $this->info('📦 Utilisation du cache (âge: '.round($age / 3600, 1).'h)');

                return $zipPath;
            }
        }

        $this->info("📥 Téléchargement depuis {$url}...");

        try {
            $response = Http::timeout(120)->get($url);

            if ($response->successful()) {
                file_put_contents($zipPath, $response->body());
                $this->info('✅ Téléchargement terminé ('.round(filesize($zipPath) / 1024 / 1024, 2).' Mo)');

                return $zipPath;
            }

            $this->error('❌ Erreur HTTP: '.$response->status());

            return null;
        } catch (\Exception $e) {
            $this->error('❌ Erreur de téléchargement: '.$e->getMessage());

            return null;
        }
    }

    private function extractZip(string $zipPath): array
    {
        $extractPath = storage_path('app/an-data/questions_xml');

        // Nettoyer le dossier d'extraction
        if (is_dir($extractPath)) {
            $this->deleteDirectory($extractPath);
        }
        mkdir($extractPath, 0755, true);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            $this->error("❌ Impossible d'ouvrir l'archive ZIP");

            return [];
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Lister les fichiers XML
        $xmlFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'xml') {
                $xmlFiles[] = $file->getPathname();
            }
        }

        return $xmlFiles;
    }

    private function countFiles(array $files): int
    {
        return count($files);
    }

    private function processXmlFile(string $xmlPath): void
    {
        $content = file_get_contents($xmlPath);
        if (! $content) {
            throw new \Exception('Impossible de lire le fichier');
        }

        // Parser le XML
        $xml = @simplexml_load_string($content);
        if (! $xml) {
            throw new \Exception('XML invalide');
        }

        // Extraire les données
        $data = $this->parseQuestion($xml);

        if (! $data['uid']) {
            throw new \Exception('UID manquant');
        }

        // Upsert
        $existing = QuestionAN::find($data['uid']);
        if ($existing) {
            $existing->update($data);
            $this->updated++;
        } else {
            QuestionAN::create($data);
            $this->imported++;
        }
    }

    private function parseQuestion(\SimpleXMLElement $xml): array
    {
        $ns = $xml->getNamespaces(true);

        // Identifiant
        $uid = (string) $xml->uid;
        $numero = (int) ($xml->identifiant->numero ?? 0);
        $legislature = (int) ($xml->identifiant->legislature ?? 17);
        $type = (string) ($xml->type ?? 'QG');

        // Auteur
        $auteur = $xml->auteur;
        $acteurRef = (string) ($auteur->identite->acteurRef ?? '');
        $mandatRef = (string) ($auteur->identite->mandatRef ?? '');
        $groupeRef = (string) ($auteur->groupe->organeRef ?? '');
        $groupeSigle = (string) ($auteur->groupe->abrege ?? '');
        $groupeNom = (string) ($auteur->groupe->developpe ?? '');

        // Ministère
        $minInt = $xml->minInt;
        $ministereRef = (string) ($minInt->organeRef ?? '');
        $ministereSigle = (string) ($minInt->abrege ?? '');
        $ministereNom = (string) ($minInt->developpe ?? '');

        // Indexation
        $rubrique = (string) ($xml->indexationAN->rubrique ?? '');
        $analyse = '';
        if (isset($xml->indexationAN->analyses->analyse)) {
            $analyses = [];
            foreach ($xml->indexationAN->analyses->analyse as $a) {
                $analyses[] = (string) $a;
            }
            $analyse = implode(', ', $analyses);
        }

        // Textes
        $texteQuestion = null;
        if (isset($xml->textesQuestion->texteQuestion->texte)) {
            $texteQuestion = (string) $xml->textesQuestion->texteQuestion->texte;
        }

        $texteReponse = null;
        $dateReponse = null;
        $pageJo = null;
        if (isset($xml->textesReponse->texteReponse)) {
            $reponse = $xml->textesReponse->texteReponse;
            $texteReponse = (string) ($reponse->texte ?? '');
            $dateReponse = (string) ($reponse->infoJO->dateJO ?? '');
            $pageJo = (string) ($reponse->infoJO->pageJO ?? '');
        }

        // Clôture
        $codeCloture = (string) ($xml->cloture->codeCloture ?? '');
        $libelleCloture = (string) ($xml->cloture->libelleCloture ?? '');
        $dateCloture = (string) ($xml->cloture->dateCloture ?? '');
        $dateQuestion = (string) ($xml->cloture->infoJO->dateJO ?? '');

        return [
            'uid' => $uid,
            'numero' => $numero,
            'legislature' => $legislature,
            'type' => $type,
            'acteur_ref' => $acteurRef ?: null,
            'mandat_ref' => $mandatRef ?: null,
            'groupe_ref' => $groupeRef ?: null,
            'groupe_sigle' => $groupeSigle ?: null,
            'groupe_nom' => $groupeNom ?: null,
            'ministere_ref' => $ministereRef ?: null,
            'ministere_sigle' => $ministereSigle ?: null,
            'ministere_nom' => $ministereNom ?: null,
            'rubrique' => $rubrique ?: null,
            'analyse' => $analyse ?: null,
            'texte_question' => $texteQuestion,
            'texte_reponse' => $texteReponse,
            'date_question' => $dateQuestion ?: null,
            'date_reponse' => $dateReponse ?: null,
            'page_jo' => $pageJo ?: null,
            'code_cloture' => $codeCloture ?: null,
            'libelle_cloture' => $libelleCloture ?: null,
            'date_cloture' => $dateCloture ?: null,
        ];
    }

    private function cleanup(string $zipPath, array $xmlFiles): void
    {
        $extractPath = storage_path('app/an-data/questions_xml');
        if (is_dir($extractPath)) {
            $this->deleteDirectory($extractPath);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function displaySummary(): void
    {
        $this->info("📊 Résumé de l'import :");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✅ Importées', $this->imported],
                ['🔄 Mises à jour', $this->updated],
                ['⏭️  Ignorées', $this->skipped],
                ['❌ Erreurs', $this->errors],
                ['📊 Total traité', $this->imported + $this->updated + $this->skipped + $this->errors],
            ]
        );

        // Stats finales
        $total = QuestionAN::count();
        $repondues = QuestionAN::repondues()->count();
        $this->newLine();
        $this->info('📈 Base de données :');
        $this->info("   Total questions : {$total}");
        $this->info("   Répondues : {$repondues}");
        $this->info('   En attente : '.($total - $repondues));
    }
}
