<?php

namespace App\Services\Presidentielle;

use App\Models\CadrageBudgetaire;
use App\Models\CadrageLigne;
use App\Models\IngestionProposition;
use App\Support\Locuteur;
use App\Support\UrlSource;

/**
 * Ce qui rend publiable un cadrage budgétaire — écrit une seule fois.
 *
 * Deux appelants, comme ReglesAffirmation : ModerationService::raisonsNonPubliable() (le
 * bouton « Publier ») et IntegriteChecker (l'export). Seules les lignes VÉRIFIÉES sont
 * contrôlées : ce sont les seules qui paraissent.
 *
 * Le principe : chaque chiffre affiché est cité, repéré dans sa source et attribué. Aucun
 * chiffre n'est calculé par nous — ni total, ni conversion entre cumulé et annuel.
 */
class ReglesCadrageBudgetaire
{
    private const TIMECODE = '/^\d{1,2}:\d{2}:\d{2}$/';

    public function __construct(private ReglesPorteur $porteur) {}

    /** @return list<string> vide = publiable */
    public function raisons(CadrageBudgetaire $c): array
    {
        $c->loadMissing(['lignes.proposition.document', 'lignes.document', 'document', 'programmeDocument']);
        $raisons = [];

        if (! array_key_exists((string) $c->nature, CadrageBudgetaire::NATURES)) {
            $raisons[] = "nature inconnue « {$c->nature} »";
        }
        if (! $c->date_source) {
            $raisons[] = 'date de présentation manquante';
        }

        if ($c->candidat_id === null) {
            if ($c->nature !== CadrageBudgetaire::NATURE_REFERENCE) {
                $raisons[] = 'un cadrage sans candidat ne peut être que la référence officielle';
            }
        } else {
            if ($c->nature === CadrageBudgetaire::NATURE_REFERENCE) {
                $raisons[] = 'la nature « référence officielle » est réservée à la ligne sans candidat';
            }
            $raisons = [...$raisons, ...$this->raisonsDocument($c)];

            // La référence est affichée en regard de chaque cadrage : sans elle, le tableau
            // n'a pas de repère commun. Elle se publie donc la première.
            $referencePubliee = CadrageBudgetaire::publie()->reference()
                ->where('election', $c->election)->exists();
            if (! $referencePubliee) {
                $raisons[] = 'aucune ligne de référence officielle publiée pour '.$c->election.' : publiez-la d\'abord (écran Budgets)';
            }
        }

        $verifiees = $c->lignes->filter->estAffichee();
        if ($verifiees->isEmpty()) {
            $raisons[] = 'aucune ligne vérifiée';
        }

        foreach ($verifiees as $ligne) {
            foreach ($this->raisonsLigne($ligne, $c) as $r) {
                $raisons[] = self::nomLigne($ligne).' : '.$r;
            }
        }

        // Deux valeurs vérifiées pour la même case afficheraient deux chiffres sans dire
        // lequel vaut : il faut en garder une, ou les distinguer par un libellé.
        $groupes = $verifiees
            ->reject(fn (CadrageLigne $l) => in_array($l->rubrique, CadrageLigne::RUBRIQUES_MULTIPLES, true))
            ->groupBy(fn (CadrageLigne $l) => $l->rubrique.'|'.($l->annee ?? '').'|'.mb_strtolower(trim((string) $l->libelle)));
        foreach ($groupes as $groupe) {
            if ($groupe->count() > 1) {
                $raisons[] = self::nomLigne($groupe->first()).' : '.$groupe->count()
                    .' lignes vérifiées pour la même case — n\'en garder qu\'une, ou les distinguer par un libellé';
            }
        }

        return $raisons;
    }

    /**
     * Ce qui manque à une ligne pour paraître. Appelée sur les lignes vérifiées par
     * raisons(), et sur toutes par l'écran, pour dire d'avance ce qui bloquera.
     *
     * @return list<string>
     */
    public function raisonsLigne(CadrageLigne $l, CadrageBudgetaire $c): array
    {
        $reference = $c->candidat_id === null;
        $raisons = [];

        $admises = $reference
            ? array_keys(CadrageLigne::RUBRIQUES)
            : [...array_keys(CadrageLigne::GRILLE), CadrageLigne::POSTE];
        if (! in_array($l->rubrique, $admises, true)) {
            $raisons[] = "rubrique « {$l->rubrique} » non admise ".($reference ? 'dans la référence' : 'dans le cadrage d\'un candidat');
        }
        if (in_array($l->rubrique, CadrageLigne::RUBRIQUES_MULTIPLES, true) && blank($l->libelle)) {
            $raisons[] = 'libellé manquant (nom du poste, de l\'hypothèse ou de l\'objectif)';
        }

        if (! array_key_exists((string) $l->unite, CadrageLigne::UNITES)
            || ! in_array($l->unite, CadrageLigne::unitesAdmises((string) $l->rubrique), true)) {
            $raisons[] = "unité « {$l->unite} » incohérente avec la rubrique";
        }
        if ($l->unite === 'texte') {
            if (blank($l->valeur_texte)) {
                $raisons[] = 'valeur en toutes lettres manquante';
            }
        } else {
            if ($l->montant === null) {
                $raisons[] = 'montant manquant (sinon, unité « en toutes lettres » et la valeur telle que dite)';
            } elseif ($l->montant_max !== null && (float) $l->montant_max < (float) $l->montant) {
                $raisons[] = 'fourchette inversée (maximum inférieur au minimum)';
            }
            if (in_array($l->rubrique, CadrageLigne::RUBRIQUES_DATEES, true) && ! $l->annee) {
                $raisons[] = 'année manquante : un déficit ou une dette se lit à une date';
            }
        }
        if ($l->qualificatif !== null && ! array_key_exists($l->qualificatif, CadrageLigne::QUALIFICATIFS)) {
            $raisons[] = "qualificatif inconnu « {$l->qualificatif} »";
        }
        if ($l->portee !== null && ! array_key_exists($l->portee, CadrageLigne::PORTEES)) {
            $raisons[] = "portée inconnue « {$l->portee} »";
        }

        if (filled($l->locuteur) && blank($l->role_locuteur)) {
            $raisons[] = "locuteur « {$l->locuteur} » sans rôle";
        }
        if ($l->qualite_locuteur !== null && $l->qualite_locuteur !== Locuteur::EQUIPE) {
            $raisons[] = 'seuls le candidat et son équipe de campagne entrent dans son cadrage';
        }

        return [...$raisons, ...($reference ? $this->sourceReference($l) : $this->sourceCandidat($l, $c))];
    }

