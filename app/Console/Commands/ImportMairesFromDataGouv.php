<?php

namespace App\Console\Commands;

use App\Models\Maire;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportMairesFromDataGouv extends Command
{
    protected $signature = 'import:maires-datagouv 
                            {--fresh : Vider la table des maires avant l\'import}
                            {--limit= : Limiter le nombre d\'imports (pour test)}';

    protected $description = 'Importe les maires depuis data.gouv.fr (Répertoire National des Élus)';

    // Fichier officiel RNE - Maires
    private const API_URL = 'https://www.data.gouv.fr/api/1/datasets/r/2876a346-d50c-4911-934e-19ee07b0e503';

    private int $created = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $this->info('🏛️ Import des maires depuis data.gouv.fr (RNE)...');
        $this->info('📡 URL: '.self::API_URL);
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des données existantes...');
            Maire::truncate();
        }

        // Télécharger les données
        $this->info('📥 Téléchargement du fichier CSV (peut prendre quelques secondes)...');

        try {
            $response = Http::timeout(300)->get(self::API_URL);

            if (! $response->successful()) {
                $this->error("❌ Erreur HTTP: {$response->status()}");

                return self::FAILURE;
            }

            $csvContent = $response->body();
            $lines = explode("\n", $csvContent);

            // Retirer l'en-tête
            $header = str_getcsv(array_shift($lines), ';');

            // Filtrer les lignes vides
            $lines = array_filter($lines, fn ($line) => trim($line) !== '');

            $this->info('✅ '.count($lines).' maires trouvés dans le fichier');

        } catch (\Exception $e) {
            $this->error("❌ Erreur de téléchargement: {$e->getMessage()}");

            return self::FAILURE;
        }

        $limit = $this->option('limit');
        $total = $limit ? min((int) $limit, count($lines)) : count($lines);

        if ($limit) {
            $this->warn("⚠️  Mode TEST : limité à {$limit} maires");
            $lines = array_slice($lines, 0, (int) $limit);
        }

        $this->newLine();
        $this->info("📊 Traitement de {$total} maires...");
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat('verbose');
        $bar->start();

        $batch = [];
        $batchSize = 500;

        foreach ($lines as $line) {
            try {
                $data = str_getcsv($line, ';');

                if (count($data) < 14) {
                    $this->skipped++;
                    $bar->advance();

                    continue;
                }

                $maireData = $this->processLine($data);

                if ($maireData) {
                    $batch[] = $maireData;

                    if (count($batch) >= $batchSize) {
                        $this->upsertBatch($batch);
                        $batch = [];
                    }
                }
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->output->isVerbose()) {
                    $this->error("Erreur: {$e->getMessage()}");
                }
            }
            $bar->advance();
        }

        // Traiter le dernier batch
        if (! empty($batch)) {
            $this->upsertBatch($batch);
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return self::SUCCESS;
    }

    private function processLine(array $data): ?array
    {
        // Format CSV :
        // 0: Code du département
        // 1: Libellé du département
        // 2: Code de la collectivité à statut particulier
        // 3: Libellé de la collectivité à statut particulier
        // 4: Code de la commune
        // 5: Libellé de la commune
        // 6: Nom de l'élu
        // 7: Prénom de l'élu
        // 8: Code sexe (M/F)
        // 9: Date de naissance (dd/mm/yyyy)
        // 10: Code de la catégorie socio-professionnelle
        // 11: Libellé de la catégorie socio-professionnelle
        // 12: Date de début du mandat (dd/mm/yyyy)
        // 13: Date de début de la fonction (dd/mm/yyyy)

        $deptCode = trim($data[0] ?? '');
        $deptName = trim($data[1] ?? '');
        $cspCode = trim($data[2] ?? '');
        $cspName = trim($data[3] ?? '');
        $codeCommune = trim($data[4] ?? '');
        $nomCommune = trim($data[5] ?? '');
        $nom = trim($data[6] ?? '');
        $prenom = trim($data[7] ?? '');
        $sexeCode = trim($data[8] ?? '');
        $dateNaissance = trim($data[9] ?? '');
        $professionCode = trim($data[10] ?? '');
        $profession = trim($data[11] ?? '');
        $dateDebutMandat = trim($data[12] ?? '');
        $dateDebutFonction = trim($data[13] ?? '');

        // Validation minimale
        if (empty($nom) || empty($prenom) || empty($codeCommune)) {
            $this->skipped++;

            return null;
        }

        // Utiliser le code CSP si pas de département (DOM-TOM)
        if (empty($deptCode) && ! empty($cspCode)) {
            $deptCode = $cspCode;
            $deptName = $cspName;
        }

        // Civilité selon le code sexe
        $civilite = $sexeCode === 'F' ? 'Mme' : 'M.';

        $parsedDebutMandat = $this->parseDate($dateDebutMandat);
        $isPost2026 = $parsedDebutMandat && $parsedDebutMandat >= '2026-03-15';
        $mandature = $isPost2026 ? '2026-2032' : '2020-2026';
        $uid = $isPost2026 ? "MAIRE-2026-{$codeCommune}" : "MAIRE-{$codeCommune}";

        return [
            'uid' => $uid,
            'nom' => mb_convert_case($nom, MB_CASE_TITLE, 'UTF-8'),
            'prenom' => mb_convert_case($prenom, MB_CASE_TITLE, 'UTF-8'),
            'nom_complet' => $civilite.' '.mb_convert_case($prenom, MB_CASE_TITLE, 'UTF-8').' '.mb_convert_case($nom, MB_CASE_TITLE, 'UTF-8'),
            'civilite' => $civilite,
            'date_naissance' => $this->parseDate($dateNaissance),
            'code_commune' => $codeCommune,
            'nom_commune' => $nomCommune,
            'code_departement' => $deptCode,
            'nom_departement' => $deptName,
            'profession' => $profession,
            'categorie_socio_pro' => $professionCode,
            'debut_mandat' => $parsedDebutMandat,
            'debut_fonction' => $this->parseDate($dateDebutFonction),
            'en_exercice' => true,
            'mandature' => $mandature,
            'updated_at' => now(),
        ];
    }

    private function parseDate(?string $dateStr): ?string
    {
        if (! $dateStr || $dateStr === '') {
            return null;
        }

        try {
            // Format dd/mm/yyyy
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $matches)) {
                return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
            }

            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function upsertBatch(array $batch): void
    {
        $preservedFields = ['nuance_politique', 'telephone', 'site_web', 'adresse_mairie', 'latitude', 'longitude',
            'photo_url', 'photo_wikipedia_url', 'wikipedia_url', 'wikidata_id', 'wikipedia_extract',
            'wikipedia_last_sync', 'personne_politique_id', 'twitter_url', 'facebook_url',
            'instagram_url', 'linkedin_url', 'fiche_enrichie', 'url_hatvp', 'hatvp_type_mandat'];

        foreach ($batch as $maireData) {
            $existing = Maire::where('uid', $maireData['uid'])->first();

            if ($existing) {
                $dataToUpdate = array_filter($maireData, fn ($v, $k) => $v !== null && ! in_array($k, array_merge(['uid'], $preservedFields)),
                    ARRAY_FILTER_USE_BOTH
                );
                $existing->update($dataToUpdate);
                $this->updated++;
            } else {
                $maireData['created_at'] = now();
                Maire::create($maireData);
                $this->created++;
            }
        }
    }

    private function displaySummary(): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Créés', $this->created],
                ['↻ Mis à jour', $this->updated],
                ['⊘ Ignorés', $this->skipped],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $total = Maire::count();
        $enExercice = Maire::enExercice()->count();

        $this->newLine();
        $this->info("📊 Total maires en base : {$total}");
        $this->info("📊 Maires en exercice : {$enExercice}");

        // Top départements
        $topDepts = Maire::selectRaw('code_departement, nom_departement, COUNT(*) as total')
            ->whereNotNull('code_departement')
            ->where('code_departement', '!=', '')
            ->groupBy('code_departement', 'nom_departement')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        if ($topDepts->isNotEmpty()) {
            $this->newLine();
            $this->info('📊 Top 10 départements :');
            foreach ($topDepts as $d) {
                $this->line("   - {$d->code_departement} ({$d->nom_departement}) : {$d->total} maires");
            }
        }

        // Stats catégories socio-pro
        $topProfessions = Maire::selectRaw('profession, COUNT(*) as total')
            ->whereNotNull('profession')
            ->where('profession', '!=', '')
            ->groupBy('profession')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($topProfessions->isNotEmpty()) {
            $this->newLine();
            $this->info('📊 Top 5 catégories socio-professionnelles :');
            foreach ($topProfessions as $p) {
                $this->line('   - '.Str::limit($p->profession, 50)." : {$p->total}");
            }
        }
    }
}
