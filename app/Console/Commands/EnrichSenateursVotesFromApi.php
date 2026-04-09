<?php

namespace App\Console\Commands;

use App\Models\DeputeSenateur;
use App\Models\InterventionParlementaire;
use App\Models\QuestionGouvernement;
use App\Models\VoteDepute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichSenateursVotesFromApi extends Command
{
    protected $signature = 'enrich:senateurs-votes 
                            {--limit= : Limiter le nombre de sénateurs} 
                            {--senateur= : UID d\'un sénateur spécifique}
                            {--all : Inclure TOUS les sénateurs (même ceux qui ne sont plus en exercice)}
                            {--votes-only : Importer uniquement les votes}
                            {--interventions-only : Importer uniquement les interventions}
                            {--questions-only : Importer uniquement les questions}';

    protected $description = 'Enrichit les sénateurs avec TOUS les détails : votes, interventions, questions (API NosSénateurs.fr)';

    private const API_BASE_URL = 'https://www.nossenateurs.fr';

    private int $senateursProcessed = 0;

    private int $votesImported = 0;

    private int $interventionsImported = 0;

    private int $questionsImported = 0;

    private int $errors = 0;

    public function handle()
    {
        $this->info('🏛️  Enrichissement COMPLET des sénateurs...');
        $this->newLine();

        $limit = $this->option('limit');
        $senateurUid = $this->option('senateur');
        $includeAll = $this->option('all');

        // Récupérer les sénateurs à enrichir
        $query = DeputeSenateur::where('source', 'senat');

        // Par défaut, uniquement les sénateurs en exercice
        if (! $includeAll) {
            $query->where('en_exercice', true);
        }

        if ($senateurUid) {
            $query->where('uid', $senateurUid);
        }

        if ($limit) {
            $query->limit($limit);
            $this->warn("⚠️  Mode TEST : {$limit} sénateurs maximum");
        }

        $senateurs = $query->get();

        if ($senateurs->isEmpty()) {
            $this->warn('⚠️  Aucun sénateur à enrichir');

            return Command::SUCCESS;
        }

        $this->info("📊 {$senateurs->count()} sénateurs à enrichir");
        $this->info('⏱️  Estimation : '.($senateurs->count() * 2).' secondes (pause de 2s par sénateur)');
        $this->newLine();

        $bar = $this->output->createProgressBar($senateurs->count());
        $bar->setFormat('verbose');

        foreach ($senateurs as $senateur) {
            $this->enrichSenateurComplete($senateur);
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
     * Enrichir un sénateur avec TOUTES ses données
     */
    private function enrichSenateurComplete(DeputeSenateur $senateur)
    {
        try {
            // Construire le slug depuis le nom/prénom
            $slug = $this->buildSlug($senateur);

            if (! $slug) {
                $this->errors++;

                return;
            }

            // Vérifier que le sénateur existe dans l'API
            $response = Http::timeout(30)->get(self::API_BASE_URL."/{$slug}/json");

            if (! $response->successful()) {
                $this->errors++;

                return;
            }

            $data = $response->json();
            $senateurData = $data['senateur'] ?? null;

            if (! $senateurData) {
                $this->errors++;

                return;
            }

            // Importer selon les options (avec endpoints séparés)
            if (! $this->option('interventions-only') && ! $this->option('questions-only')) {
                $this->importVotesFromEndpoint($senateur, $slug);
            }

            if (! $this->option('votes-only') && ! $this->option('questions-only')) {
                $this->importInterventionsFromEndpoint($senateur, $slug);
            }

            if (! $this->option('votes-only') && ! $this->option('interventions-only')) {
                $this->importQuestionsFromEndpoint($senateur, $slug);
            }

            $this->senateursProcessed++;

        } catch (\Exception $e) {
            $this->errors++;
            Log::error("Erreur enrichissement sénateur {$senateur->nom}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Importer les votes d'un sénateur depuis l'endpoint /slug/votes/json
     */
    private function importVotesFromEndpoint(DeputeSenateur $senateur, string $slug)
    {
        try {
            $response = Http::timeout(30)->get(self::API_BASE_URL."/{$slug}/votes/json");

            if (! $response->successful()) {
                return;
            }

            $data = $response->json();
            $votes = $data['votes'] ?? [];

            foreach ($votes as $voteData) {
                try {
                    $vote = $voteData['vote'] ?? $voteData;

                    VoteDepute::updateOrCreate(
                        [
                            'depute_senateur_id' => $senateur->id,
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
     * Importer les interventions d'un sénateur depuis l'endpoint /slug/interventions/json
     */
    private function importInterventionsFromEndpoint(DeputeSenateur $senateur, string $slug)
    {
        try {
            $response = Http::timeout(30)->get(self::API_BASE_URL."/{$slug}/interventions/json");

            if (! $response->successful()) {
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
                            'depute_senateur_id' => $senateur->id,
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
     * Importer les questions au gouvernement d'un sénateur depuis l'endpoint /slug/questions/json
     */
    private function importQuestionsFromEndpoint(DeputeSenateur $senateur, string $slug)
    {
        try {
            $response = Http::timeout(30)->get(self::API_BASE_URL."/{$slug}/questions/json");

            if (! $response->successful()) {
                return;
            }

            $data = $response->json();
            $questions = $data['questions'] ?? [];

            foreach ($questions as $questionData) {
                try {
                    $question = $questionData['question'] ?? $questionData;

                    QuestionGouvernement::updateOrCreate(
                        [
                            'depute_senateur_id' => $senateur->id,
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
                            'statut' => ! empty($question['reponse']) ? 'repondu' : 'en_attente',
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
     * Construire le slug d'un sénateur pour l'API
     */
    private function buildSlug(DeputeSenateur $senateur): ?string
    {
        $prenom = strtolower($senateur->prenom);
        $nom = strtolower($senateur->nom);

        // Normaliser
        $prenom = $this->slugify($prenom);
        $nom = $this->slugify($nom);

        // Prendre le premier prénom uniquement
        $prenomParts = explode('-', $prenom);
        $prenom = $prenomParts[0];

        return $prenom.'-'.$nom;
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
        if (! $date) {
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

        return match ($position) {
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
        if (! $resultat) {
            return null;
        }

        $resultat = strtolower($resultat);

        return match ($resultat) {
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
        $this->info('✅ Enrichissement terminé !');
        $this->newLine();

        $this->info('📊 Résumé :');
        $this->line("   ✓ {$this->senateursProcessed} sénateurs traités");
        $this->line("   📝 {$this->votesImported} votes importés");
        $this->line("   🎤 {$this->interventionsImported} interventions importées");
        $this->line("   ❓ {$this->questionsImported} questions importées");

        if ($this->errors > 0) {
            $this->warn("   ⚠️  {$this->errors} erreurs");
        }

        $this->newLine();

        // Statistiques globales pour les sénateurs
        try {
            $totalVotesSenateurs = VoteDepute::whereHas('deputeSenateur', function ($q) {
                $q->where('source', 'senat');
            })->count();

            $totalInterventionsSenateurs = InterventionParlementaire::whereHas('deputeSenateur', function ($q) {
                $q->where('source', 'senat');
            })->count();

            $totalQuestionsSenateurs = QuestionGouvernement::whereHas('deputeSenateur', function ($q) {
                $q->where('source', 'senat');
            })->count();
        } catch (\Exception $e) {
            $totalVotesSenateurs = 0;
            $totalInterventionsSenateurs = 0;
            $totalQuestionsSenateurs = 0;
            $this->warn('⚠️  Tables non créées. Lancer: php artisan migrate');
        }

        $this->info('📈 Total sénateurs en base de données :');
        $this->line("   {$totalVotesSenateurs} votes");
        $this->line("   {$totalInterventionsSenateurs} interventions");
        $this->line("   {$totalQuestionsSenateurs} questions");
    }
}
