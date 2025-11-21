<?php

namespace App\Console\Commands;

use App\Models\Senateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportQuestionsSenat extends Command
{
    protected $signature = 'import:questions-senat 
                            {--limit= : Limite du nombre de questions (pour tests)}
                            {--fresh : Vider la table avant import}';

    protected $description = 'Importe les Questions au Gouvernement du Sénat depuis data.senat.fr';

    private int $imported = 0;
    private int $updated = 0;
    private int $errors = 0;

    /**
     * API data.senat.fr - Questions
     * https://data.senat.fr/data/opendata/ODSEN_QUESTIONS.csv
     */
    public function handle(): int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $fresh = $this->option('fresh');

        $this->info("🏛️  Import des Questions au Gouvernement (Sénat)...");

        if ($fresh) {
            $this->warn("⚠️  Mode --fresh : suppression des questions existantes...");
            DB::table('senateurs_questions')->truncate();
        }

        if ($limit) {
            $this->warn("⚠️  Mode TEST : {$limit} questions maximum");
        }

        // Récupérer le CSV
        $this->info("📥 Téléchargement du CSV...");
        $csvUrl = 'https://data.senat.fr/data/opendata/ODSEN_QUESTIONS.csv';

        try {
            $response = Http::timeout(60)->get($csvUrl);

            if (!$response->successful()) {
                $this->error("❌ Erreur HTTP : " . $response->status());
                return Command::FAILURE;
            }

            $csvContent = $response->body();
            $lines = explode("\n", $csvContent);
            $headers = null;
            $questions = [];

            foreach ($lines as $index => $line) {
                if ($index === 0) {
                    $headers = str_getcsv($line, ';');
                    continue;
                }

                if (empty(trim($line))) {
                    continue;
                }

                $data = str_getcsv($line, ';');

                if (count($data) < count($headers)) {
                    continue;
                }

                $row = array_combine($headers, $data);
                $questions[] = $row;

                if ($limit && count($questions) >= $limit) {
                    break;
                }
            }

            $this->info("📊 " . count($questions) . " questions trouvées");

            // Import
            $progressBar = $this->output->createProgressBar(count($questions));
            $progressBar->start();

            foreach ($questions as $questionData) {
                try {
                    $this->importQuestion($questionData);
                } catch (\Exception $e) {
                    $this->errors++;
                    if ($this->errors <= 5) {
                        $this->newLine();
                        $this->error("❌ Erreur : " . $e->getMessage());
                    }
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

        } catch (\Exception $e) {
            $this->error("❌ Erreur : " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->displaySummary();

        return Command::SUCCESS;
    }

    private function importQuestion(array $data): void
    {
        $matricule = $data['Auteur_matricule'] ?? null;
        $numero = $data['Numero'] ?? null;

        if (!$matricule || !$numero) {
            $this->errors++;
            return;
        }

        // Vérifier si le sénateur existe
        $senateur = Senateur::where('matricule', $matricule)->first();

        if (!$senateur) {
            $this->errors++;
            return;
        }

        $result = DB::table('senateurs_questions')->updateOrInsert(
            [
                'senateur_matricule' => $matricule,
                'numero' => $numero,
            ],
            [
                'type' => $data['Type'] ?? 'Orale',
                'texte_question' => $data['Texte_question'] ?? null,
                'ministre_destinataire' => $data['Ministre'] ?? null,
                'date_question' => $this->parseDate($data['Date_question'] ?? null),
                'date_reponse' => $this->parseDate($data['Date_reponse'] ?? null),
                'texte_reponse' => $data['Texte_reponse'] ?? null,
                'a_reponse' => !empty($data['Date_reponse']),
                'theme' => $data['Theme'] ?? null,
                'sous_theme' => $data['Sous_theme'] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if ($result) {
            $this->imported++;
        } else {
            $this->updated++;
        }
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date || empty(trim($date))) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function displaySummary(): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Nouvelles questions', $this->imported],
                ['↻ Questions mises à jour', $this->updated],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        $total = DB::table('senateurs_questions')->count();
        $avecReponse = DB::table('senateurs_questions')->where('a_reponse', true)->count();
        $sansReponse = $total - $avecReponse;

        $this->info("📊 Total en base : {$total} questions");
        $this->info("   - Avec réponse : {$avecReponse}");
        $this->info("   - Sans réponse : {$sansReponse}");
    }
}

