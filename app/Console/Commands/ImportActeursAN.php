<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportActeursAN extends Command
{
    protected $signature = 'import:acteurs-an 
                            {--limit= : Limite le nombre d\'acteurs à importer (pour tests)}
                            {--fresh : Vide la table avant l\'import}';

    protected $description = 'Importe les acteurs (députés) depuis les fichiers JSON de l\'Assemblée Nationale';

    private int $imported = 0;

    private int $updated = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $this->info('🏛️  Import des acteurs AN...');

        $basePath = public_path('data/acteur');

        if (! is_dir($basePath)) {
            $this->error("❌ Répertoire introuvable : {$basePath}");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des acteurs existants...');
            ActeurAN::truncate();
        }

        $files = File::glob($basePath.'/*.json');
        $total = count($files);

        $limit = $this->option('limit');
        if ($limit) {
            $files = array_slice($files, 0, (int) $limit);
            $this->warn("⚠️  Mode TEST : {$limit} acteurs maximum");
        }

        $this->info("📊 {$total} fichiers trouvés");
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            try {
                $this->importActeur($file);
            } catch (\Exception $e) {
                $this->errors++;
                $this->newLine();
                $this->warn("⚠️  Erreur : {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Résumé
        $this->displaySummary();

        return self::SUCCESS;
    }

    private function importActeur(string $filePath): void
    {
        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (! isset($data['acteur'])) {
            throw new \Exception("Structure JSON invalide dans {$filePath}");
        }

        $acteur = $data['acteur'];
        $uid = $acteur['uid']['#text'] ?? null;

        if (! $uid) {
            throw new \Exception("UID manquant dans {$filePath}");
        }

        // Extraction des données
        $etatCivil = $acteur['etatCivil'] ?? [];
        $ident = $etatCivil['ident'] ?? [];
        $infoNaissance = $etatCivil['infoNaissance'] ?? [];
        $profession = $acteur['profession'] ?? [];
        $socProc = $profession['socProcINSEE'] ?? [];

        // Préparation des adresses (JSON)
        $adresses = $this->extractAdresses($acteur['adresses'] ?? []);

        // Nettoyer les champs qui peuvent contenir "?" ou des objets
        $urlHatvp = $acteur['uri_hatvp'] ?? null;
        if (is_array($urlHatvp) || $urlHatvp === '?') {
            $urlHatvp = null;
        }

        $depNais = $infoNaissance['depNais'] ?? null;
        if (is_array($depNais) || $depNais === '?') {
            $depNais = null;
        }

        $catSocPro = $socProc['catSocPro'] ?? null;
        if (is_array($catSocPro) || $catSocPro === '?') {
            $catSocPro = null;
        }

        // Tronquer le trigramme à 3 caractères max
        $trigramme = $ident['trigramme'] ?? null;
        if ($trigramme && strlen($trigramme) > 3) {
            $trigramme = substr($trigramme, 0, 3);
        }

        // Insert ou update
        $acteurModel = ActeurAN::updateOrCreate(
            ['uid' => $uid],
            [
                'civilite' => $ident['civ'] ?? null,
                'prenom' => $ident['prenom'] ?? null,
                'nom' => $ident['nom'] ?? null,
                'trigramme' => $trigramme,
                'date_naissance' => $infoNaissance['dateNais'] ?? null,
                'ville_naissance' => $infoNaissance['villeNais'] ?? null,
                'departement_naissance' => $depNais,
                'pays_naissance' => $infoNaissance['paysNais'] ?? null,
                'profession' => $profession['libelleCourant'] ?? null,
                'categorie_socio_pro' => $catSocPro,
                'url_hatvp' => $urlHatvp,
                'adresses' => $adresses,
            ]
        );

        if ($acteurModel->wasRecentlyCreated) {
            $this->imported++;
        } else {
            $this->updated++;
        }
    }

    private function extractAdresses(array $adressesData): array
    {
        $adresses = [];

        if (! isset($adressesData['adresse'])) {
            return $adresses;
        }

        $adressesList = $adressesData['adresse'];

        // Si une seule adresse, la transformer en tableau
        if (isset($adressesList['uid'])) {
            $adressesList = [$adressesList];
        }

        foreach ($adressesList as $adresse) {
            $type = $adresse['typeLibelle'] ?? $adresse['type'] ?? 'Inconnu';

            $adresseFormatted = [
                'uid' => $adresse['uid'] ?? null,
                'type' => $type,
                'poids' => $adresse['poids'] ?? null,
            ];

            // Selon le type d'adresse
            if (isset($adresse['@xsi:type'])) {
                $xsiType = $adresse['@xsi:type'];

                if ($xsiType === 'AdressePostale_Type') {
                    $adresseFormatted['intitule'] = $adresse['intitule'] ?? null;
                    $adresseFormatted['numero_rue'] = $adresse['numeroRue'] ?? null;
                    $adresseFormatted['nom_rue'] = $adresse['nomRue'] ?? null;
                    $adresseFormatted['complement'] = $adresse['complementAdresse'] ?? null;
                    $adresseFormatted['code_postal'] = $adresse['codePostal'] ?? null;
                    $adresseFormatted['ville'] = $adresse['ville'] ?? null;
                } elseif (in_array($xsiType, ['AdresseMail_Type', 'AdresseTelephonique_Type', 'AdresseSiteWeb_Type'])) {
                    $adresseFormatted['valeur'] = $adresse['valElec'] ?? null;
                }
            }

            $adresses[] = $adresseFormatted;
        }

        return $adresses;
    }

    private function displaySummary(): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Nouveaux acteurs', $this->imported],
                ['↻ Acteurs mis à jour', $this->updated],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $total = ActeurAN::count();
        $deputes = ActeurAN::deputes()->count();

        $this->newLine();
        $this->info("📊 Total en base de données : {$total} acteurs");
        $this->info("👤 Députés actifs (avec mandat ASSEMBLEE) : {$deputes}");
    }
}
