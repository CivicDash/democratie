<?php

namespace App\Console\Commands;

use App\Models\DeputeSenateur;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportSenateursFromCsv extends Command
{
    protected $signature = 'import:senateurs {--fresh : Vider la table des sénateurs avant l\'import}';

    protected $description = 'Importe les sénateurs depuis le fichier CSV local (public/data/elus-senateurs-sen.csv)';

    public function handle()
    {
        $this->info('🏛️ Import des sénateurs depuis fichier CSV local...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Suppression des données des sénateurs existants...');
            DeputeSenateur::where('source', 'senat')->delete();
        }

        // Chemin du fichier CSV local
        $csvPath = public_path('data/elus-senateurs-sen.csv');

        if (! file_exists($csvPath)) {
            $this->error('❌ Fichier CSV introuvable: '.$csvPath);

            return Command::FAILURE;
        }

        $this->info('📂 Lecture du fichier: '.basename($csvPath));
        $this->newLine();

        try {
            $handle = fopen($csvPath, 'r');

            if (! $handle) {
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
                // Libellé de la collectivité à statut particulier;Nom de l'élu;Prénom de l'élu;
                // Code sexe;Date de naissance;Code de la catégorie socio-professionnelle;
                // Libellé de la catégorie socio-professionnelle;Date de début du mandat

                if (count($data) < 11) {
                    $errors++;
                    $bar->advance();

                    continue;
                }

                $deptCode = trim($data[0] ?? '');
                $deptName = trim($data[1] ?? '');
                $nom = trim($data[4] ?? '');
                $prenom = trim($data[5] ?? '');
                $sexeCode = trim($data[6] ?? '');
                $dateNaissance = trim($data[7] ?? '');
                $professionCode = trim($data[8] ?? '');
                $profession = trim($data[9] ?? '');
                $dateDebutMandat = trim($data[10] ?? '');

                if (empty($nom) || empty($prenom) || empty($deptCode)) {
                    $errors++;
                    $bar->advance();

                    continue;
                }

                // Générer un UID unique basé sur nom, prénom et département
                $uid = 'SEN-'.strtoupper($deptCode).'-'.$this->slugify($nom.'-'.$prenom);

                // Pour les sénateurs, la circonscription est le département
                $circonscription = $deptCode.' - '.$deptName;

                // Convertir les dates
                $dateNaissanceParsed = $this->parseDate($dateNaissance);
                $dateDebutMandatParsed = $this->parseDate($dateDebutMandat);

                // Déterminer la civilité
                $civilite = $sexeCode === 'F' ? 'Mme' : 'M.';

                try {
                    $senateur = DeputeSenateur::updateOrCreate(
                        [
                            'source' => 'senat',
                            'uid' => $uid,
                        ],
                        [
                            'nom' => strtoupper($nom),
                            'prenom' => ucwords(strtolower($prenom)),
                            'nom_complet' => $civilite.' '.ucwords(strtolower($prenom)).' '.strtoupper($nom),
                            'civilite' => $civilite,
                            'circonscription' => $circonscription,
                            'numero_circonscription' => null, // Pas de numéro pour les sénateurs
                            'profession' => $profession,
                            'date_naissance' => $dateNaissanceParsed,
                            'legislature' => null, // Pas de législature pour le Sénat
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

                    if ($senateur->wasRecentlyCreated) {
                        $imported++;
                    } else {
                        $updated++;
                    }

                } catch (\Exception $e) {
                    $errors++;
                    if ($errors < 5) { // Afficher seulement les 5 premières erreurs
                        $this->warn("⚠️  Erreur pour {$prenom} {$nom}: ".$e->getMessage());
                    }
                }

                $bar->advance();
            }

            fclose($handle);
            $bar->finish();
            $this->newLine(2);

            $this->info('✅ Import terminé !');
            $this->info("   ✓ {$imported} sénateurs importés");
            $this->info("   ↻ {$updated} sénateurs mis à jour");
            if ($errors > 0) {
                $this->warn("   ⚠️  {$errors} lignes ignorées");
            }

            // Vérification finale
            $total = DeputeSenateur::where('source', 'senat')->count();
            $this->newLine();
            $this->info("📊 Total en base: {$total} sénateurs");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'import: '.$e->getMessage());
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
