<?php

namespace App\Console\Commands;

use App\Models\DeputeSenateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportDeputesFromCsv extends Command
{
    protected $signature = 'import:deputes {--fresh : Vider la table des députés avant l\'import}';
    protected $description = 'Importe les députés depuis le fichier CSV local (public/data/elus-deputes-dep.csv)';

    public function handle()
    {
        $this->info('🏛️ Import des députés depuis fichier CSV local...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Suppression des données des députés existants...');
            DeputeSenateur::where('source', 'assemblee')->delete();
        }

        // Chemin du fichier CSV local
        $csvPath = public_path('data/elus-deputes-dep.csv');
        
        if (!file_exists($csvPath)) {
            $this->error('❌ Fichier CSV introuvable: ' . $csvPath);
            return Command::FAILURE;
        }

        $this->info('📂 Lecture du fichier: ' . basename($csvPath));
        $this->newLine();

        try {
            $handle = fopen($csvPath, 'r');
            
            if (!$handle) {
                $this->error('❌ Impossible d\'ouvrir le fichier CSV');
                return Command::FAILURE;
            }

            // Lire l'en-tête
            $header = fgetcsv($handle, 2000, ';');
            
            // Compter les lignes pour la barre de progression
            $lineCount = count(file($csvPath)) - 1;
            
            $this->info("📊 {$lineCount} lignes à traiter");
            $bar = $this->output->createProgressBar($lineCount);
            $bar->setFormat('verbose');
            
            $imported = 0;
            $updated = 0;
            $errors = 0;

            while (($data = fgetcsv($handle, 2000, ';')) !== false) {
                // Format CSV:
                // Code du département;Libellé du département;Code de la collectivité à statut particulier;
                // Libellé de la collectivité à statut particulier;Code de la circonscription législative;
                // Libellé de la circonscription législative;Nom de l'élu;Prénom de l'élu;Code sexe;
                // Date de naissance;Code de la catégorie socio-professionnelle;
                // Libellé de la catégorie socio-professionnelle;Date de début du mandat
                
                if (count($data) < 13) {
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $deptCode = trim($data[0] ?? '');
                $deptName = trim($data[1] ?? '');
                $circonscriptionCode = trim($data[4] ?? '');
                $circonscriptionLabel = trim($data[5] ?? '');
                $nom = trim($data[6] ?? '');
                $prenom = trim($data[7] ?? '');
                $sexeCode = trim($data[8] ?? '');
                $dateNaissance = trim($data[9] ?? '');
                $professionCode = trim($data[10] ?? '');
                $profession = trim($data[11] ?? '');
                $dateDebutMandat = trim($data[12] ?? '');
                
                if (empty($nom) || empty($prenom) || empty($deptCode)) {
                    $errors++;
                    $bar->advance();
                    continue;
                }

                // Générer un UID unique basé sur nom, prénom et circonscription
                $uid = 'DEP-' . strtoupper($deptCode) . '-' . $this->slugify($nom . '-' . $prenom);
                
                // Formater la circonscription (ex: "75-01")
                $circonscription = $deptCode . '-' . substr($circonscriptionCode, -2);
                
                // Convertir les dates
                $dateNaissanceParsed = $this->parseDate($dateNaissance);
                $dateDebutMandatParsed = $this->parseDate($dateDebutMandat);
                
                // Déterminer la civilité
                $civilite = $sexeCode === 'F' ? 'Mme' : 'M.';

                try {
                    $depute = DeputeSenateur::updateOrCreate(
                        [
                            'source' => 'assemblee',
                            'uid' => $uid,
                        ],
                        [
                            'nom' => strtoupper($nom),
                            'prenom' => ucwords(strtolower($prenom)),
                            'nom_complet' => $civilite . ' ' . ucwords(strtolower($prenom)) . ' ' . strtoupper($nom),
                            'civilite' => $civilite,
                            'circonscription' => $circonscription,
                            'numero_circonscription' => substr($circonscriptionCode, -2),
                            'profession' => $profession,
                            'date_naissance' => $dateNaissanceParsed,
                            'legislature' => 17, // Législature actuelle
                            'debut_mandat' => $dateDebutMandatParsed,
                            'fin_mandat' => null, // Mandat en cours
                            'en_exercice' => true,
                            'groupe_politique' => null, // À compléter via API si nécessaire
                            'groupe_sigle' => null,
                            'photo_url' => null,
                            'url_profil' => null,
                            'fonctions' => null,
                            'commissions' => null,
                            'nb_propositions' => 0,
                            'nb_amendements' => 0,
                            'taux_presence' => null,
                        ]
                    );

                    if ($depute->wasRecentlyCreated) {
                        $imported++;
                    } else {
                        $updated++;
                    }

                } catch (\Exception $e) {
                    $errors++;
                    if ($errors < 5) { // Afficher seulement les 5 premières erreurs
                        $this->warn("⚠️  Erreur pour {$prenom} {$nom}: " . $e->getMessage());
                    }
                }

                $bar->advance();
            }

            fclose($handle);
            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Import terminé !");
            $this->info("   ✓ {$imported} députés importés");
            $this->info("   ↻ {$updated} députés mis à jour");
            if ($errors > 0) {
                $this->warn("   ⚠️  {$errors} lignes ignorées");
            }
            
            // Vérification finale
            $total = DeputeSenateur::where('source', 'assemblee')->count();
            $this->newLine();
            $this->info("📊 Total en base: {$total} députés");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'import: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Parse une date au format DD/MM/YYYY
     */
    private function parseDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Format: DD/MM/YYYY
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
                return Carbon::createFromFormat('d/m/Y', $date);
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Slugify une chaîne pour l'UID
     */
    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        return $text;
    }
}

