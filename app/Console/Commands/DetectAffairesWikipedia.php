<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\AffaireJudiciaire;
use App\Models\Maire;
use App\Models\PersonnePolitique;
use App\Models\Senateur;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DetectAffairesWikipedia extends Command
{
    protected $signature = 'affaires:detect-wikipedia
        {--type=all : depute|senateur|gouvernement|maire|all}
        {--limit=200 : Nombre max d\'entités à scanner}
        {--dry-run : Simuler sans écrire}';

    protected $description = 'Détecte les affaires judiciaires via analyse NLP des extraits Wikipedia';

    private const PATTERNS_HAUTE = [
        '/condamn[ée]e?\s.{0,80}(tribunal|cour|justice)/iu' => 0.70,
        '/peine\s+de\s+(prison|.*mois|.*an)/iu' => 0.70,
        '/inéligibilit[ée]/iu' => 0.70,
        '/condamn[ée]e?\s+[àa]\s+\d+/iu' => 0.70,
    ];

    private const PATTERNS_MOYENNE = [
        '/mis[e]?\s+en\s+examen/iu' => 0.45,
        '/renvoy[ée]e?\s+devant\s+(le\s+)?tribunal/iu' => 0.45,
        '/garde\s+[àa]\s+vue/iu' => 0.45,
        '/poursuivi[e]?\s+(pour|en)/iu' => 0.40,
        '/placement\s+en\s+détention/iu' => 0.50,
    ];

    private int $detected = 0;
    private int $duplicates = 0;

    public function handle(): int
    {
        $type = $this->option('type');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('Détection Wikipedia NLP des affaires judiciaires...');
        if ($dryRun) {
            $this->warn('Mode simulation (dry-run)');
        }

        if (in_array($type, ['all', 'depute'])) {
            $this->scanDeputes($limit, $dryRun);
        }
        if (in_array($type, ['all', 'senateur'])) {
            $this->scanSenateurs($limit, $dryRun);
        }
        if (in_array($type, ['all', 'gouvernement'])) {
            $this->scanGouvernement($limit, $dryRun);
        }
        if (in_array($type, ['all', 'maire'])) {
            $this->scanMaires($limit, $dryRun);
        }

        $this->newLine();
        $this->info("Résultat : {$this->detected} affaire(s) détectée(s), {$this->duplicates} doublon(s) ignoré(s)");

        return self::SUCCESS;
    }

    private function scanDeputes(int $limit, bool $dryRun): void
    {
        $this->info('Scan des députés...');
        $deputes = ActeurAN::deputes()
            ->whereNotNull('wikipedia_extract')
            ->where('wikipedia_extract', '!=', '')
            ->limit($limit)
            ->get();

        $bar = $this->output->createProgressBar($deputes->count());

        foreach ($deputes as $depute) {
            $this->analyzeExtract($depute->wikipedia_extract, [
                'acteur_an_uid' => $depute->uid,
                'nom' => $depute->nom,
                'prenom' => $depute->prenom,
                'parti_politique' => $depute->groupe_politique_actuel?->libelle_abrege,
                'fonction_au_moment' => 'Député',
            ], $depute->wikipedia_url, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function scanSenateurs(int $limit, bool $dryRun): void
    {
        $this->info('Scan des sénateurs...');
        $senateurs = Senateur::actifs()
            ->whereNotNull('wikipedia_extract')
            ->where('wikipedia_extract', '!=', '')
            ->limit($limit)
            ->get();

        $bar = $this->output->createProgressBar($senateurs->count());

        foreach ($senateurs as $senateur) {
            $this->analyzeExtract($senateur->wikipedia_extract, [
                'senateur_matricule' => $senateur->matricule,
                'nom' => $senateur->nom_usuel,
                'prenom' => $senateur->prenom_usuel,
                'parti_politique' => $senateur->groupe_politique,
                'fonction_au_moment' => 'Sénateur',
            ], $senateur->wikipedia_url, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function scanGouvernement(int $limit, bool $dryRun): void
    {
        $this->info('Scan du gouvernement (PersonnePolitique)...');
        $personnes = PersonnePolitique::whereNotNull('wikipedia_extract')
            ->where('wikipedia_extract', '!=', '')
            ->limit($limit)
            ->get();

        $bar = $this->output->createProgressBar($personnes->count());

        foreach ($personnes as $personne) {
            $eluData = [
                'personne_politique_id' => $personne->id,
                'nom' => $personne->nom,
                'prenom' => $personne->prenom,
                'parti_politique' => $personne->parti_politique,
                'fonction_au_moment' => $personne->posteActuel->first()?->fonction ?? 'Membre du gouvernement',
            ];

            if ($personne->uid_an) {
                $eluData['acteur_an_uid'] = $personne->uid_an;
            }
            if ($personne->uid_senat) {
                $eluData['senateur_matricule'] = $personne->uid_senat;
            }

            $this->analyzeExtract($personne->wikipedia_extract, $eluData, $personne->wikipedia_url, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function scanMaires(int $limit, bool $dryRun): void
    {
        $this->info('Scan des maires (communes >= 10 000 hab.)...');
        $maires = Maire::enExercice()
            ->whereNotNull('wikipedia_extract')
            ->where('wikipedia_extract', '!=', '')
            ->where('population_commune', '>=', 10000)
            ->limit($limit)
            ->get();

        $bar = $this->output->createProgressBar($maires->count());

        foreach ($maires as $maire) {
            $this->analyzeExtract($maire->wikipedia_extract, [
                'maire_id' => $maire->id,
                'nom' => $maire->nom,
                'prenom' => $maire->prenom,
                'parti_politique' => $maire->nuance_libelle,
                'fonction_au_moment' => 'Maire de ' . $maire->nom_commune,
            ], $maire->wikipedia_url, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function analyzeExtract(string $extract, array $eluData, ?string $sourceUrl, bool $dryRun): void
    {
        $allPatterns = array_merge(self::PATTERNS_HAUTE, self::PATTERNS_MOYENNE);

        foreach ($allPatterns as $pattern => $confidence) {
            if (preg_match($pattern, $extract, $matches, PREG_OFFSET_CAPTURE)) {
                $matchPos = $matches[0][1];
                $matchText = $matches[0][0];

                $start = max(0, $matchPos - 150);
                $end = min(mb_strlen($extract), $matchPos + mb_strlen($matchText) + 150);
                $context = mb_substr($extract, $start, $end - $start);

                $titre = $this->extractTitre($context, $matchText, $eluData);

                $isDuplicate = AffaireJudiciaire::where(function ($q) use ($eluData) {
                    if (isset($eluData['acteur_an_uid'])) {
                        $q->where('acteur_an_uid', $eluData['acteur_an_uid']);
                    } elseif (isset($eluData['senateur_matricule'])) {
                        $q->where('senateur_matricule', $eluData['senateur_matricule']);
                    } elseif (isset($eluData['personne_politique_id'])) {
                        $q->where('personne_politique_id', $eluData['personne_politique_id']);
                    } elseif (isset($eluData['maire_id'])) {
                        $q->where('maire_id', $eluData['maire_id']);
                    }
                })->where('source_detection', 'wikipedia_nlp')
                  ->where('titre', $titre)
                  ->exists();

                if ($isDuplicate) {
                    $this->duplicates++;
                    return;
                }

                if ($dryRun) {
                    $this->line("  [DRY] {$eluData['prenom']} {$eluData['nom']} (conf={$confidence}) : {$titre}");
                    $this->detected++;
                    return;
                }

                $affaire = AffaireJudiciaire::create(array_merge($eluData, [
                    'titre' => $titre,
                    'description' => Str::limit($context, 2000),
                    'type_affaire' => $this->guessTypeFromContext($context),
                    'categorie' => $this->guessCategorieFromContext($context),
                    'statut_judiciaire' => $this->guessStatutFromContext($context),
                    'statut_validation' => 'detecte',
                    'affiche_publiquement' => false,
                    'source_detection' => 'wikipedia_nlp',
                    'detecte_at' => now(),
                    'detection_confidence' => $confidence,
                    'detection_raw_data' => [
                        'pattern' => $pattern,
                        'match' => $matchText,
                        'context' => $context,
                        'position' => $matchPos,
                    ],
                ]));

                $affaire->sources()->create([
                    'type_source' => 'wikipedia',
                    'titre' => "Wikipedia — {$eluData['prenom']} {$eluData['nom']}",
                    'url' => $sourceUrl,
                    'fiabilite' => 'basse',
                    'extrait' => Str::limit($context, 500),
                ]);

                $affaire->moderationLogs()->create([
                    'action' => 'detection',
                    'nouveau_statut' => 'detecte',
                    'commentaire' => "Détection Wikipedia NLP (confiance : {$confidence})",
                    'metadata' => ['source' => 'wikipedia_nlp', 'confidence' => $confidence, 'pattern' => $pattern],
                ]);

                $this->detected++;
                return;
            }
        }
    }

    private function extractTitre(string $context, string $match, array $eluData): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $context);
        foreach ($sentences as $sentence) {
            if (str_contains($sentence, $match)) {
                return Str::limit(trim($sentence), 497);
            }
        }
        return Str::limit("{$eluData['prenom']} {$eluData['nom']} — {$match}", 497);
    }

    private function guessTypeFromContext(string $context): string
    {
        $text = mb_strtolower($context);
        $mapping = [
            'corruption' => 'corruption',
            'détournement de fonds' => 'detournement_fonds',
            'fraude fiscal' => 'fraude_fiscale',
            'abus de biens' => 'abus_biens_sociaux',
            'prise illégale d\'intérêt' => 'prise_illegale_interet',
            'favoritisme' => 'favoritisme',
            'trafic d\'influence' => 'trafic_influence',
            'emploi fictif' => 'emploi_fictif',
            'recel' => 'recel',
            'blanchiment' => 'blanchiment',
            'harcèlement' => 'harcelement',
            'violence' => 'violence',
            'diffamation' => 'diffamation',
            'injure' => 'injure',
            'financement' => 'financement_illegal_campagne',
            'conflit d\'intérêt' => 'conflit_interets',
        ];

        foreach ($mapping as $keyword => $type) {
            if (str_contains($text, $keyword)) {
                return $type;
            }
        }
        return 'autre';
    }

    private function guessCategorieFromContext(string $context): string
    {
        $text = mb_strtolower($context);
        if (preg_match('/corrupt|détournement|fraude|abus|favoritisme|trafic|prise illégale|conflit/u', $text)) {
            return 'probite';
        }
        if (preg_match('/financement|campagne|compte/u', $text)) {
            return 'financement';
        }
        if (preg_match('/harcèl|violence|agression|menace/u', $text)) {
            return 'personne';
        }
        if (preg_match('/manquement|déclaration|probité/u', $text)) {
            return 'manquement';
        }
        return 'autre';
    }

    private function guessStatutFromContext(string $context): string
    {
        $text = mb_strtolower($context);
        if (preg_match('/condamn[ée].*définitiv/u', $text)) {
            return 'condamne_definitif';
        }
        if (preg_match('/condamn[ée].*appel/u', $text)) {
            return 'condamne_appel';
        }
        if (preg_match('/condamn[ée]/u', $text)) {
            return 'condamne_premiere_instance';
        }
        if (preg_match('/relax[ée]/u', $text)) {
            return 'relaxe';
        }
        if (preg_match('/acquitt[ée]/u', $text)) {
            return 'acquitte';
        }
        if (preg_match('/mis[e]?\s+en\s+examen/u', $text)) {
            return 'mis_en_examen';
        }
        if (preg_match('/non[- ]lieu/u', $text)) {
            return 'non_lieu';
        }
        return 'en_cours';
    }
}
