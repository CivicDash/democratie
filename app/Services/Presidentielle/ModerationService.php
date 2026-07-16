<?php

namespace App\Services\Presidentielle;

use App\Exceptions\ModerationException;
use App\Models\Argument;
use App\Models\IngestionProposition;
use App\Models\MesureScrutinLien;
use App\Models\PresidentielleModerationLog;
use App\Models\ProgrammeMesure;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Moteur du back-office de modération présidentielle (plan §5).
 * Applique les transitions de statut avec garde-fous éditoriaux bloquants,
 * et journalise chaque action. Aucun contenu ne devient public sans passer
 * par publier() qui vérifie les invariants (symétrie, sources, explication).
 */
class ModerationService
{
    /** Prise en charge : detecte -> en_review. */
    public function prendreEnCharge(Model $entite, User $user): Model
    {
        return $this->transitionStatut($entite, 'en_review', 'prise_en_charge', $user);
    }

    /** Demande de complément : -> a_completer. */
    public function demanderComplement(Model $entite, User $user, string $motif): Model
    {
        return $this->transitionStatut($entite, 'a_completer', 'demande_complement', $user, $motif);
    }

    /** Validation éditoriale : -> valide (pose valide_par/valide_at). */
    public function valider(Model $entite, User $user, ?string $commentaire = null): Model
    {
        $ancien = $entite->statut_validation;
        $entite->statut_validation = 'valide';
        if (in_array('valide_par', $entite->getFillable(), true)) {
            $entite->valide_par = $user->id;
            $entite->valide_at = now();
        }
        $entite->save();
        $this->log($entite, 'validation', $ancien, 'valide', $user, $commentaire);

        return $entite;
    }

    /**
     * Double validation d'un argument « contre » (règle §5).
     * Le second validateur doit être différent du premier.
     */
    public function doubleValider(Argument $argument, User $user): Argument
    {
        if ($argument->sens !== 'contre') {
            throw new ModerationException('La double validation ne concerne que les arguments « contre ».');
        }
        if ($argument->statut_validation !== 'valide' || ! $argument->valide_par) {
            throw new ModerationException('L\'argument doit d\'abord être validé une première fois.');
        }
        if ((int) $argument->valide_par === $user->id) {
            throw new ModerationException('La seconde validation doit être faite par un autre modérateur.');
        }
        $argument->double_valide_par = $user->id;
        $argument->double_valide_at = now();
        $argument->save();
        $this->log($argument, 'double_validation', 'valide', 'valide', $user);

        return $argument;
    }

    /**
     * Publication : affiche_publiquement = true, sous conditions d'invariants.
     *
     * @throws ModerationException si un invariant bloquant n'est pas satisfait
     */
    public function publier(Model $entite, User $user): Model
    {
        if (($entite->statut_validation ?? null) !== 'valide') {
            throw new ModerationException('Le contenu doit être au statut « valide » avant publication.');
        }

        $raisons = $this->raisonsNonPubliable($entite);
        if ($raisons) {
            throw new ModerationException('Publication impossible : '.implode(' ; ', $raisons));
        }

        $entite->affiche_publiquement = true;
        $entite->save();
        $this->log($entite, 'publication', 'valide', 'valide', $user);

        return $entite;
    }

    /** Dépublication (retrait de l'affichage public) — ne supprime pas le contenu. */
    public function depublier(Model $entite, User $user, ?string $motif = null): Model
    {
        $entite->affiche_publiquement = false;
        $entite->save();
        $this->log($entite, 'depublication', 'valide', $entite->statut_validation, $user, $motif);

        return $entite;
    }

    /**
     * Supprime une mesure (soft-delete, réversible) et détache sa (ses) proposition(s)
     * d'ingestion en la (les) renvoyant en file de tri (`detecte`, `mesure_id = null`).
     * Ce détachement est indispensable : sans lui, la prise de parole d'origine reste
     * bloquée à la suppression (documentDestroy refuse tant qu'une proposition est `rattachee`).
     * Refuse une mesure encore publiée : dépublier d'abord (protection du contenu public).
     *
     * @throws ModerationException si la mesure est encore affichée publiquement
     */
    public function supprimerMesure(ProgrammeMesure $mesure, User $user, ?string $motif = null): void
    {
        if ($mesure->affiche_publiquement) {
            throw new ModerationException('Dépubliez la mesure avant de la supprimer.');
        }

        IngestionProposition::where('mesure_id', $mesure->id)->update([
            'statut' => 'detecte',
            'mesure_id' => null,
            'valide_par' => null,
            'valide_at' => null,
        ]);

        $this->log($mesure, 'suppression', $mesure->statut_validation, null, $user, $motif);
        $mesure->delete();
    }

