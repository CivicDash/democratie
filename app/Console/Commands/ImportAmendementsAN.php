<?php

namespace App\Console\Commands;

use App\Models\AmendementAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportAmendementsAN extends Command
{
    protected $signature = 'import:amendements-an 
                            {--legislature=17 : Législature à importer (par défaut: 17)}
                            {--all : Importer tous les amendements (toutes législatures)}
                            {--limit= : Limite le nombre d\'amendements à importer (pour tests)}
                            {--fresh : Vide la table avant l\'import}';

    protected $description = 'Importe les amendements depuis la structure amendements/ (parsing récursif)';

    private int $imported = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;
    private int $processed = 0;

    public function handle(): int
    {
        $legislature = $this->option('legislature');
        $importAll = $this->option('all');
        
        $this->info('📝 Import des amendements AN...');
        
        if ($importAll) {
            $this->warn('⚠️  Mode --all : import de TOUS les amendements (TRÈS LONG !)');
        } else {
            $this->info("📊 Législature cible : {$legislature}");
        }

        $basePath = public_path('data/amendements');
        
        if (!is_dir($basePath)) {
            $this->error("❌ Répertoire introuvable : {$basePath}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des amendements existants...');
            AmendementAN::truncate();
        }

        // Récupération récursive de tous les fichiers JSON d'amendements
        $this->info('🔍 Recherche des fichiers amendements...');
        $files = $this->findAmendementFiles($basePath, $legislature, $importAll);
        
        $limit = $this->option('limit');
        if ($limit) {
            $files = array_slice($files, 0, (int)$limit);
            $this->warn("⚠️  Mode TEST : {$limit} amendements maximum");
        }

        $this->info("📊 " . count($files) . " fichiers trouvés");
        
        if (count($files) === 0) {
            $this->warn('⚠️  Aucun amendement trouvé');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            try {
                $this->importAmendement($file);
                $this->processed++;
                
                // Affichage intermédiaire tous les 1000 amendements
                if ($this->processed % 1000 === 0) {
                    $bar->clear();
                    $this->newLine();
                    $this->info("⏳ Traités : {$this->processed} | Importés : {$this->imported} | Erreurs : {$this->errors}");
                    $bar->display();
                }
            } catch (\Exception $e) {
                $this->errors++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary($legislature, $importAll);

        return self::SUCCESS;
    }

    private function findAmendementFiles(string $basePath, int $legislature, bool $importAll): array
    {
        $files = [];
        
        // Parcourir les dossiers DLR*
        $dossierDirs = File::directories($basePath);
        $dossierDirs = array_filter($dossierDirs, function($dir) use ($legislature, $importAll) {
            $dirname = basename($dir);
            if (!str_starts_with($dirname, 'DLR')) {
                return false;
            }
            
            // Filtrage législature
            if (!$importAll) {
                preg_match('/L(\d+)N/', $dirname, $matches);
                $dosLeg = isset($matches[1]) ? (int)$matches[1] : null;
                return $dosLeg === (int)$legislature;
            }
            
            return true;
        });

        foreach ($dossierDirs as $dossierDir) {
            // Parcourir les textes (PIONANR5L17B0263)
            $texteDirs = File::directories($dossierDir);
            
            foreach ($texteDirs as $texteDir) {
                // Les fichiers AMAN*.json sont DIRECTEMENT dans le dossier texte
                $amendementFiles = File::glob($texteDir . '/AMAN*.json');
                $files = array_merge($files, $amendementFiles);
                
                // Mais on vérifie aussi les anciennes structures avec phases/divisions
                // au cas où certains dossiers utilisent encore cette structure
                $phaseDirs = File::directories($texteDir);
                
                foreach ($phaseDirs as $phaseDir) {
                    // Si c'est un dossier de phase (P0D1, P1D1, etc.)
                    if (preg_match('/^P\d+D\d+$/', basename($phaseDir))) {
                        $amendementFiles = File::glob($phaseDir . '/AMAN*.json');
                        $files = array_merge($files, $amendementFiles);
                    }
                    
                    // Ancienne structure avec phases puis divisions séparées
                    $divisionDirs = File::directories($phaseDir);
                    foreach ($divisionDirs as $divisionDir) {
                        $amendementFiles = File::glob($divisionDir . '/AMAN*.json');
                        $files = array_merge($files, $amendementFiles);
                    }
                }
            }
        }

        return $files;
    }

    private function importAmendement(string $filePath): void
    {
        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (!isset($data['amendement'])) {
            throw new \Exception("Structure JSON invalide");
        }

        $amendement = $data['amendement'];
        $uid = $amendement['uid'] ?? null;

        if (!$uid) {
            throw new \Exception("UID manquant");
        }

        // Extraction legislature depuis l'UID
        preg_match('/L(\d+)/', $uid, $matches);
        $legislature = isset($matches[1]) ? (int)$matches[1] : null;

        // Extraction des données
        $identificationAmd = $amendement['identification'] ?? [];
        $auteur = $amendement['auteur'] ?? [];
        $pointeurFragmentTexte = $amendement['pointeurFragmentTexte'] ?? [];
        $corps = $amendement['corps'] ?? [];
        $cycleDeVie = $amendement['cycleDeVie'] ?? [];

        // Extraction auteur
        $auteurType = $auteur['tribunOuGroupe'] ?? $auteur['typeAuteur'] ?? 'Inconnu';
        $auteurActeurRef = null;
        $auteurGroupeRef = null;
        $auteurLibelle = null;

        if (isset($auteur['acteurRef'])) {
            $auteurActeurRef = $auteur['acteurRef'];
        } elseif (isset($auteur['organeRef'])) {
            $auteurGroupeRef = $auteur['organeRef'];
        }
        $auteurLibelle = $auteur['identite'] ?? $auteur['libelle'] ?? null;

        // Cosignataires
        $cosignataires = [];
        $nombreCosignataires = 0;
        if (isset($amendement['cosignataires']['cosignataire'])) {
            $cosigsData = $amendement['cosignataires']['cosignataire'];
            if (isset($cosigsData['acteurRef'])) {
                $cosigsData = [$cosigsData];
            }
            foreach ($cosigsData as $cosig) {
                if (isset($cosig['acteurRef'])) {
                    $cosignataires[] = $cosig['acteurRef'];
                }
            }
            $nombreCosignataires = count($cosignataires);
        }

        // Insert ou update
        $amendementModel = AmendementAN::updateOrCreate(
            ['uid' => $uid],
            [
                'texte_legislatif_ref' => $identificationAmd['texteVisePar'] ?? null,
                'examen_ref' => $identificationAmd['examen'] ?? null,
                'legislature' => $legislature,
                'numero_long' => $identificationAmd['numeroLong'] ?? null,
                'numero_ordre_depot' => $identificationAmd['numeroOrdreDepot'] ?? null,
                'prefixe_organe_examen' => $identificationAmd['prefixeOrganeExamen'] ?? null,
                
                // Auteur
                'auteur_type' => $auteurType,
                'auteur_acteur_ref' => $auteurActeurRef,
                'auteur_groupe_ref' => $auteurGroupeRef,
                'auteur_libelle' => $auteurLibelle,
                
                // Cosignataires
                'cosignataires_acteur_refs' => $cosignataires,
                'nombre_cosignataires' => $nombreCosignataires,
                
                // Article visé
                'article_designation' => $pointeurFragmentTexte['division']['articleDesignation'] ?? null,
                'article_designation_courte' => $pointeurFragmentTexte['division']['articleDesignationCourte'] ?? null,
                'division_titre' => $pointeurFragmentTexte['division']['titre'] ?? null,
                'division_type' => $pointeurFragmentTexte['division']['type'] ?? null,
                
                // Contenu
                'cartouche_informatif' => $corps['cartoucheInformatif'] ?? null,
                'dispositif' => $corps['dispositif'] ?? null,
                'expose' => $corps['exposeSommaire'] ?? null,
                
                // Cycle de vie
                'date_depot' => $cycleDeVie['dateDepot'] ?? null,
                'date_publication' => $cycleDeVie['datePublication'] ?? null,
                'soumis_article_40' => (bool)($cycleDeVie['soumisArticle40'] ?? false),
                'etat_code' => $cycleDeVie['etat'] ?? null,
                'etat_libelle' => $cycleDeVie['etatLibelle'] ?? null,
                'sous_etat_code' => $cycleDeVie['sousEtat'] ?? null,
                'sous_etat_libelle' => $cycleDeVie['sousEtatLibelle'] ?? null,
                'date_sort' => $cycleDeVie['dateSort'] ?? null,
                'sort_code' => $cycleDeVie['sort'] ?? null,
                'sort_libelle' => $cycleDeVie['sortLibelle'] ?? null,
            ]
        );

        if ($amendementModel->wasRecentlyCreated) {
            $this->imported++;
        } else {
            $this->updated++;
        }
    }

    private function displaySummary(int $legislature, bool $importAll): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Nouveaux amendements', $this->imported],
                ['↻ Amendements mis à jour', $this->updated],
                ['⊘ Amendements skippés', $this->skipped],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $total = AmendementAN::count();
        $adoptes = AmendementAN::adoptes()->count();
        $rejetes = AmendementAN::rejetes()->count();
        
        if (!$importAll) {
            $totalLeg = AmendementAN::legislature($legislature)->count();
            $adoptesLeg = AmendementAN::legislature($legislature)->adoptes()->count();
        }
        
        $this->newLine();
        $this->info("📊 Total en base de données : {$total} amendements");
        $this->info("   - Adoptés : {$adoptes}");
        $this->info("   - Rejetés : {$rejetes}");
        
        if (!$importAll) {
            $this->newLine();
            $this->info("📊 Législature {$legislature} : {$totalLeg} amendements");
            $this->info("   - Adoptés : {$adoptesLeg}");
        }
    }
}

