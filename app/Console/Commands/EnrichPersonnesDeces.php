<?php

namespace App\Console\Commands;

use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnrichPersonnesDeces extends Command
{
    protected $signature = 'enrich:personnes-deces {--dry-run : Afficher sans modifier}';

    protected $description = 'Enrichit les dates de décès des personnalités politiques connues';

    /**
     * Données des personnalités décédées connues
     * Format: 'prenom nom' => 'YYYY-MM-DD'
     */
    protected array $personnesDecedees = [
        // Présidents de la Ve République
        'Charles de Gaulle' => '1970-11-09',
        'Georges Pompidou' => '1974-04-02',
        'François Mitterrand' => '1996-01-08',
        'Jacques Chirac' => '2019-09-26',

        // Premiers ministres décédés
        'Michel Debré' => '1996-08-02',
        'Georges Pompidou' => '1974-04-02',
        'Jacques Chaban-Delmas' => '2000-11-10',
        'Pierre Messmer' => '2007-08-29',
        'Raymond Barre' => '2007-08-25',
        'Pierre Mauroy' => '2013-06-07',
        'Pierre Bérégovoy' => '1993-05-01',
        'Édouard Balladur' => null, // vivant
        'Lionel Jospin' => null, // vivant

        // Ministres historiques décédés
        'André Malraux' => '1976-11-23',
        'Robert Schuman' => '1963-09-04',
        'Edgar Faure' => '1988-03-30',
        'Maurice Couve de Murville' => '1999-12-24',
        'Michel Jobert' => '2002-05-26',
        'Jean-Pierre Chevènement' => null, // vivant
        'Simone Veil' => '2017-06-30',
        'Robert Badinter' => '2024-02-09',
        'Jack Lang' => null, // vivant
        'Christiane Taubira' => null, // vivant

        // Autres personnalités importantes
        'Valéry Giscard d\'Estaing' => '2020-12-02',
        'Jean Lecanuet' => '1993-02-22',
        'Michel Rocard' => '2016-07-02',
        'Jacques Delors' => '2023-12-27',
        'Philippe Séguin' => '2010-01-07',
    ];

    public function handle(): int
    {
        $this->info('🔍 Recherche des personnalités à enrichir...');

        $updated = 0;
        $notFound = [];
        $alreadySet = 0;

        foreach ($this->personnesDecedees as $nomComplet => $dateDeces) {
            if ($dateDeces === null) {
                continue; // Personne vivante, on ignore
            }

            // Essayer de trouver la personne par nom
            $parts = $this->parseNomComplet($nomComplet);

            $personne = PersonnePolitique::where(function ($query) use ($parts) {
                // Recherche par prénom + nom
                $query->where('prenom', 'ILIKE', $parts['prenom'])
                    ->where('nom', 'ILIKE', $parts['nom']);
            })->orWhere(function ($query) use ($nomComplet) {
                // Ou par nom complet dans le champ nom (cas De Gaulle, etc.)
                $query->where('nom', 'ILIKE', '%'.$nomComplet.'%');
            })->first();

            if (! $personne) {
                // Essayer une recherche plus large
                $personne = PersonnePolitique::where('nom', 'ILIKE', '%'.$parts['nom'].'%')
                    ->where('prenom', 'ILIKE', $parts['prenom'].'%')
                    ->first();
            }

            if (! $personne) {
                $notFound[] = $nomComplet;

                continue;
            }

            if ($personne->date_deces) {
                $alreadySet++;
                $this->line("  ⏭️  {$personne->nom_complet} - date de décès déjà renseignée");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->info("  🔍 {$personne->nom_complet} → {$dateDeces} (dry-run)");
            } else {
                $personne->date_deces = Carbon::parse($dateDeces);
                $personne->save();
                $this->info("  ✅ {$personne->nom_complet} → décédé le {$dateDeces}");
            }

            $updated++;
        }

        $this->newLine();
        $this->info('📊 Résumé :');
        $this->line("  - Mis à jour : {$updated}");
        $this->line("  - Déjà renseignés : {$alreadySet}");
        $this->line('  - Non trouvés : '.count($notFound));

        if (! empty($notFound)) {
            $this->newLine();
            $this->warn('⚠️  Personnalités non trouvées dans la base :');
            foreach ($notFound as $nom) {
                $this->line("    - {$nom}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Parse un nom complet en prénom et nom
     */
    protected function parseNomComplet(string $nomComplet): array
    {
        // Cas spéciaux avec particules
        $particules = ['de', 'du', 'd\'', 'le', 'la', 'des'];

        $parts = explode(' ', $nomComplet);

        // Cas "Charles de Gaulle" → prénom: Charles, nom: de Gaulle
        if (count($parts) >= 3) {
            $prenom = array_shift($parts);
            $nom = implode(' ', $parts);

            return ['prenom' => $prenom, 'nom' => $nom];
        }

        // Cas simple "Georges Pompidou"
        if (count($parts) === 2) {
            return ['prenom' => $parts[0], 'nom' => $parts[1]];
        }

        // Cas avec un seul mot
        return ['prenom' => '', 'nom' => $nomComplet];
    }
}
