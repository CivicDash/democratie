<?php

namespace App\Console\Commands;

use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\Ministre;
use App\Models\Remaniement;
use Illuminate\Console\Command;

class ImportGouvernement extends Command
{
    protected $signature = 'import:gouvernement 
                            {--force : Forcer le réimport}';

    protected $description = 'Import de la composition du Gouvernement';

    // Gouvernement actuel (Barnier - Décembre 2024 / Bayrou - Décembre 2024)
    private const GOUVERNEMENT_ACTUEL = [
        'nom' => 'Gouvernement Bayrou',
        'premier_ministre' => 'François Bayrou',
        'president' => 'Emmanuel Macron',
        'date_debut' => '2024-12-13',
        'numero' => 4, // 4ème gouvernement sous Macron
        'legislature' => '17',
    ];

    private const MINISTERES = [
        // Ministères régaliens
        ['nom' => 'Ministère de l\'Intérieur', 'sigle' => 'MINT', 'type' => 'ministere', 'ordre' => 1],
        ['nom' => 'Ministère des Armées', 'sigle' => 'MINARM', 'type' => 'ministere', 'ordre' => 2],
        ['nom' => 'Ministère de la Justice', 'sigle' => 'MJ', 'type' => 'ministere', 'ordre' => 3],
        ['nom' => 'Ministère de l\'Europe et des Affaires étrangères', 'sigle' => 'MEAE', 'type' => 'ministere', 'ordre' => 4],
        ['nom' => 'Ministère de l\'Économie, des Finances et de l\'Industrie', 'sigle' => 'MEFSIN', 'type' => 'ministere', 'ordre' => 5],

        // Ministères sociaux
        ['nom' => 'Ministère du Travail, de la Santé, des Solidarités et des Familles', 'sigle' => 'MTSSF', 'type' => 'ministere', 'ordre' => 6],
        ['nom' => 'Ministère de l\'Éducation nationale', 'sigle' => 'MEN', 'type' => 'ministere', 'ordre' => 7],
        ['nom' => 'Ministère de l\'Enseignement supérieur et de la Recherche', 'sigle' => 'MESR', 'type' => 'ministere', 'ordre' => 8],

        // Ministères thématiques
        ['nom' => 'Ministère de la Transition écologique, de la Biodiversité, de la Forêt, de la Mer et de la Pêche', 'sigle' => 'MTE', 'type' => 'ministere', 'ordre' => 9],
        ['nom' => 'Ministère de l\'Agriculture et de la Souveraineté alimentaire', 'sigle' => 'MAA', 'type' => 'ministere', 'ordre' => 10],
        ['nom' => 'Ministère de la Culture', 'sigle' => 'MC', 'type' => 'ministere', 'ordre' => 11],
        ['nom' => 'Ministère des Sports, de la Jeunesse et de la Vie associative', 'sigle' => 'MSJVA', 'type' => 'ministere', 'ordre' => 12],
        ['nom' => 'Ministère de l\'Aménagement du territoire et de la Décentralisation', 'sigle' => 'MATD', 'type' => 'ministere', 'ordre' => 13],
        ['nom' => 'Ministère des Outre-mer', 'sigle' => 'MOM', 'type' => 'ministere', 'ordre' => 14],
        ['nom' => 'Ministère de la Fonction publique', 'sigle' => 'MFP', 'type' => 'ministere', 'ordre' => 15],
        ['nom' => 'Ministère du Logement', 'sigle' => 'ML', 'type' => 'ministere', 'ordre' => 16],
    ];