    /** Libellé lisible d'une ligne, pour les messages. */
    public static function nomLigne(CadrageLigne $l): string
    {
        $nom = CadrageLigne::libelleRubrique((string) $l->rubrique);
        if (filled($l->libelle)) {
            $nom .= ' « '.mb_strimwidth($l->libelle, 0, 60, '…').' »';
        }

        return $nom.($l->annee ? " {$l->annee}" : '');
    }

    /** @return list<string> */
    private function raisonsDocument(CadrageBudgetaire $c): array
    {
        $nbDocuments = count(array_filter([$c->ingestion_document_id, $c->programme_document_id]));
        if ($nbDocuments !== 1) {
            return ['exactement un document source requis (prise de parole ou référentiel de programme)'];
        }

        $doc = $c->documentSource();
        if (! $doc) {
            return ['document source introuvable'];
        }

        $raisons = [];
        if (! UrlSource::estValide($doc->url)) {
            $raisons[] = 'document source sans URL valide';
        }
        foreach ($this->porteur->raisons($doc) as $r) {
            $raisons[] = 'document source : '.$r;
        }
        if ($doc->porteurEstEntourage()) {
            $raisons[] = 'document porté par un parti ou un groupe parlementaire : sa vision n\'est pas le cadrage du candidat (section « Autour du candidat »)';
        }

        return $raisons;
    }

    /** @return list<string> */
    private function sourceReference(CadrageLigne $l): array
    {
        $raisons = [];
        if (blank($l->source_producteur)) {
            $raisons[] = 'producteur de la source manquant (INSEE, ministère, HCFP…)';
        }
        if (! UrlSource::estValide($l->source_url)) {
            $raisons[] = 'URL de la source manquante ou invalide';
        }
        if (! $l->source_date) {
            $raisons[] = 'date de la source manquante';
        }
        if (blank($l->citation)) {
            $raisons[] = 'extrait de la source manquant';
        }
        if (filled($l->locuteur)) {
            $raisons[] = 'une ligne de référence n\'a pas de locuteur';
        }

        return $raisons;
    }

    /** @return list<string> */
    private function sourceCandidat(CadrageLigne $l, CadrageBudgetaire $c): array
    {
        $raisons = [];
        if (blank($l->citation)) {
            $raisons[] = 'citation manquante';
        }
        if (blank($l->reperage)) {
            $raisons[] = 'repérage manquant (timecode hh:mm:ss ou page)';
        }

        $doc = $l->document ?? $c->documentSource();
        if (! $doc) {
            $raisons[] = 'aucun document source';
        } else {
            if (! UrlSource::estValide($doc->url)) {
                $raisons[] = 'document sans URL valide';
            }
            // Le document de l'en-tête est contrôlé une fois pour tout le cadrage.
            if ($l->ingestion_document_id) {
                foreach ($this->porteur->raisons($doc) as $r) {
                    $raisons[] = 'document : '.$r;
                }
                if ($doc->porteurEstEntourage()) {
                    $raisons[] = 'document porté par un parti ou un groupe parlementaire';
                }
            }
            if (filled($l->reperage) && preg_match('#youtube\.com|youtu\.be#', (string) $doc->url)
                && ! preg_match(self::TIMECODE, trim((string) $l->reperage))) {
                $raisons[] = 'repérage d\'une vidéo au format hh:mm:ss attendu';
            }
        }

        if ($l->proposition_id) {
            $p = $l->proposition;
            if (! $p) {
                $raisons[] = 'proposition source introuvable';
            } else {
                if (! in_array($p->statut, IngestionProposition::STATUTS_PUBLICS, true)) {
                    $raisons[] = "proposition source non validée (statut {$p->statut})";
                }
                if ((int) $p->candidat_id !== (int) $c->candidat_id) {
                    $raisons[] = 'proposition source d\'un autre candidat';
                }
                $documentId = $l->ingestion_document_id ?? $c->ingestion_document_id;
                if ($documentId !== null && (int) $p->document_id !== (int) $documentId) {
                    $raisons[] = 'proposition source issue d\'un autre document';
                }
                if (! $p->verbatim_verifie) {
                    $raisons[] = 'citation de la proposition source non vérifiée';
                }
                if ($p->estEntourage()) {
                    $raisons[] = 'proposition source de l\'entourage (parti)';
                }
            }
        }

        return $raisons;
    }
}
