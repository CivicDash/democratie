<?php

namespace Database\Seeders;

use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Amorce des candidats présidentielle 2027 (données seed_candidat2027.md).
 * TOUT entre en statut `detecte` / `affiche_publiquement = false` :
 * chaque ligne doit être vérifiée humainement avant publication.
 * Idempotent : firstOrCreate par slug de personne + updateOrCreate de la candidature.
 */
class CandidatsPresidentielle2027Seeder extends Seeder
{
    public function run(): void
    {
        $candidats = [
            [
                'slug' => 'edouard-philippe', 'civilite' => 'M.', 'prenom' => 'Édouard', 'nom' => 'Philippe',
                'parti' => 'Horizons', 'nuance' => 'CEN', 'date' => '2024-09-01',
                'couleur' => '#2563eb', 'site' => 'https://edouardphilippe.fr',
            ],
            [
                'slug' => 'gabriel-attal', 'civilite' => 'M.', 'prenom' => 'Gabriel', 'nom' => 'Attal',
                'parti' => 'Renaissance', 'nuance' => 'CEN', 'date' => '2026-05-22',
                'couleur' => '#f59e0b', 'site' => 'https://attalpresident.fr',
            ],
            [
                'slug' => 'jean-luc-melenchon', 'civilite' => 'M.', 'prenom' => 'Jean-Luc', 'nom' => 'Mélenchon',
                'parti' => 'La France insoumise', 'nuance' => 'GAU', 'date' => '2026-05-03',
                'couleur' => '#c0392b', 'site' => 'https://melenchon2027.fr',
            ],
        ];

        foreach ($candidats as $ordre => $c) {
            $personne = PersonnePolitique::firstOrCreate(
                ['slug' => $c['slug']],
                [
                    'civilite' => $c['civilite'],
                    'prenom' => $c['prenom'],
                    'nom' => $c['nom'],
                    'parti_politique' => $c['parti'],
                    'nuance_politique' => $c['nuance'],
                ]
            );

            CandidatPresidentielle::updateOrCreate(
                ['personne_politique_id' => $personne->id, 'election' => '2027'],
                [
                    'uuid' => (string) Str::uuid(),
                    'statut_candidature' => 'declare',
                    'date_declaration' => $c['date'],
                    'parti_soutien' => $c['parti'],
                    'nuance_politique' => $c['nuance'],
                    'site_campagne_url' => $c['site'],
                    'couleur_hex' => $c['couleur'],
                    // Non publié tant que non vérifié humainement.
                    'statut_validation' => 'detecte',
                    'affiche_publiquement' => false,
                    'ordre_affichage' => $ordre + 1,
                    'source_detection' => 'seed',
                ]
            );
        }
    }
}