    private const MINISTRES = [
        // Premier ministre
        ['prenom' => 'François', 'nom' => 'Bayrou', 'fonction' => 'Premier ministre', 'type_fonction' => 'premier_ministre', 'ministere' => null, 'parti' => 'MoDem', 'photo' => 'https://www.gouvernement.fr/sites/default/files/styles/minister_portrait/public/2024-12/bayrou.jpg'],

        // Ministres
        ['prenom' => 'Bruno', 'nom' => 'Retailleau', 'fonction' => 'Ministre de l\'Intérieur', 'type_fonction' => 'ministre', 'ministere' => 'MINT', 'parti' => 'LR'],
        ['prenom' => 'Sébastien', 'nom' => 'Lecornu', 'fonction' => 'Ministre des Armées', 'type_fonction' => 'ministre', 'ministere' => 'MINARM', 'parti' => 'Horizons'],
        ['prenom' => 'Gérald', 'nom' => 'Darmanin', 'fonction' => 'Garde des Sceaux, ministre de la Justice', 'type_fonction' => 'ministre', 'ministere' => 'MJ', 'parti' => 'Renaissance'],
        ['prenom' => 'Jean-Noël', 'nom' => 'Barrot', 'fonction' => 'Ministre de l\'Europe et des Affaires étrangères', 'type_fonction' => 'ministre', 'ministere' => 'MEAE', 'parti' => 'MoDem'],
        ['prenom' => 'Éric', 'nom' => 'Lombard', 'fonction' => 'Ministre de l\'Économie, des Finances et de l\'Industrie', 'type_fonction' => 'ministre', 'ministere' => 'MEFSIN', 'parti' => 'Sans étiquette'],
        ['prenom' => 'Catherine', 'nom' => 'Vautrin', 'fonction' => 'Ministre du Travail, de la Santé, des Solidarités et des Familles', 'type_fonction' => 'ministre', 'ministere' => 'MTSSF', 'parti' => 'Horizons'],
        ['prenom' => 'Élisabeth', 'nom' => 'Borne', 'fonction' => 'Ministre de l\'Éducation nationale', 'type_fonction' => 'ministre', 'ministere' => 'MEN', 'parti' => 'Renaissance'],
        ['prenom' => 'Patrick', 'nom' => 'Hetzel', 'fonction' => 'Ministre de l\'Enseignement supérieur et de la Recherche', 'type_fonction' => 'ministre', 'ministere' => 'MESR', 'parti' => 'LR'],
        ['prenom' => 'Agnès', 'nom' => 'Pannier-Runacher', 'fonction' => 'Ministre de la Transition écologique', 'type_fonction' => 'ministre', 'ministere' => 'MTE', 'parti' => 'Renaissance'],
        ['prenom' => 'Annie', 'nom' => 'Genevard', 'fonction' => 'Ministre de l\'Agriculture', 'type_fonction' => 'ministre', 'ministere' => 'MAA', 'parti' => 'LR'],
        ['prenom' => 'Rachida', 'nom' => 'Dati', 'fonction' => 'Ministre de la Culture', 'type_fonction' => 'ministre', 'ministere' => 'MC', 'parti' => 'LR'],
        ['prenom' => 'Gil', 'nom' => 'Avérous', 'fonction' => 'Ministre des Sports', 'type_fonction' => 'ministre', 'ministere' => 'MSJVA', 'parti' => 'DVG'],
        ['prenom' => 'François', 'nom' => 'Rebsamen', 'fonction' => 'Ministre de l\'Aménagement du territoire', 'type_fonction' => 'ministre', 'ministere' => 'MATD', 'parti' => 'PS'],
        ['prenom' => 'Manuel', 'nom' => 'Valls', 'fonction' => 'Ministre des Outre-mer', 'type_fonction' => 'ministre', 'ministere' => 'MOM', 'parti' => 'DVG'],
        ['prenom' => 'Laurent', 'nom' => 'Marcangeli', 'fonction' => 'Ministre de la Fonction publique', 'type_fonction' => 'ministre', 'ministere' => 'MFP', 'parti' => 'Horizons'],
        ['prenom' => 'Valérie', 'nom' => 'Létard', 'fonction' => 'Ministre du Logement', 'type_fonction' => 'ministre', 'ministere' => 'ML', 'parti' => 'UDI'],
    ];

