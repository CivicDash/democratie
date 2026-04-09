<?php

namespace App\Console\Commands;

use App\Models\ScrutinAN;
use App\Models\VoteIndividuelAN;
use Illuminate\Console\Command;

class ExtractVotesIndividuelsAN extends Command
{
    protected $signature = 'extract:votes-individuels-an 
                            {--legislature=17 : Législature à traiter (par défaut: 17)}
                            {--all : Traiter tous les scrutins (toutes législatures)}
                            {--limit= : Limite le nombre de scrutins traités (pour tests)}
                            {--fresh : Vide la table avant l\'extraction}';

    protected $description = 'Extrait les votes individuels depuis scrutins_an.ventilation_votes et les dénormalise dans votes_individuels_an';

    private int $imported = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $legislature = $this->option('legislature');
        $extractAll = $this->option('all');

        $this->info('🗳️  Extraction des votes individuels...');

        if ($extractAll) {
            $this->warn('⚠️  Mode --all : extraction depuis TOUS les scrutins');
        } else {
            $this->info("📊 Législature cible : {$legislature}");
        }

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des votes existants...');
            VoteIndividuelAN::truncate();
        }

        // Récupération des scrutins
        $query = ScrutinAN::query();

        if (! $extractAll) {
            $query->legislature($legislature);
        }

        $limit = $this->option('limit');
        if ($limit) {
            $query->limit((int) $limit);
            $this->warn("⚠️  Mode TEST : {$limit} scrutins maximum");
        }

        $scrutins = $query->get();

        if ($scrutins->isEmpty()) {
            $this->error('❌ Aucun scrutin trouvé. Lancer d\'abord : import:scrutins-an');

            return self::FAILURE;
        }

        $this->info("📊 {$scrutins->count()} scrutins à traiter");
        $bar = $this->output->createProgressBar($scrutins->count());
        $bar->start();

        foreach ($scrutins as $scrutin) {
            try {
                $this->extractVotesScrutin($scrutin);
            } catch (\Exception $e) {
                $this->errors++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary($legislature, $extractAll);

        return self::SUCCESS;
    }

    private function extractVotesScrutin(ScrutinAN $scrutin): void
    {
        $ventilation = $scrutin->ventilation_votes;

        if (! $ventilation || ! isset($ventilation['organe'])) {
            return;
        }

        $organes = $ventilation['organe'];

        // Si un seul organe, transformer en tableau
        if (isset($organes['organeRef'])) {
            $organes = [$organes];
        }

        foreach ($organes as $organe) {
            $this->extractVotesOrgane($scrutin, $organe);
        }
    }

    private function extractVotesOrgane(ScrutinAN $scrutin, array $organe): void
    {
        $organeRef = $organe['organeRef'] ?? null;

        // La structure est: organe.groupes.groupe[] (tableau)
        $groupesData = $organe['groupes'] ?? [];
        $groupes = $groupesData['groupe'] ?? [];

        // Si un seul groupe, transformer en tableau
        if (isset($groupes['organeRef'])) {
            $groupes = [$groupes];
        }

        foreach ($groupes as $groupe) {
            $this->extractVotesGroupe($scrutin, $organeRef, $groupe);
        }
    }

    private function extractVotesGroupe(ScrutinAN $scrutin, ?string $organeRef, array $groupe): void
    {
        $groupeRef = $groupe['organeRef'] ?? null;
        $positionGroupe = $groupe['vote']['positionMajoritaire'] ?? null;

        // Parcourir les différentes positions (PLURIEL dans le JSON !)
        $positionsMap = [
            'pours' => 'pour',
            'contres' => 'contre',
            'abstentions' => 'abstention',
            'nonVotants' => 'non_votant',
        ];

        foreach ($positionsMap as $jsonKey => $dbPosition) {
            if (! isset($groupe['vote']['decompteNominatif'][$jsonKey]['votant'])) {
                continue;
            }

            $votants = $groupe['vote']['decompteNominatif'][$jsonKey]['votant'];

            // Si un seul votant, transformer en tableau
            if (isset($votants['acteurRef'])) {
                $votants = [$votants];
            }

            foreach ($votants as $votant) {
                $this->createVoteIndividuel(
                    $scrutin,
                    $votant,
                    $groupeRef,
                    $dbPosition,
                    $positionGroupe
                );
            }
        }
    }

    private function createVoteIndividuel(
        ScrutinAN $scrutin,
        array $votant,
        ?string $groupeRef,
        string $position,
        ?string $positionGroupe
    ): void {
        $acteurRef = $votant['acteurRef'] ?? null;

        if (! $acteurRef) {
            return;
        }

        try {
            VoteIndividuelAN::updateOrCreate(
                [
                    'scrutin_ref' => $scrutin->uid,
                    'acteur_ref' => $acteurRef,
                ],
                [
                    'mandat_ref' => $votant['mandatRef'] ?? null,
                    'groupe_ref' => $groupeRef,
                    'position' => $position,
                    'position_groupe' => $positionGroupe,
                    'numero_place' => $votant['numeroPlace'] ?? null,
                    'par_delegation' => (bool) ($votant['parDelegation'] ?? false),
                    'cause_non_vote' => $votant['causeRef'] ?? null,
                ]
            );

            $this->imported++;
        } catch (\Exception $e) {
            $this->errors++;
        }
    }

    private function displaySummary(int $legislature, bool $extractAll): void
    {
        $this->info('✅ Extraction terminée !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Votes individuels créés', $this->imported],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $total = VoteIndividuelAN::count();
        $pour = VoteIndividuelAN::pour()->count();
        $contre = VoteIndividuelAN::contre()->count();
        $abstention = VoteIndividuelAN::abstention()->count();
        $nonVotants = VoteIndividuelAN::nonVotant()->count();

        $this->newLine();
        $this->info("📊 Total en base de données : {$total} votes individuels");
        $this->info("   - Pour : {$pour}");
        $this->info("   - Contre : {$contre}");
        $this->info("   - Abstention : {$abstention}");
        $this->info("   - Non votants : {$nonVotants}");
    }
}
