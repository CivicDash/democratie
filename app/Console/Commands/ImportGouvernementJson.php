<?php

namespace App\Console\Commands;

use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\Ministre;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportGouvernementJson extends Command
{
    protected $signature = 'import:gouvernement-json 
                            {--file= : Chemin vers le fichier JSON}
                            {--force : Forcer le réimport et supprimer les anciens}
                            {--dry-run : Mode simulation}';

    protected $description = 'Import de la composition du Gouvernement depuis un fichier JSON';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $file = $this->option('file') ?: database_path('data/gouvernement.json');

        $this->info('🏛️ Import du Gouvernement depuis JSON');
        $this->info('📄 Fichier : ' . $file);
        $this->newLine();

        // 1. Lire le fichier
        if (!file_exists($file)) {
            $this->error('❌ Fichier non trouvé : ' . $file);
            return Command::FAILURE;
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('❌ Erreur JSON : ' . json_last_error_msg());
            return Command::FAILURE;
        }

        // 2. Valider les données
        if (!isset($data['gouvernement']) || !isset($data['membres'])) {
            $this->error('❌ Structure JSON invalide. Clés requises : gouvernement, membres');
            return Command::FAILURE;
        }

        $gouv = $data['gouvernement'];
        $membres = $data['membres'];

        $this->info('📊 Gouvernement : ' . ($gouv['nom'] ?? 'Non défini'));
        $this->info('👔 Premier Ministre : ' . ($gouv['premier_ministre'] ?? 'Non défini'));
        $this->info('📅 Depuis : ' . ($gouv['date_debut'] ?? 'Non défini'));
        $this->info('👥 Membres : ' . count($membres));
        $this->newLine();

        // 3. Afficher les membres
        $this->table(
            ['#', 'Fonction', 'Nom', 'Type', 'Parti'],
            collect($membres)->map(fn($m, $i) => [
                $i + 1,
                Str::limit($m['fonction'] ?? '', 40),
                trim(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? '')),
                $m['type'] ?? 'ministre',
                $m['parti'] ?? '-',
            ])->toArray()
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('🔄 Mode simulation - Aucune modification effectuée');
            return Command::SUCCESS;
        }

        // 4. Sauvegarder
        $this->info('💾 Enregistrement en base de données...');

        // Créer/mettre à jour le gouvernement
        $gouvernement = Gouvernement::updateOrCreate(
            ['actif' => true],
            [
                'nom' => $gouv['nom'] ?? 'Gouvernement actuel',
                'slug' => Str::slug($gouv['nom'] ?? 'gouvernement-actuel'),
                'premier_ministre' => $gouv['premier_ministre'] ?? 'Non défini',
                'president' => $gouv['president'] ?? 'Emmanuel Macron',
                'date_debut' => $gouv['date_debut'] ?? now()->format('Y-m-d'),
            ]
        );

        // Désactiver les anciens gouvernements
        Gouvernement::where('id', '!=', $gouvernement->id)->update(['actif' => false]);

        if ($force) {
            // Supprimer les anciens ministres de ce gouvernement
            Ministre::where('gouvernement_id', $gouvernement->id)->delete();
        } else {
            // Désactiver les anciens ministres
            Ministre::where('gouvernement_id', $gouvernement->id)->update(['actif' => false]);
        }

        $ordre = 1;
        $stats = ['created' => 0, 'updated' => 0];

        foreach ($membres as $membre) {
            $prenom = $membre['prenom'] ?? '';
            $nom = $membre['nom'] ?? '';
            $fonction = $membre['fonction'] ?? 'Membre du gouvernement';
            $type = $membre['type'] ?? $this->detectType($fonction);

            // Créer/trouver le ministère si spécifié
            $ministereId = null;
            if (!empty($membre['ministere'])) {
                $ministere = Ministere::firstOrCreate(
                    ['nom' => $membre['ministere']],
                    [
                        'slug' => Str::slug($membre['ministere']),
                        'type' => $type === 'premier_ministre' ? 'ministere' : $type,
                        'actif' => true,
                    ]
                );
                $ministereId = $ministere->id;
            }

            // Créer/mettre à jour le ministre
            $ministre = Ministre::updateOrCreate(
                [
                    'gouvernement_id' => $gouvernement->id,
                    'prenom' => $prenom,
                    'nom' => $nom,
                ],
                [
                    'slug' => Str::slug($prenom . '-' . $nom),
                    'fonction' => $fonction,
                    'type_fonction' => $type,
                    'ministere_id' => $ministereId,
                    'parti_politique' => $membre['parti'] ?? null,
                    'photo_url' => $membre['photo_url'] ?? null,
                    'date_debut' => $gouvernement->date_debut,
                    'actif' => true,
                ]
            );

            if ($ministre->wasRecentlyCreated) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            $ordre++;
        }

        $this->newLine();
        $this->info('✅ Import terminé !');
        $this->info('   → Créés : ' . $stats['created']);
        $this->info('   → Mis à jour : ' . $stats['updated']);
        $this->info('   → Gouvernement ID : ' . $gouvernement->id);

        return Command::SUCCESS;
    }

    private function detectType(string $fonction): string
    {
        $fonctionLower = strtolower($fonction);

        if (str_contains($fonctionLower, 'premier ministre')) {
            return 'premier_ministre';
        }
        if (str_contains($fonctionLower, "secrétaire d'état") || str_contains($fonctionLower, 'secrétaire d\'état')) {
            return 'secretaire_etat';
        }
        if (str_contains($fonctionLower, 'ministre délégué') || str_contains($fonctionLower, 'ministre déléguée')) {
            return 'ministre_delegue';
        }
        return 'ministre';
    }
}
