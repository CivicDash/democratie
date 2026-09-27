<?php

namespace App\Console\Commands;

use App\Models\CandidatPresidentielle;
use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alimente le parcours des candidats à partir des données déjà en base CivicDash :
 * fonctions gouvernementales (postes_ministeriels), mandats de député et de sénateur datés,
 * mandat de maire, déclarations HATVP.
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
            $total += $this->importDepuisHatvp($personne);
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

    /**
     * Mandats parlementaires et municipaux, datés à partir des tables CivicDash : un
     * événement par mandat de député (deputes_circonscriptions, avec sa circonscription),
     * par mandat de sénateur (senateurs_mandats) et pour le mandat de maire en cours.
     * Une ligne générique sans date n'est créée qu'à défaut de mandat daté : elle ne dit ni
     * quand, ni où, et se lit comme un mandat en cours.
     */
    private function importMandats(PersonnePolitique $personne): int
    {
        $n = 0;

        if ($personne->uid_an && $personne->depute) {
            $titre = $this->feminin($personne, $personne->depute->civilite) ? 'Députée' : 'Député';
            $url = 'https://www.assemblee-nationale.fr/dyn/deputes/'.$personne->uid_an;
            $mandats = DB::table('deputes_circonscriptions')->where('acteur_uid', $personne->uid_an)->orderBy('date_debut')->get();
            foreach ($mandats as $m) {
                $circo = $m->num_circo ? ', '.($m->num_circo == 1 ? '1re' : $m->num_circo.'e').' circonscription' : '';
                $n += $this->creer($personne, 'mandat', $titre, 'Assemblée nationale — '.$m->departement.$circo, $m->date_debut, $m->date_fin, 'civicdash', $url) ? 1 : 0;
            }
            if ($mandats->isEmpty()) {
                $n += $this->creer($personne, 'mandat', 'Députée/Député à l\'Assemblée nationale', 'Assemblée nationale', null, null, 'civicdash', $url) ? 1 : 0;
            }
        }

        if ($personne->uid_senat && $personne->senateur) {
            $s = $personne->senateur;
            $titre = $this->feminin($personne, $s->civilite) ? 'Sénatrice' : 'Sénateur';
            $url = 'https://www.senat.fr/senateur/'.Str::slug($s->nom_usuel ?? $s->nom, '_').'_'.Str::slug($s->prenom_usuel ?? $s->prenom, '_').strtolower($s->matricule).'.html';
            $mandats = DB::table('senateurs_mandats')->where('senateur_matricule', $personne->uid_senat)->orderBy('date_debut')->get();
            foreach ($mandats as $m) {
                $n += $this->creer($personne, 'mandat', $titre, 'Sénat — '.($m->departement_nom ?? 'circonscription inconnue'), $m->date_debut, $m->date_fin, 'civicdash', $url) ? 1 : 0;
            }
            if ($mandats->isEmpty()) {
                $n += $this->creer($personne, 'mandat', 'Sénatrice/Sénateur', 'Sénat', null, null, 'civicdash', $url) ? 1 : 0;
            }
        }

        if ($personne->maire_id && $personne->maire) {
            $m = $personne->maire;
            $n += $this->creer($personne, 'mandat', 'Maire', (string) ($m->nom_commune ?? 'Commune'),
                $m->debut_mandat, $m->en_exercice ? null : $m->fin_mandat, 'civicdash',
                'https://www.data.gouv.fr/fr/datasets/repertoire-national-des-elus-1/') ? 1 : 0;
        }

        return $n;
    }

    /** Titre au féminin ? La civilité de la source d'abord, celle de la fiche à défaut. */
    private function feminin(PersonnePolitique $personne, ?string $civiliteSource = null): bool
    {
        return str_starts_with((string) ($civiliteSource ?: $personne->civilite), 'Mme');
    }

    /**
     * Enrichit le parcours depuis la déclaration HATVP rattachée (DIA) : mandats électifs,
     * activités professionnelles/consultant, participations dirigeantes, fonctions bénévoles.
     * Les dates y sont déclarées au mois près : la précision « mois » le dit à l'affichage.
     * Événements en `detecte` (validation humaine), sourcés vers la fiche HATVP.
     */
    private function importDepuisHatvp(PersonnePolitique $personne): int
    {
        $decl = $personne->declarationsHatvp()->with([
            'mandatsElectifs', 'activitesProfessionnelles', 'activitesConsultant',
            'participationsDirigeantes', 'fonctionsBenevoles',
        ])->first();
        if (! $decl) {
            return 0;
        }

        $url = $personne->url_hatvp
            ?? 'https://www.hatvp.fr/fiche-nominative/?declarant='
            .urlencode(strtolower($personne->nom).'-'.strtolower($personne->prenom));
        $n = 0;

        foreach ($decl->mandatsElectifs as $m) {
            $titre = $m->description_mandat ?? $m->description ?? 'Mandat électif';
            $n += $this->creer($personne, 'mandat', $titre, null, $m->date_debut, $m->date_fin, 'hatvp', $url, 'mois') ? 1 : 0;
        }
        foreach ($decl->activitesProfessionnelles as $a) {
            if (blank($a->description) && in_array(trim((string) $a->employeur), ['', 'Non précisé'], true)) {
                continue;
            }
            $n += $this->creer($personne, 'poste_prive', $a->description ?? 'Activité professionnelle', $a->employeur, $a->date_debut, $a->date_fin, 'hatvp', $url, 'mois') ? 1 : 0;
        }
        foreach ($decl->activitesConsultant as $a) {
            $n += $this->creer($personne, 'poste_prive', $a->description ?? 'Activité de conseil', null, $a->date_debut, $a->date_fin, 'hatvp', $url, 'mois') ? 1 : 0;
        }
        foreach ($decl->participationsDirigeantes as $p) {
            $titre = $p->activite ?: 'Mandat de direction';
            $n += $this->creer($personne, 'poste_prive', $titre, $p->nom_societe ?? $p->societe, $p->date_debut, $p->date_fin, 'hatvp', $url, 'mois') ? 1 : 0;
        }
        foreach ($decl->fonctionsBenevoles as $f) {
            // Ni intitulé ni organisme : la ligne « Fonction bénévole » n'apprendrait rien.
            if (blank($f->description) && blank($f->organisme)) {
                continue;
            }
            $n += $this->creer($personne, 'engagement', $f->description ?? 'Fonction bénévole', $f->organisme, null, null, 'hatvp', $url) ? 1 : 0;
        }

        return $n;
    }

    /**
     * Crée l'événement s'il n'existe pas déjà (dédoublonnage).
     *
     * Une ligne corrigée à la main garde sa clé d'origine (`cle_import` : type, titre, date
     * de début telle que la source la donnait), et une ligne rejetée est supprimée en douce.
     * Les deux doivent être reconnues : sinon la synchro suivante recréerait, en
     * « détecté », la date fausse ou le doublon qu'un modérateur vient d'écarter.
     */
    private function creer(PersonnePolitique $personne, string $type, string $titre, ?string $organisation, $dateDebut, $dateFin, string $sourceDetection = 'civicdash', ?string $sourceUrl = null, string $precision = 'jour'): bool
    {
        $titre = trim($titre) !== '' ? $titre : 'Sans titre';
        $debut = $dateDebut ? \Illuminate\Support\Carbon::parse($dateDebut)->toDateString() : null;
        $existe = ParcoursEvenement::withTrashed()
            ->where('personne_politique_id', $personne->id)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('type', $type)->where('titre', $titre)->where('date_debut', $debut))
                ->orWhere('detection_raw_data->cle_import', $type.'|'.$titre.'|'.$debut))
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
            'precision_debut' => $precision,
            'precision_fin' => $precision,
            'en_cours' => $dateDebut && ! $dateFin,
            'source_url' => $sourceUrl,
            'source_detection' => $sourceDetection,
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ]);

        return true;
    }
}