    /**
     * Crée une mesure (statut `detecte`, non publiée) à partir d'une proposition
     * d'ingestion validée, et rattache la proposition à cette mesure.
     * La citation verbatim et le timestamp sont conservés pour la vérification humaine.
     *
     * @throws ModerationException si la proposition n'a pas de candidat/thème résolu
     */
    public function creerMesureDepuisProposition(IngestionProposition $p, User $user): ProgrammeMesure
    {
        if (! $p->candidat_id || ! $p->theme_id) {
            throw new ModerationException('Proposition sans candidat ou thème résolu — à compléter avant rattachement.');
        }
        // Garde anti-rejeu (double-clic) : une proposition déjà traitée ne recrée pas de mesure.
        if ($p->statut !== 'detecte') {
            throw new ModerationException('Proposition déjà traitée (statut : '.$p->statut.').');
        }

        $mesure = ProgrammeMesure::create([
            'candidat_id' => $p->candidat_id,
            'theme_id' => $p->theme_id,
            'titre' => Str::limit($p->resume_propose, 295, ''),
            'resume' => Str::limit($p->resume_propose, 295, ''),
            'source_officielle_url' => $p->source_url,
            'statut_mesure' => $p->type === 'revirement' ? 'modifiee' : 'annoncee',
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
            'source_detection' => 'ingestion',
            'detection_confidence' => $p->confiance,
            'detection_raw_data' => [
                'proposition_uuid' => $p->uuid,
                'citation_verbatim' => $p->citation_verbatim,
                'timestamp' => $p->timestamp_ou_paragraphe,
                'type_origine' => $p->type,
            ],
        ]);

        $p->update(['statut' => 'rattachee', 'mesure_id' => $mesure->id, 'valide_par' => $user->id, 'valide_at' => now()]);

        $this->log($mesure, 'creation_depuis_proposition', null, 'detecte', $user);
        $this->log($p, 'rattachement', $p->getOriginal('statut'), 'rattachee', $user);

        return $mesure;
    }

    /** Rejette une proposition d'ingestion (statut `rejetee`), avec motif journalisé. */
    public function rejeterProposition(IngestionProposition $p, User $user, ?string $motif = null): IngestionProposition
    {
        $ancien = $p->statut;
        $p->update(['statut' => 'rejetee', 'valide_par' => $user->id, 'valide_at' => now()]);
        $this->log($p, 'rejet', $ancien, 'rejetee', $user, $motif);

        return $p;
    }

    /** Raisons bloquant la publication (vide = publiable). */
    public function raisonsNonPubliable(Model $entite): array
    {
        $raisons = [];

        if ($entite instanceof ProgrammeMesure) {
            if (blank($entite->source_officielle_url)) {
                $raisons[] = 'aucune source officielle';
            }
            $args = $entite->arguments()->publie()->with('sources')->get();
            $pour = $args->where('sens', 'pour')->first(fn ($a) => $this->aSourceFiable($a));
            $contre = $args->where('sens', 'contre')->first(fn ($a) => $this->aSourceFiable($a));
            if (! $pour) {
                $raisons[] = 'aucun argument « pour » publié et sourcé';
            }
            if (! $contre) {
                $raisons[] = 'aucun argument « contre » publié et sourcé (symétrie obligatoire)';
            }
        }

        if ($entite instanceof Argument && $entite->sens === 'contre' && ! $entite->double_valide_par) {
            $raisons[] = 'argument « contre » non doublement validé';
        }

        if ($entite instanceof MesureScrutinLien && blank($entite->explication)) {
            $raisons[] = 'lien scrutin sans explication rédigée';
        }

        return $raisons;
    }

    private function aSourceFiable(Argument $argument): bool
    {
        return $argument->sources->contains(fn ($s) => in_array($s->fiabilite, ['haute', 'moyenne'], true));
    }

    private function transitionStatut(Model $entite, string $nouveau, string $action, User $user, ?string $commentaire = null): Model
    {
        $ancien = $entite->statut_validation;
        $entite->statut_validation = $nouveau;
        $entite->save();
        $this->log($entite, $action, $ancien, $nouveau, $user, $commentaire);

        return $entite;
    }

    private function log(Model $entite, string $action, ?string $ancien, ?string $nouveau, User $user, ?string $commentaire = null): void
    {
        PresidentielleModerationLog::create([
            'entite_type' => $entite->getMorphClass(),
            'entite_id' => $entite->getKey(),
            'action' => $action,
            'ancien_statut' => $ancien,
            'nouveau_statut' => $nouveau,
            'commentaire' => $commentaire,
            'moderator_id' => $user->id,
            'created_at' => now(),
        ]);
    }
}
