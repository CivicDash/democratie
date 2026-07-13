<?php

namespace Database\Seeders;

use App\Models\ProgrammeTheme;
use Illuminate\Database\Seeder;

/**
 * Référentiel NEUTRE des 15 thèmes du comparateur présidentielle.
 * La grille ne dérive d'aucun programme de candidat (plan §4) : elle s'appuie
 * sur des nomenclatures institutionnelles (missions LOLF, COFOG, périmètres
 * ministériels, sections CESE), documentées dans `sources_taxonomie`.
 * Idempotent (updateOrCreate par slug).
 */
class ProgrammeThemesSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            ['pouvoir-achat', 'Pouvoir d\'achat', 'lucide-wallet', 'Revenus, prix, salaires, prestations.'],
            ['travail-retraites', 'Travail & retraites', 'lucide-briefcase', 'Emploi, droit du travail, système de retraites.'],
            ['sante', 'Santé', 'lucide-heart-pulse', 'Système de soins, hôpital, accès aux soins.'],
            ['education', 'Éducation', 'lucide-graduation-cap', 'École, enseignement supérieur, recherche.'],
            ['securite-justice', 'Sécurité & justice', 'lucide-scale', 'Police, justice, prisons, délinquance.'],
            ['immigration', 'Immigration', 'lucide-globe', 'Politique migratoire, asile, intégration.'],
            ['ecologie-energie', 'Écologie & énergie', 'lucide-leaf', 'Climat, énergie, biodiversité, transition.'],
            ['logement', 'Logement', 'lucide-home', 'Accès au logement, construction, urbanisme.'],
            ['institutions', 'Institutions & démocratie', 'lucide-landmark', 'Constitution, référendum, décentralisation.'],
            ['europe-international', 'Europe & international', 'lucide-flag', 'UE, diplomatie, relations internationales.'],
            ['defense', 'Défense', 'lucide-shield', 'Armées, dissuasion, industrie de défense.'],
            ['fiscalite-budget', 'Fiscalité & budget', 'lucide-calculator', 'Impôts, dépenses publiques, dette, déficit.'],
            ['numerique', 'Numérique', 'lucide-cpu', 'IA, souveraineté numérique, données.'],
            ['agriculture', 'Agriculture', 'lucide-wheat', 'Agriculture, alimentation, ruralité, pêche.'],
            ['culture', 'Culture', 'lucide-palette', 'Culture, patrimoine, médias, audiovisuel.'],
        ];

        $sources = 'Missions du budget de l\'État (LOLF) ; classification COFOG (Eurostat) '
            .'des fonctions des administrations publiques ; périmètres ministériels ; sections du CESE.';

        foreach ($themes as $ordre => [$slug, $nom, $icone, $description]) {
            ProgrammeTheme::updateOrCreate(
                ['slug' => $slug],
                [
                    'nom' => $nom,
                    'icone' => $icone,
                    'description' => $description,
                    'sources_taxonomie' => $sources,
                    'ordre' => $ordre + 1,
                    'actif' => true,
                ]
            );
        }
    }
}
