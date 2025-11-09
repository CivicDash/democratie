<?php

namespace App\Console\Commands;

use App\Models\DeputeSenateur;
use App\Models\VoteDepute;
use App\Models\InterventionParlementaire;
use App\Models\QuestionGouvernement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichDeputesVotesFromApi extends Command
{
    protected $signature = 'enrich:deputes-votes 
                            {--limit= : Limiter le nombre de députés} 
                            {--depute= : UID d\'un député spécifique}
                            {--votes-only : Importer uniquement les votes}
                            {--interventions-only : Importer uniquement les interventions}
                            {--questions-only : Importer uniquement les questions}';
    
    protected $description = 'Enrichit les députés avec TOUS les détails : votes, interventions, questions (API NosDéputés.fr)';

    private const API_BASE_URL = 'https://www.nosdeputes.fr';
    private int $deputesProcessed = 0;
    private int $votesImported = 0;
    private int $interventionsImported = 0;
    private int $questionsImported = 0;
    private int $errors = 0;

    public function handle()
    {
        $this->info('🏛️  Enrichissement COMPLET des députés...');
        $this->newLine();

        $limit = $this->option('limit');
        $deputeUid = $this->option('depute');
        
        // Récupérer les députés à enrichir
        $query = DeputeSenateur::where('source', 'assemblee')
            ->where('en_exercice', true);

        if ($deputeUid) {
            $query->where('uid', $deputeUid);
        }

        if ($limit) {
            $query->limit($limit);
            $this->warn("⚠️  Mode TEST : {$limit} députés maximum");
        }

        $deputes = $query->get();

        if ($deputes->isEmpty()) {
            $this->warn('⚠️  Aucun député à enrichir');
            return Command::SUCCESS;
        }

        $this->info("📊 {$deputes->count()} députés à enrichir");
        $this->info("⏱️  Estimation : " . ($deputes->count() * 2) . " secondes (pause de 2s par député)");
        $this->newLine();

        $bar = $this->output->createProgressBar($deputes->count());
        $bar->setFormat('verbose');

        foreach ($deputes as $depute) {
            $this->enrichDeputeComplete($depute);
            $bar->advance();
            
            // Pause obligatoire pour ne pas surcharger l'API
            sleep(2);
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return Command::SUCCESS;
    }

    /**
     * Enrichir un député avec TOUTES ses données
     */
    private function enrichDeputeComplete(DeputeSenateur $depute)
    {
        try {
            // Construire le slug depuis le nom/prénom
            $slug = $this->buildSlug($depute);
            
            if (!$slug) {
                $this->errors++;
                return;
            }

            // Vérifier que le député existe dans l'API
            $response = Http::timeout(30)->get(self::API_BASE_URL . "/{$slug}/json");

            if (!$response->successful()) {
                $this->errors++;
                return;
            }

            $data = $response->json();
            $deputeData = $data['depute'] ?? null;

            if (!$deputeData) {
                $this->errors++;
                return;
            }

            // Importer selon les options (avec endpoints séparés)
            if (!$this->option('interventions-only') && !$this->option('questions-only')) {
                $this->importVotesFromEndpoint($depute, $slug);
            }

            if (!$this->option('votes-only') && !$this->option('questions-only')) {
                $this->importInterventionsFromEndpoint($depute, $slug);
            }

            if (!$this->option('votes-only') && !$this->option('interventions-only')) {
                $this->importQuestionsFromEndpoint($depute, $slug);
            }

            $this->deputesProcessed++;

        } catch (\Exception $e) {
            $this->errors++;
            Log::error("Erreur enrichissement député {$depute->nom}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Importer les votes d'un député depuis l'endpoint /slug/votes/json
     */
    private function importVotesFromEndpoint(DeputeSenateur $depute, string $slug)
    {
        try {
            $response = Http::timeout(30)->get(self::API_BASE_URL . "/{$slug}/votes/json");

            if (!$response->successful()) {
                return;
            }

            $data = $response->json();
            $votes = $data['votes'] ?? [];

            foreach ($votes as $voteData) {
                try {
                    $vote = $voteData['vote'] ?? $voteData;
                    
                    VoteDepute::updateOrCreate(
                        [
                            'depute_senateur_id' => $depute->id,
                            'numero_scrutin' => $vote['numero'] ?? $vote['numero_scrutin'] ?? '',
                        ],
                        [
                            'date_vote' => $this->parseDate($vote['date'] ?? null),
                            'titre' => $vote['titre'] ?? $vote['objet'] ?? 'Vote',
                            'position' => $this->normalizePosition($vote['position'] ?? ''),
                            'resultat' => $this->normalizeResultat($vote['sort'] ?? null),
                            'pour' => $vote['pour'] ?? null,
                            'contre' => $vote['contre'] ?? null,
                            'abstentions' => $vote['abstentions'] ?? null,
                            'absents' => $vote['absents'] ?? null,
                            'type_vote' => $vote['type'] ?? null,
                            'url_scrutin' => $vote['url'] ?? null,
                            'contexte' => $vote['demandeur'] ?? null,
                        ]
                    );

                    $this->votesImported++;
                } catch (\Exception $e) {
                    // Ignorer les erreurs individuelles
                }
            }
        } catch (\Exception $e) {
            // Ignorer si l'endpoint n'existe pas
        }
    }

    /**
     * Importer les interventions d'un député depuis l'endpoint /slug/interventions/json
     */
    private function importInterventionsFromEndpoint(DeputeSenateur $depute, string $slug)
    {
        try {
            $response = Http::timeout(30)->get(self::API_BASE_URL . "/{$slug}/interventions/json");

            if (!$response->successful()) {
                return;
            }

            $data = $response->json();
            $interventions = $data['interventions'] ?? [];

            foreach ($interventions as $interventionData) {
                try {
                    $inter = $interventionData['intervention'] ?? $interventionData;
                    
                    // Calculer le nombre de mots si contenu disponible
                    $contenu = $inter['intervention'] ?? $inter['contenu'] ?? null;
                    $nbMots = $contenu ? str_word_count(strip_tags($contenu)) : null;

                    InterventionParlementaire::updateOrCreate(
                        [
                            'depute_senateur_id' => $depute->id,
                            'date_intervention' => $this->parseDate($inter['date'] ?? null),
                            'titre' => $inter['titre'] ?? $inter['section'] ?? 'Intervention',
                        ],
                        [
                            'type' => $inter['type'] ?? 'seance',
                            'sujet' => $inter['sujet'] ?? $inter['section'] ?? null,
                            'contenu' => $contenu,
                            'nb_mots' => $nbMots,
                            'url_video' => $inter['url_video'] ?? null,
                            'url_texte' => $inter['url'] ?? null,
                        ]
                    );

                    $this->interventionsImported++;
                } catch (\Exception $e) {
                    // Ignorer les erreurs individuelles
                }
            }
        } catch (\Exception $e) {
            // Ignorer si l'endpoint n'existe pas
        }
    }

    /**
     * Importer les questions au gouvernement d'un député depuis l'endpoint /slug/questions/json
     */
    private function importQuestionsFromEndpoint(DeputeSenateur $depute, string $slug)
    {
        try {
            $response = Http::timeout(30)->get(self::API_BASE_URL . "/{$slug}/questions/json");

            if (!$response->successful()) {
                return;
            }

            $data = $response->json();
            $questions = $data['questions'] ?? [];

            foreach ($questions as $questionData) {
                try {
                    $question = $questionData['question'] ?? $questionData;
                    
                    QuestionGouvernement::updateOrCreate(
                        [
                            'depute_senateur_id' => $depute->id,
                            'numero' => $question['numero'] ?? $question['id'] ?? '',
                        ],
                        [
                            'type' => $question['type'] ?? 'ecrite',
                            'date_depot' => $this->parseDate($question['date'] ?? $question['date_depot'] ?? null),
                            'date_reponse' => $this->parseDate($question['date_reponse'] ?? null),
                            'ministere' => $question['ministere'] ?? null,
                            'titre' => $question['titre'] ?? $question['question'] ?? 'Question',
                            'question' => $question['question'] ?? $question['question_texte'] ?? null,
                            'reponse' => $question['reponse'] ?? $question['reponse_texte'] ?? null,
                            'statut' => !empty($question['reponse']) ? 'repondu' : 'en_attente',
                            'url' => $question['url'] ?? null,
                        ]
                    );

                    $this->questionsImported++;
                } catch (\Exception $e) {
                    // Ignorer les erreurs individuelles
                }
            }
        } catch (\Exception $e) {
            // Ignorer si l'endpoint n'existe pas
        }
    }

    /**
     * Construire le slug d'un député pour l'API
     */
    private function buildSlug(DeputeSenateur $depute): ?string
    {
        $prenom = strtolower($depute->prenom);
        $nom = strtolower($depute->nom);
        
        // Normaliser
        $prenom = $this->slugify($prenom);
        $nom = $this->slugify($nom);
        
        // Prendre le premier prénom uniquement
        $prenomParts = explode('-', $prenom);
        $prenom = $prenomParts[0];
        
        return $prenom . '-' . $nom;
    }

    /**
     * Slugify une chaîne
     */
    private function slugify(string $str): string
    {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/[\s-]+/', '-', $str);
        $str = trim($str, '-');
        return $str;
    }

    /**
     * Parser une date
     */
    private function parseDate(?string $date)
    {
        if (!$date) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Normaliser la position d'un vote
     */
    private function normalizePosition(string $position): string
    {
        $position = strtolower($position);
        
        return match($position) {
            'pour', 'oui' => 'pour',
            'contre', 'non' => 'contre',
            'abstention', 'abstenu' => 'abstention',
            'absent', 'absente' => 'absent',
            default => $position,
        };
    }

    /**
     * Normaliser le résultat d'un vote
     */
    private function normalizeResultat(?string $resultat): ?string
    {
        if (!$resultat) {
            return null;
        }

        $resultat = strtolower($resultat);
        
        return match($resultat) {
            'adopte', 'adoptée', 'oui' => 'adopte',
            'rejete', 'rejetée', 'non' => 'rejete',
            default => $resultat,
        };
    }

    /**
     * Afficher le résumé
     */
    private function displaySummary()
    {
        $this->info("✅ Enrichissement terminé !");
        $this->newLine();
        
        $this->info("📊 Résumé :");
        $this->line("   ✓ {$this->deputesProcessed} députés traités");
        $this->line("   📝 {$this->votesImported} votes importés");
        $this->line("   🎤 {$this->interventionsImported} interventions importées");
        $this->line("   ❓ {$this->questionsImported} questions importées");
        
        if ($this->errors > 0) {
            $this->warn("   ⚠️  {$this->errors} erreurs");
        }

        $this->newLine();
        
        // Statistiques globales
        try {
            $totalVotes = VoteDepute::count();
            $totalInterventions = InterventionParlementaire::count();
            $totalQuestions = QuestionGouvernement::count();
        } catch (\Exception $e) {
            $totalVotes = 0;
            $totalInterventions = 0;
            $totalQuestions = 0;
            $this->warn("⚠️  Tables non créées. Lancer: php artisan migrate");
        }

        $this->info("📈 Total en base de données :");
        $this->line("   {$totalVotes} votes");
        $this->line("   {$totalInterventions} interventions");
        $this->line("   {$totalQuestions} questions");
    }
}

