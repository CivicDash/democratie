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

            // Récupérer la fiche complète du député
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

            // Importer selon les options
            if (!$this->option('interventions-only') && !$this->option('questions-only')) {
                $this->importVotes($depute, $deputeData);
            }

            if (!$this->option('votes-only') && !$this->option('questions-only')) {
                $this->importInterventions($depute, $deputeData);
            }

            if (!$this->option('votes-only') && !$this->option('interventions-only')) {
                $this->importQuestions($depute, $deputeData);
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
     * Importer les votes d'un député
     */
    private function importVotes(DeputeSenateur $depute, array $deputeData)
    {
        $votes = $deputeData['votes'] ?? [];

        foreach ($votes as $voteData) {
            try {
                VoteDepute::updateOrCreate(
                    [
                        'depute_senateur_id' => $depute->id,
                        'numero_scrutin' => $voteData['numero_scrutin'] ?? $voteData['id'] ?? '',
                    ],
                    [
                        'date_vote' => $this->parseDate($voteData['date'] ?? null),
                        'titre' => $voteData['titre'] ?? $voteData['objet'] ?? 'Vote',
                        'position' => $this->normalizePosition($voteData['position'] ?? ''),
                        'resultat' => $this->normalizeResultat($voteData['resultat'] ?? null),
                        'pour' => $voteData['pour'] ?? null,
                        'contre' => $voteData['contre'] ?? null,
                        'abstentions' => $voteData['abstentions'] ?? null,
                        'absents' => $voteData['absents'] ?? null,
                        'type_vote' => $voteData['type'] ?? $voteData['sort'] ?? null,
                        'url_scrutin' => $voteData['url'] ?? null,
                        'contexte' => $voteData['demandeur'] ?? null,
                    ]
                );

                $this->votesImported++;
            } catch (\Exception $e) {
                // Ignorer les erreurs individuelles
            }
        }
    }

    /**
     * Importer les interventions d'un député
     */
    private function importInterventions(DeputeSenateur $depute, array $deputeData)
    {
        $interventions = $deputeData['interventions'] ?? [];

        foreach ($interventions as $interventionData) {
            try {
                // Calculer le nombre de mots si contenu disponible
                $contenu = $interventionData['contenu'] ?? $interventionData['intervention'] ?? null;
                $nbMots = $contenu ? str_word_count(strip_tags($contenu)) : null;

                InterventionParlementaire::updateOrCreate(
                    [
                        'depute_senateur_id' => $depute->id,
                        'date_intervention' => $this->parseDate($interventionData['date'] ?? null),
                        'titre' => $interventionData['titre'] ?? $interventionData['section'] ?? 'Intervention',
                    ],
                    [
                        'type' => $interventionData['type'] ?? 'seance',
                        'sujet' => $interventionData['sujet'] ?? null,
                        'contenu' => $contenu,
                        'nb_mots' => $nbMots,
                        'url_video' => $interventionData['url_video'] ?? null,
                        'url_texte' => $interventionData['url'] ?? null,
                    ]
                );

                $this->interventionsImported++;
            } catch (\Exception $e) {
                // Ignorer les erreurs individuelles
            }
        }
    }

    /**
     * Importer les questions au gouvernement d'un député
     */
    private function importQuestions(DeputeSenateur $depute, array $deputeData)
    {
        $questions = $deputeData['questions'] ?? [];

        foreach ($questions as $questionData) {
            try {
                QuestionGouvernement::updateOrCreate(
                    [
                        'depute_senateur_id' => $depute->id,
                        'numero' => $questionData['numero'] ?? $questionData['id'] ?? '',
                    ],
                    [
                        'type' => $questionData['type'] ?? 'ecrite',
                        'date_depot' => $this->parseDate($questionData['date'] ?? $questionData['date_depot'] ?? null),
                        'date_reponse' => $this->parseDate($questionData['date_reponse'] ?? null),
                        'ministere' => $questionData['ministere'] ?? null,
                        'titre' => $questionData['titre'] ?? $questionData['question'] ?? 'Question',
                        'question' => $questionData['question'] ?? $questionData['question_texte'] ?? null,
                        'reponse' => $questionData['reponse'] ?? $questionData['reponse_texte'] ?? null,
                        'statut' => $questionData['reponse'] ? 'repondu' : 'en_attente',
                        'url' => $questionData['url'] ?? null,
                    ]
                );

                $this->questionsImported++;
            } catch (\Exception $e) {
                // Ignorer les erreurs individuelles
            }
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

