<?php

namespace App\Services\Presidentielle;

use App\Exceptions\ModerationException;
use App\Models\Affirmation;
use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\CadrageBudgetaire;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\MesureScrutinLien;
use App\Models\PresidentielleModerationLog;
use App\Models\ProgrammeDocument;
use App\Models\ProgrammeMesure;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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
     * Double validation d'une liaison « contre » (règle §5). Le sens vit désormais sur la
     * liaison : c'est l'emploi d'un fait CONTRE une mesure qu'on soumet à double contrôle.
     * Le second validateur doit être différent du premier.
     */
    public function doubleValider(ArgumentMesureLien $lien, User $user): ArgumentMesureLien
    {
        if ($lien->sens !== 'contre') {
            throw new ModerationException('La double validation ne concerne que les liaisons « contre ».');
        }
        if ($lien->statut_validation !== 'valide' || ! $lien->valide_par) {
            throw new ModerationException('La liaison doit d\'abord être validée une première fois.');
        }
        if ((int) $lien->valide_par === $user->id) {
            throw new ModerationException('La seconde validation doit être faite par un autre modérateur.');
        }
        $lien->double_valide_par = $user->id;
        $lien->double_valide_at = now();
        $lien->save();
        $this->log($lien, 'double_validation', 'valide', 'valide', $user);

        return $lien;
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
     * Supprime un repère chiffré (soft-delete, réversible). Refuse une fiche
     * encore publiée : dépublier d'abord, comme pour les mesures.
     *
     * @throws ModerationException si la fiche est encore affichée publiquement
     */
    public function supprimerAffirmation(Affirmation $fiche, User $user, ?string $motif = null): void
    {
        if ($fiche->affiche_publiquement) {
            throw new ModerationException('Dépubliez la fiche avant de la supprimer.');
        }

        $this->log($fiche, 'suppression', $fiche->statut_validation, null, $user, $motif);
        $fiche->delete();
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
        // Premier garde-fou : la parole d'un parti ne devient jamais une mesure du candidat.
        // La contrainte CHECK de la table le garantit aussi pour un locuteur `parti` ; le
        // porteur du document, lui, ne se contrôle qu'ici.
        if ($p->estEntourage()) {
            throw new ModerationException('Propos de '.($p->locuteur ?? $p->document?->porteur_libelle ?? 'l\'entourage')
                .' : une proposition de l\'entourage ne devient jamais une mesure du candidat. Utilisez « Valider pour Autour du candidat ».');
        }
        if (! $p->candidat_id || ! $p->theme_id) {
            throw new ModerationException('Proposition sans candidat ou thème résolu — à compléter avant rattachement.');
        }
        // Garde anti-rejeu (double-clic) : une proposition déjà traitée ne recrée pas de mesure.
        if ($p->statut !== 'detecte') {
            throw new ModerationException('Proposition déjà traitée (statut : '.$p->statut.').');
        }
        // Rattachée, elle paraît aussitôt dans les prises de parole : son locuteur doit
        // être nommé avec son rôle.
        if ($raisons = app(ReglesLocuteur::class)->raisons($p)) {
            throw new ModerationException('Rattachement impossible : '.implode(' ; ', $raisons));
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
        if ($p->statut === 'rattachee') {
            throw new ModerationException('Proposition rattachée à une mesure : supprimez d\'abord la mesure (la proposition revient en file).');
        }
        $ancien = $p->statut;
        $p->update(['statut' => 'rejetee', 'valide_par' => $user->id, 'valide_at' => now()]
            + (self::colonne('affiche_publiquement') ? ['affiche_publiquement' => false] : []));
        $this->log($p, 'rejet', $ancien, 'rejetee', $user, $motif);

        return $p;
    }

    /**
     * « Valider sans mesure » : une proposition du candidat ou de son équipe, relue, qui
     * n'a pas vocation à devenir une mesure (une déclaration chiffrée, un constat). Validée,
     * elle paraît dans les prises de parole du candidat — seules les propositions validées
     * ou rattachées y paraissent.
     *
     * @throws ModerationException
     */
    public function validerSansMesure(IngestionProposition $p, User $user, ?string $commentaire = null): IngestionProposition
    {
        if ($p->statut !== 'detecte') {
            throw new ModerationException('Proposition déjà traitée (statut : '.$p->statut.').');
        }
        if (! $p->candidat_id) {
            throw new ModerationException('Proposition sans candidat résolu — à compléter avant validation.');
        }
        if ($raisons = app(ReglesLocuteur::class)->raisons($p)) {
            throw new ModerationException('Validation impossible : '.implode(' ; ', $raisons));
        }

        $p->update(['statut' => 'validee', 'valide_par' => $user->id, 'valide_at' => now()]);
        $this->log($p, 'validation_sans_mesure', 'detecte', 'validee', $user, $commentaire);

        return $p;
    }

    /**
     * Valide d'un coup les propositions encore en file d'une prise de parole.
     *
     * Seules passent celles dont la citation a été retrouvée mot pour mot à l'import et qui
     * relèvent du candidat ou de son équipe : la parole d'un parti garde son circuit
     * (« Autour du candidat »). Chaque proposition suit le circuit unitaire, invariants et
     * journal compris ; un refus isolé n'arrête pas le lot.
     *
     * Mode `mesures` : les propositions de type « mesure » deviennent des mesures (non
     * publiées), les autres sont validées sans mesure. Mode `sans_mesure` : toutes paraissent
     * dans les prises de parole, aucune mesure n'est créée.
     *
     * @return array{mesures: int, sans_mesure: int, ignorees: array<string, int>, echecs: list<array{id: int, motif: string}>}
     */
    public function validerDocument(IngestionDocument $document, User $user, string $mode = 'mesures'): array
    {
        if (! in_array($mode, ['mesures', 'sans_mesure'], true)) {
            throw new ModerationException("Mode de validation inconnu « {$mode} ».");
        }

        $bilan = ['mesures' => 0, 'sans_mesure' => 0, 'ignorees' => [], 'echecs' => []];
        $ignorer = function (string $motif) use (&$bilan) {
            $bilan['ignorees'][$motif] = ($bilan['ignorees'][$motif] ?? 0) + 1;
        };
        $commentaire = "Validation de toute la prise de parole #{$document->id}.";

        $propositions = $document->propositions()->where('statut', 'detecte')->orderBy('id')->get();
        foreach ($propositions as $p) {
            if (! $p->verbatim_verifie) {
                $ignorer('citation non confirmée mot pour mot');

                continue;
            }
            if ($p->estEntourage()) {
                $ignorer('parole de l\'entourage (circuit « Autour du candidat »)');

                continue;
            }
            try {
                if ($mode === 'mesures' && $p->type === 'mesure') {
                    $this->creerMesureDepuisProposition($p, $user);
                    $bilan['mesures']++;
                } else {
                    $this->validerSansMesure($p, $user, $commentaire);
                    $bilan['sans_mesure']++;
                }
            } catch (ModerationException $e) {
                $bilan['echecs'][] = ['id' => $p->id, 'motif' => $e->getMessage()];
            }
        }

        return $bilan;
    }

    /**
     * Valide une proposition de l'entourage pour « Autour du candidat ». Elle n'est pas
     * encore publiée : la publication vérifie l'attribution, la citation et l'endossement.
     *
     * @throws ModerationException
     */
    public function validerEntourage(IngestionProposition $p, User $user, ?string $commentaire = null): IngestionProposition
    {
        if (! $p->estEntourage()) {
            throw new ModerationException('Cette proposition relève du candidat ou de son équipe : utilisez « Valider → mesure » ou « Valider sans mesure ».');
        }
        if ($p->statut !== 'detecte') {
            throw new ModerationException('Proposition déjà traitée (statut : '.$p->statut.').');
        }

        $p->update(['statut' => 'validee', 'valide_par' => $user->id, 'valide_at' => now(), 'affiche_publiquement' => false]);
        $this->log($p, 'validation_entourage', 'detecte', 'validee', $user, $commentaire);

        return $p;
    }

    /** @throws ModerationException si ReglesEntourage refuse */
    public function publierEntourage(IngestionProposition $p, User $user): IngestionProposition
    {
        if ($raisons = app(ReglesEntourage::class)->raisons($p)) {
            throw new ModerationException('Publication impossible : '.implode(' ; ', $raisons));
        }

        $p->update(['affiche_publiquement' => true]);
        $this->log($p, 'publication_entourage', 'validee', 'validee', $user);

        return $p;
    }

    public function depublierEntourage(IngestionProposition $p, User $user, ?string $motif = null): IngestionProposition
    {
        $p->update(['affiche_publiquement' => false]);
        $this->log($p, 'depublication_entourage', $p->statut, $p->statut, $user, $motif);

        return $p;
    }

    /**
     * Supprime un cadrage budgétaire (soft-delete, réversible). Refuse un cadrage publié :
     * dépublier d'abord, comme pour les mesures.
     *
     * @throws ModerationException
     */
    public function supprimerCadrage(CadrageBudgetaire $cadrage, User $user, ?string $motif = null): void
    {
        if ($cadrage->affiche_publiquement) {
            throw new ModerationException('Dépubliez le cadrage avant de le supprimer.');
        }

        $this->log($cadrage, 'suppression', $cadrage->statut_validation, null, $user, $motif);
        $cadrage->delete();
    }

    /** Raisons bloquant la publication (vide = publiable). */
    public function raisonsNonPubliable(Model $entite): array
    {
        $raisons = [];

        if ($entite instanceof ProgrammeMesure) {
            if (blank($entite->source_officielle_url)) {
                $raisons[] = 'aucune source officielle';
            }
            // Symétrie via les liaisons publiées (sens porté par la liaison, fiabilité par l'argument).
            $liens = $entite->liens()->publie()->with('argument.sources')->get()
                ->filter(fn ($l) => $l->argument && $l->argument->affiche_publiquement
                    && filled($l->note_contextuelle) && $l->argument->aSourceFiable());
            // La symétrie porte sur l'ARGUMENTAIRE, pas sur la mesure. Une mesure sans
            // aucun argument publié est un relevé sourcé de ce que propose le candidat :
            // il n'y a rien à équilibrer, et rien à taire. Dès qu'un argument est publié,
            // les deux sens redeviennent obligatoires — c'est là, et seulement là, qu'un
            // déséquilibre constituerait une prise de position.
            //
            // La règle précédente ne distinguait pas les deux cas et bloquait donc la
            // publication de 522 mesures sur 523, faute d'argumentaire écrit.
            if ($liens->isNotEmpty()) {
                if ($liens->where('sens', 'pour')->isEmpty()) {
                    $raisons[] = 'argumentaire déséquilibré : aucun argument « pour » publié et sourcé';
                }
                if ($liens->where('sens', 'contre')->isEmpty()) {
                    $raisons[] = 'argumentaire déséquilibré : aucun argument « contre » publié et sourcé';
                }
            }

            // Une mesure du candidat ne vient jamais de la parole d'un parti, et son
            // locuteur, s'il n'est pas le candidat, est nommé avec son rôle.
            if (self::colonne('qualite_locuteur')) {
                foreach ($entite->propositionsSource()->with('document')->get() as $source) {
                    foreach (app(ReglesLocuteur::class)->raisons($source) as $r) {
                        $raisons[] = 'proposition source : '.$r;
                    }
                }
            }
        }

        if ($entite instanceof ProgrammeDocument) {
            $raisons = [...$raisons, ...app(ReglesPorteur::class)->raisons($entite)];
        }

        if ($entite instanceof CadrageBudgetaire) {
            $raisons = [...$raisons, ...app(ReglesCadrageBudgetaire::class)->raisons($entite)];
        }

        if ($entite instanceof Argument && ! $entite->aSourceFiable()) {
            $raisons[] = 'aucune source fiable (haute/moyenne)';
        }

        if ($entite instanceof ArgumentMesureLien) {
            if ($entite->mesure_id === null) {
                $raisons[] = 'liaison non reliée à une mesure (à résoudre)';
            }
            if (blank($entite->note_contextuelle)) {
                $raisons[] = 'note contextuelle manquante';
            }
            $entite->loadMissing('argument');
            if (! $entite->argument || ! $entite->argument->affiche_publiquement) {
                $raisons[] = 'argument non publié';
            }
            if ($entite->sens === 'contre' && ! $entite->double_valide_par) {
                $raisons[] = 'liaison « contre » non doublement validée';
            }
        }

        if ($entite instanceof QuizQuestion) {
            $entite->loadMissing('options.mesures');
            $options = $entite->options;

            // Une option sans mesure publiée derrière est une position que NOUS aurions
            // formulée : le quiz ne doit proposer que des positions réellement défendues.
            $adossees = $options->filter(fn ($o) => $o->mesures
                ->where('affiche_publiquement', true)->isNotEmpty());

            if ($adossees->count() !== $options->count() || $options->isEmpty()) {
                $raisons[] = 'chaque option doit être adossée à au moins une mesure publiée';
            }

            if ($entite->format === 'arbitrage') {
                if ($options->count() < 2) {
                    $raisons[] = 'un arbitrage demande au moins deux options — une seule serait un plébiscite';
                }

                // Un « arbitrage » dont toutes les options viennent du même candidat n'oppose
                // personne : c'est un questionnaire sur une seule offre politique.
                $candidats = $adossees->flatMap(fn ($o) => $o->mesures
                    ->where('affiche_publiquement', true)->pluck('candidat_id'))->unique();
                if ($options->count() >= 2 && $candidats->count() < 2) {
                    $raisons[] = 'toutes les options viennent du même candidat — ce n\'est pas un arbitrage';
                }
            } elseif ($options->count() !== 1) {
                $raisons[] = 'un accord porte sur une seule proposition';
            }
        }

        if ($entite instanceof MesureScrutinLien && blank($entite->explication)) {
            $raisons[] = 'lien scrutin sans explication rédigée';
        }

        // Repères chiffrés : une seule implémentation, partagée avec IntegriteChecker.
        if ($entite instanceof Affirmation) {
            $raisons = [...$raisons, ...app(ReglesAffirmation::class)->raisons($entite)];
        }

        return $raisons;
    }

    private function transitionStatut(Model $entite, string $nouveau, string $action, User $user, ?string $commentaire = null): Model
    {
        $ancien = $entite->statut_validation;
        $entite->statut_validation = $nouveau;
        $entite->save();
        $this->log($entite, $action, $ancien, $nouveau, $user, $commentaire);

        return $entite;
    }

    /**
     * Le code peut être déployé avant la migration : une colonne absente ne doit pas faire
     * échouer un geste sans rapport avec elle.
     */
    private static function colonne(string $colonne): bool
    {
        return Schema::hasColumn('ingestion_propositions', $colonne);
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
