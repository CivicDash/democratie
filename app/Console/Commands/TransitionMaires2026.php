<?php

namespace App\Console\Commands;

use App\Models\Maire;
use App\Models\MaireMandat;
use App\Models\ResultatListeMunicipale;
use App\Models\ResultatMunicipal;
use App\Models\Ville;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TransitionMaires2026 extends Command
{
    protected $signature = 'municipales:transition-maires
                            {--dry-run : Simuler sans écrire en base}
                            {--date-installation=2026-03-22 : Date d\'installation du nouveau conseil}';

    protected $description = 'Effectue la transition maires 2020-2026 → 2026-2032 à partir des résultats importés';

    private int $sortantsClotures = 0;

    private int $reelus = 0;

    private int $nouveaux = 0;

    private int $sansSuccesseur = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $dateInstallation = Carbon::parse($this->option('date-installation'));

        $this->info('Transition des maires 2020-2026 → 2026-2032');
        if ($dryRun) {
            $this->warn('Mode DRY RUN — aucune modification en base');
        }
        $this->newLine();

        $resultats = ResultatMunicipal::whereIn('statut_commune', ['elu_t1', 'elu_t2'])
            ->get()
            ->groupBy('code_commune');

        $this->info($resultats->count().' communes avec un résultat définitif');

        $bar = $this->output->createProgressBar($resultats->count());
        $bar->setFormat('verbose');
        $bar->start();

        foreach ($resultats as $codeCommune => $communeResultats) {
            try {
                if (! $dryRun) {
                    DB::transaction(fn () => $this->processCommune(
                        $codeCommune,
                        $communeResultats,
                        $dateInstallation
                    ));
                } else {
                    $this->simulateCommune($codeCommune, $communeResultats);
                }
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->error("Erreur ({$codeCommune}): {$e->getMessage()}");
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->displaySummary($dryRun);

        return self::SUCCESS;
    }

    private function processCommune(string $codeCommune, $resultats, Carbon $dateInstallation): void
    {
        $dernierResultat = $resultats->sortByDesc('tour')->first();

        $listeGagnante = ResultatListeMunicipale::where('resultat_commune_id', $dernierResultat->id)
            ->where('elu', true)
            ->orderByDesc('voix')
            ->first();

        if (! $listeGagnante) {
            $this->sansSuccesseur++;

            return;
        }

        $teteNom = $listeGagnante->tete_de_liste_nom;
        $tetePrenom = $listeGagnante->tete_de_liste_prenom;

        if (! $teteNom) {
            $teteListe = $listeGagnante->liste?->candidats()
                ->where('est_tete_de_liste', true)
                ->first();
            $teteNom = $teteListe?->nom;
            $tetePrenom = $teteListe?->prenom;
        }

        if (! $teteNom) {
            $this->sansSuccesseur++;

            return;
        }

        // Idempotence: si un maire 2026-2032 en exercice existe déjà pour cette commune, skip
        $dejaTraite = Maire::where('code_commune', $codeCommune)
            ->where('mandature', '2026-2032')
            ->where('en_exercice', true)
            ->first();

        if ($dejaTraite) {
            if ($this->output->isVerbose()) {
                $this->line("  ⏭ {$codeCommune} déjà traité ({$dejaTraite->nom_complet})");
            }

            return;
        }

        // Chercher l'ancien maire sortant (2020-2026 encore en exercice)
        $ancienMaire = Maire::where('code_commune', $codeCommune)
            ->where('mandature', '2020-2026')
            ->where('en_exercice', true)
            ->first();

        if ($ancienMaire) {
            $ancienMaire->update([
                'en_exercice' => false,
                'fin_mandat' => $dateInstallation,
            ]);

            MaireMandat::where('maire_id', $ancienMaire->id)
                ->where('est_actuel', true)
                ->update([
                    'est_actuel' => false,
                    'date_fin' => $dateInstallation,
                ]);

            $this->sortantsClotures++;
        }

        $estReelu = false;
        $maireExistant = null;

        if ($ancienMaire) {
            $estReelu = $this->isSamePerson(
                $ancienMaire->nom, $ancienMaire->prenom,
                $teteNom, $tetePrenom
            );

            if ($estReelu) {
                $maireExistant = $ancienMaire;
            }
        }

        if (! $maireExistant) {
            $maireExistant = Maire::where('code_commune', $codeCommune)
                ->where(function ($q) use ($teteNom, $tetePrenom) {
                    $q->where('nom', 'ILIKE', $teteNom)
                        ->where('prenom', 'ILIKE', $tetePrenom);
                })
                ->first();

            if ($maireExistant && $ancienMaire && $maireExistant->id === $ancienMaire->id) {
                $estReelu = true;
            }
        }

        $ville = Ville::where('code_insee', $codeCommune)->first();
        $score = $listeGagnante->pourcentage_exprimes;
        $tourElection = $dernierResultat->tour;
        $listeElectorale = $listeGagnante->liste;

        if ($estReelu && $maireExistant) {
            $maireExistant->update([
                'en_exercice' => true,
                'mandature' => '2026-2032',
                'debut_mandat' => $dateInstallation,
                'debut_fonction' => $dateInstallation,
                'fin_mandat' => null,
                'reelu' => true,
                'score_election_pct' => $score,
                'tour_election' => $tourElection,
                'liste_id' => $listeElectorale?->id,
            ]);

            $this->createOrUpdateMandat($maireExistant, $ville, $dateInstallation, $score, $tourElection);
            $this->reelus++;
        } else {
            $sexe = $listeGagnante->tete_de_liste_sexe;
            if (! $sexe && $listeGagnante->liste) {
                $teteCand = $listeGagnante->liste->candidats()
                    ->where('est_tete_de_liste', true)->first();
                $sexe = $teteCand?->sexe;
            }

            $nouveauMaire = Maire::create([
                'uid' => 'MAIRE-2026-'.$codeCommune,
                'nom' => mb_convert_case($teteNom, MB_CASE_TITLE, 'UTF-8'),
                'prenom' => mb_convert_case($tetePrenom, MB_CASE_TITLE, 'UTF-8'),
                'nom_complet' => ($sexe === 'F' ? 'Mme ' : 'M. ')
                    .mb_convert_case($tetePrenom, MB_CASE_TITLE, 'UTF-8').' '
                    .mb_convert_case($teteNom, MB_CASE_TITLE, 'UTF-8'),
                'civilite' => $sexe === 'F' ? 'Mme' : 'M.',
                'code_commune' => $codeCommune,
                'nom_commune' => $dernierResultat->nom_commune,
                'code_departement' => $dernierResultat->code_departement,
                'nom_departement' => $ville?->departement_nom ?? $dernierResultat->code_departement ?? '',
                'en_exercice' => true,
                'mandature' => '2026-2032',
                'debut_mandat' => $dateInstallation,
                'debut_fonction' => $dateInstallation,
                'predecesseur_id' => $ancienMaire?->id,
                'reelu' => false,
                'score_election_pct' => $score,
                'tour_election' => $tourElection,
                'liste_id' => $listeElectorale?->id,
                'ville_id' => $ville?->id,
                'population_commune' => $ville?->population,
            ]);

            $this->createOrUpdateMandat($nouveauMaire, $ville, $dateInstallation, $score, $tourElection);
            $maireExistant = $nouveauMaire;
            $this->nouveaux++;
        }

        if ($ville) {
            $ville->update(['maire_actuel_id' => $maireExistant->id]);
        }
    }

    private function createOrUpdateMandat(Maire $maire, ?Ville $ville, Carbon $dateInstallation, $score, int $tour): void
    {
        MaireMandat::updateOrCreate(
            [
                'maire_id' => $maire->id,
                'mandature' => '2026-2032',
            ],
            [
                'ville_id' => $ville?->id,
                'nom' => $maire->nom,
                'prenom' => $maire->prenom,
                'sexe' => $maire->civilite === 'Mme' ? 'F' : 'M',
                'date_debut' => $dateInstallation,
                'annee_election' => 2026,
                'nuance_politique' => $maire->nuance_politique,
                'score_election_pct' => $score,
                'tour_election' => $tour,
                'est_actuel' => true,
            ]
        );
    }

    private function simulateCommune(string $codeCommune, $resultats): void
    {
        $dernierResultat = $resultats->sortByDesc('tour')->first();
        $listeGagnante = ResultatListeMunicipale::where('resultat_commune_id', $dernierResultat->id)
            ->where('elu', true)
            ->orderByDesc('voix')
            ->first();

        if (! $listeGagnante) {
            $this->sansSuccesseur++;

            return;
        }

        $ancienMaire = Maire::where('code_commune', $codeCommune)
            ->where('mandature', '2020-2026')
            ->where('en_exercice', true)
            ->first();

        if ($ancienMaire) {
            $this->sortantsClotures++;
            $estReelu = $this->isSamePerson(
                $ancienMaire->nom, $ancienMaire->prenom,
                $listeGagnante->tete_de_liste_nom ?? '',
                $listeGagnante->tete_de_liste_prenom ?? ''
            );
            $estReelu ? $this->reelus++ : $this->nouveaux++;
        } else {
            $this->nouveaux++;
        }
    }

    private function isSamePerson(string $nom1, string $prenom1, string $nom2, string $prenom2): bool
    {
        return mb_strtolower(trim($nom1)) === mb_strtolower(trim($nom2))
            && mb_strtolower(trim($prenom1)) === mb_strtolower(trim($prenom2));
    }

    private function displaySummary(bool $dryRun): void
    {
        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Transition des maires terminée !");
        $this->newLine();

        $total = $this->reelus + $this->nouveaux;
        $tauxReelection = $total > 0 ? round(($this->reelus / $total) * 100, 1) : 0;

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Sortants clôturés', $this->sortantsClotures],
                ['Maires réélus', $this->reelus],
                ['Nouveaux maires', $this->nouveaux],
                ['Sans successeur identifié', $this->sansSuccesseur],
                ['Taux de réélection', "{$tauxReelection} %"],
                ['Erreurs', $this->errors],
            ]
        );
    }
}