    public function handle(): int
    {
        $force = $this->option('force');

        $this->info('🏛️ Import de la composition du Gouvernement');

        // 1. Créer/Mettre à jour le gouvernement
        $gouvernement = $this->createGouvernement($force);

        // 2. Créer les ministères
        $this->createMinisteres($gouvernement, $force);

        // 3. Créer les ministres
        $this->createMinistres($gouvernement);

        // 4. Créer l'événement de formation
        $this->createRemaniement($gouvernement);

        $this->newLine();
        $this->info('✅ Import du gouvernement terminé !');
        $this->displayStats($gouvernement);

        return Command::SUCCESS;
    }

    private function createGouvernement(bool $force): Gouvernement
    {
        $this->info('📋 Création/Mise à jour du gouvernement...');

        // Désactiver les gouvernements précédents
        Gouvernement::where('actif', true)->update(['actif' => false]);

        $data = self::GOUVERNEMENT_ACTUEL;

        $gouvernement = Gouvernement::updateOrCreate(
            ['nom' => $data['nom']],
            [
                'premier_ministre' => $data['premier_ministre'],
                'president' => $data['president'],
                'date_debut' => $data['date_debut'],
                'numero' => $data['numero'],
                'legislature' => $data['legislature'],
                'actif' => true,
            ]
        );

        $this->info("   → {$gouvernement->nom} créé/mis à jour");

        return $gouvernement;
    }

    private function createMinisteres(Gouvernement $gouvernement, bool $force): void
    {
        $this->info('🏢 Création des ministères...');

        if ($force) {
            Ministere::where('gouvernement_id', $gouvernement->id)->delete();
        }

        $bar = $this->output->createProgressBar(count(self::MINISTERES));

        foreach (self::MINISTERES as $data) {
            Ministere::updateOrCreate(
                [
                    'gouvernement_id' => $gouvernement->id,
                    'sigle' => $data['sigle'],
                ],
                [
                    'nom' => $data['nom'],
                    'type' => $data['type'],
                    'ordre' => $data['ordre'],
                    'couleur' => Ministere::getCouleurDefaut($data['nom']),
                    'actif' => true,
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function createMinistres(Gouvernement $gouvernement): void
    {
        $this->info('👔 Création des ministres...');

        // Désactiver les anciens ministres
        Ministre::where('gouvernement_id', $gouvernement->id)->update(['actif' => false]);

        $bar = $this->output->createProgressBar(count(self::MINISTRES));

        foreach (self::MINISTRES as $data) {
            // Trouver le ministère
            $ministere = null;
            if ($data['ministere']) {
                $ministere = Ministere::where('gouvernement_id', $gouvernement->id)
                    ->where('sigle', $data['ministere'])
                    ->first();
            }

            Ministre::updateOrCreate(
                [
                    'gouvernement_id' => $gouvernement->id,
                    'prenom' => $data['prenom'],
                    'nom' => $data['nom'],
                ],
                [
                    'ministere_id' => $ministere?->id,
                    'fonction' => $data['fonction'],
                    'type_fonction' => $data['type_fonction'],
                    'date_debut' => self::GOUVERNEMENT_ACTUEL['date_debut'],
                    'actif' => true,
                    'parti_politique' => $data['parti'] ?? null,
                    'photo_url' => $data['photo'] ?? null,
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function createRemaniement(Gouvernement $gouvernement): void
    {
        Remaniement::updateOrCreate(
            [
                'gouvernement_id' => $gouvernement->id,
                'date' => self::GOUVERNEMENT_ACTUEL['date_debut'],
            ],
            [
                'type' => 'formation',
                'description' => 'Formation du '.$gouvernement->nom,
                'nb_entrants' => count(self::MINISTRES),
            ]
        );
    }

    private function displayStats(Gouvernement $gouvernement): void
    {
        $this->newLine();
        $this->table(
            ['Indicateur', 'Valeur'],
            [
                ['Gouvernement', $gouvernement->nom],
                ['Premier ministre', $gouvernement->premier_ministre],
                ['Date de formation', $gouvernement->date_debut->format('d/m/Y')],
                ['Ministères', Ministere::where('gouvernement_id', $gouvernement->id)->count()],
                ['Ministres', Ministre::where('gouvernement_id', $gouvernement->id)->where('actif', true)->count()],
            ]
        );
    }
}
