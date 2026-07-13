<?php

namespace App\Services\Presidentielle;

use App\Exceptions\ModerationException;
use App\Models\Argument;
use App\Models\MesureScrutinLien;
use App\Models\PresidentielleModerationLog;
use App\Models\ProgrammeMesure;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
