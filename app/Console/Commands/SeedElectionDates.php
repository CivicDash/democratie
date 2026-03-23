<?php

namespace App\Console\Commands;

use App\Models\EvenementLegislatif;
use Illuminate\Console\Command;

/**
 * Ajoute les dates des élections dans le calendrier législatif
 */
class SeedElectionDates extends Command
{
    protected $signature = 'seed:election-dates {--force : Recréer les événements existants}';
    protected $description = 'Ajoute les dates des élections 2026-2027 dans le calendrier';

    public function handle(): int
    {
        $this->info('📅 Ajout des dates électorales au calendrier...');

        $elections = [
            // Municipales 2026
            [
                'titre' => 'Élections Municipales 2026 - 1er tour',
                'date_debut' => '2026-03-15',
                'date_fin' => '2026-03-15',
                'type' => 'election',
                'lieu' => 'France',
                'description' => 'Premier tour des élections municipales. Les électeurs sont appelés à voter pour élire leurs conseillers municipaux qui désigneront ensuite le maire.',
                'icon' => '🗳️',
                'color' => '#8B5CF6', // Violet
                'url_source' => 'https://www.service-public.fr/particuliers/vosdroits/F1939',
                'source' => 'calendrier-electoral',
                'uid' => 'municipales-2026-t1',
            ],
            [
                'titre' => 'Élections Municipales 2026 - 2nd tour',
                'date_debut' => '2026-03-22',
                'date_fin' => '2026-03-22',
                'type' => 'election',
                'lieu' => 'France',
                'description' => 'Second tour des élections municipales dans les communes où aucune liste n\'a obtenu la majorité absolue au premier tour.',
                'icon' => '🗳️',
                'color' => '#8B5CF6',
                'url_source' => 'https://www.service-public.fr/particuliers/vosdroits/F1939',
                'source' => 'calendrier-electoral',
                'uid' => 'municipales-2026-t2',
            ],
            [
                'titre' => 'Date limite de dépôt des candidatures municipales',
                'date_debut' => '2026-02-27',
                'date_fin' => '2026-02-27',
                'type' => 'echeance',
                'lieu' => 'Préfectures',
                'description' => 'Date limite pour déposer les candidatures aux élections municipales en préfecture (18h00).',
                'icon' => '📋',
                'color' => '#F59E0B', // Orange
                'url_source' => 'https://www.service-public.fr/particuliers/vosdroits/F1939',
                'source' => 'calendrier-electoral',
                'uid' => 'municipales-2026-depot',
            ],
            [
                'titre' => 'Début de la campagne électorale - Municipales',
                'date_debut' => '2026-03-02',
                'date_fin' => '2026-03-14',
                'type' => 'campagne',
                'lieu' => 'France',
                'description' => 'Période officielle de la campagne électorale pour le premier tour des élections municipales.',
                'icon' => '📢',
                'color' => '#10B981', // Vert
                'url_source' => 'https://www.service-public.fr/particuliers/vosdroits/F1939',
                'source' => 'calendrier-electoral',
                'uid' => 'municipales-2026-campagne-t1',
            ],

            // Présidentielle 2027
            [
                'titre' => 'Élection Présidentielle 2027 - 1er tour',
                'date_debut' => '2027-04-11',
                'date_fin' => '2027-04-11',
                'type' => 'election',
                'lieu' => 'France',
                'description' => 'Premier tour de l\'élection présidentielle française.',
                'icon' => '🇫🇷',
                'color' => '#EF4444', // Rouge
                'url_source' => 'https://www.conseil-constitutionnel.fr/',
                'source' => 'calendrier-electoral',
                'uid' => 'presidentielle-2027-t1',
            ],
            [
                'titre' => 'Élection Présidentielle 2027 - 2nd tour',
                'date_debut' => '2027-04-25',
                'date_fin' => '2027-04-25',
                'type' => 'election',
                'lieu' => 'France',
                'description' => 'Second tour de l\'élection présidentielle française. Les deux candidats arrivés en tête au premier tour s\'affrontent.',
                'icon' => '🇫🇷',
                'color' => '#EF4444',
                'url_source' => 'https://www.conseil-constitutionnel.fr/',
                'source' => 'calendrier-electoral',
                'uid' => 'presidentielle-2027-t2',
            ],

            // Législatives 2027
            [
                'titre' => 'Élections Législatives 2027 - 1er tour',
                'date_debut' => '2027-06-13',
                'date_fin' => '2027-06-13',
                'type' => 'election',
                'lieu' => 'France',
                'description' => 'Premier tour des élections législatives. Renouvellement de l\'Assemblée nationale.',
                'icon' => '🏛️',
                'color' => '#3B82F6', // Bleu
                'url_source' => 'https://www.assemblee-nationale.fr/',
                'source' => 'calendrier-electoral',
                'uid' => 'legislatives-2027-t1',
            ],
            [
                'titre' => 'Élections Législatives 2027 - 2nd tour',
                'date_debut' => '2027-06-20',
                'date_fin' => '2027-06-20',
                'type' => 'election',
                'lieu' => 'France',
                'description' => 'Second tour des élections législatives. Renouvellement de l\'Assemblée nationale.',
                'icon' => '🏛️',
                'color' => '#3B82F6',
                'url_source' => 'https://www.assemblee-nationale.fr/',
                'source' => 'calendrier-electoral',
                'uid' => 'legislatives-2027-t2',
            ],

            // Sénatoriales 2026
            [
                'titre' => 'Élections Sénatoriales 2026',
                'date_debut' => '2026-09-27',
                'date_fin' => '2026-09-27',
                'type' => 'election',
                'lieu' => 'France',
                'description' => 'Renouvellement de la moitié du Sénat (série 1). Élection au suffrage universel indirect par les grands électeurs.',
                'icon' => '🔴',
                'color' => '#EC4899', // Rose (Sénat)
                'url_source' => 'https://www.senat.fr/',
                'source' => 'calendrier-electoral',
                'uid' => 'senatoriales-2026',
            ],
        ];

        $force = $this->option('force');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($elections as $data) {
            $existing = EvenementLegislatif::where('uid', $data['uid'])
                ->first();

            if ($existing && !$force) {
                $skipped++;
                continue;
            }

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                EvenementLegislatif::create($data);
                $created++;
            }

            $this->line("  ✓ {$data['titre']} ({$data['date_debut']})");
        }

        $this->newLine();
        $this->info("✅ Terminé !");
        $this->table(
            ['Action', 'Nombre'],
            [
                ['Créés', $created],
                ['Mis à jour', $updated],
                ['Ignorés', $skipped],
            ]
        );

        return self::SUCCESS;
    }
}
