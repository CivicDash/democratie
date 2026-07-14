<?php

namespace App\Console\Commands;

use App\Models\CandidatPresidentielle;
use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Alimente le parcours des candidats à partir des données déjà en base CivicDash :
 * fonctions gouvernementales (postes_ministeriels), mandats AN/Sénat, maire.
 * Chaque événement entre en statut `detecte` (validation humaine avant publication) ;
 * dédoublonnage par (personne, type, titre, date_debut).
 */
class PresidentielleImportParcours extends Command
{
    protected $signature = 'presidentielle:import-parcours {--candidat= : slug d\'un candidat, sinon tous les candidats de l\'élection} {--election=2027}';

    protected $description = 'Importe le parcours (fonctions gouvernementales, mandats) depuis les données CivicDash.';

    public function handle(): int
    {
        $query = CandidatPresidentielle::where('election', $this->option('election'))->with('personnePolitique');
        if ($slug = $this->option('candidat')) {
            $query->whereHas('personnePolitique', fn ($q) => $q->where('slug', $slug));
        }
        $candidats = $query->get();

        $total = 0;
        foreach ($candidats as $candidat) {
            $personne = $candidat->personnePolitique;
            if (! $personne) {
                continue;
            }
            $total += $this->importFonctionsGouvernementales($personne);
            $total += $this->importMandats($personne);
        }

        $this->info("{$total} événement(s) de parcours importé(s) en statut detecte (à valider).");

        return self::SUCCESS;
    }

    private function importFonctionsGouvernementales(PersonnePolitique $personne): int
    {
        $n = 0;
        foreach ($personne->postes()->with('gouvernement')->get() as $poste) {
            $organisation = $poste->gouvernement?->nom ? "Gouvernement {$poste->gouvernement->nom}" : 'Gouvernement';
            if ($this->creer($personne, 'fonction_gouvernementale', (string) $poste->fonction, $organisation, $poste->date_debut, $poste->date_fin)) {
                $n++;
            }
        }

        return $n;
    }

    private function importMandats(PersonnePolitique $personne): int
    {
        $n = 0;
        // Député (si rattaché à un acteur AN) — période souvent partielle : titre générique.
        if ($personne->uid_an && $personne->depute) {
            if ($this->creer($personne, 'mandat', 'Députée/Député à l\'Assemblée nationale', 'Assemblée nationale', null, null)) {
                $n++;
            }
        }
        if ($personne->uid_senat && $personne->senateur) {
            if ($this->creer($personne, 'mandat', 'Sénatrice/Sénateur', 'Sénat', null, null)) {
                $n++;
            }
        }
        if ($personne->maire_id && $personne->maire) {
            if ($this->creer($personne, 'mandat', 'Maire', (string) ($personne->maire->nom_commune ?? 'Commune'), null, null)) {
                $n++;
            }
        }

        return $n;
    }

    /** Crée l'événement s'il n'existe pas déjà (dédoublonnage). */
    private function creer(PersonnePolitique $personne, string $type, string $titre, ?string $organisation, $dateDebut, $dateFin): bool
    {
        $existe = ParcoursEvenement::where('personne_politique_id', $personne->id)
            ->where('type', $type)->where('titre', $titre)
            ->where('date_debut', $dateDebut ? \Illuminate\Support\Carbon::parse($dateDebut)->toDateString() : null)
            ->exists();
        if ($existe) {
            return false;
        }

        ParcoursEvenement::create([
            'uuid' => (string) Str::uuid(),
            'personne_politique_id' => $personne->id,
            'type' => $type,
            'titre' => $titre,
            'organisation' => $organisation,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'en_cours' => $dateDebut && ! $dateFin,
            'source_detection' => 'civicdash',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ]);

        return true;
    }
}
