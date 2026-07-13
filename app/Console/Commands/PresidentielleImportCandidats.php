<?php

namespace App\Console\Commands;

use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Importe la liste des candidats depuis un JSON de seed (contrat seed_presidentielle).
 * TOUT entre en statut_validation=detecte / affiche_publiquement=false : vérification
 * humaine obligatoire avant publication (les source_url null sont à sourcer).
 * Réutilise les personnes_politiques existantes (firstOrCreate par slug) — pas de doublon.
 */
class PresidentielleImportCandidats extends Command
{
    protected $signature = 'presidentielle:import-candidats {fichier} {--election=2027}';

    protected $description = 'Importe/actualise la liste des candidats (statut detecte) depuis un JSON de seed.';

    public function handle(): int
    {
        $fichier = $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($fichier), true);
        $candidats = $data['candidats'] ?? null;
        if (! is_array($candidats)) {
            $this->error('JSON invalide : clé "candidats" attendue.');

            return self::FAILURE;
        }

        $election = (string) $this->option('election');
        $crees = 0;
        $maj = 0;

        foreach ($candidats as $c) {
            $slug = $c['slug'] ?? null;
            if (! $slug) {
                continue;
            }
            [$prenom, $nom] = $this->separerNom($c['nom'] ?? $slug);

            $personne = PersonnePolitique::firstOrCreate(
                ['slug' => $slug],
                ['prenom' => $prenom, 'nom' => $nom, 'parti_politique' => $c['parti'] ?? null, 'nuance_politique' => $c['nuance'] ?? null]
            );

            $candidat = CandidatPresidentielle::updateOrCreate(
                ['personne_politique_id' => $personne->id, 'election' => $election],
                [
                    'uuid' => (string) Str::uuid(),
                    'statut_candidature' => $c['statut_candidature'] ?? 'declare',
                    'date_declaration' => $c['date_declaration'] ?? null,
                    'parti_soutien' => $c['parti'] ?? null,
                    'nuance_politique' => $c['nuance'] ?? null,
                    'condition' => $c['condition'] ?? null,
                    'site_campagne_url' => $c['site_campagne'] ?? null,
                    'programme_url_officiel' => $c['programme_url'] ?? null,
                    'source_detection' => 'seed',
                    // Non publié : vérification humaine obligatoire.
                    'statut_validation' => 'detecte',
                    'affiche_publiquement' => false,
                ]
            );

            $candidat->wasRecentlyCreated ? $crees++ : $maj++;
        }

        $this->info("Candidats importés en file de modération (detecte) : {$crees} créé(s), {$maj} mis à jour.");
        $this->line('Aucun n\'est publié : à valider un par un au back-office.');

        return self::SUCCESS;
    }

    /** "Jean-Luc Mélenchon" -> ["Jean-Luc", "Mélenchon"]. */
    private function separerNom(string $complet): array
    {
        $parts = preg_split('/\s+/', trim($complet), 2);

        return [$parts[0] ?? $complet, $parts[1] ?? ''];
    }
}
