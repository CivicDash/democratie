<?php

namespace App\Console\Commands;

use App\Models\IngestionDocument;
use App\Services\Presidentielle\ImportCadrageBudgetaire;
use App\Support\VerificationVerbatim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import d'un cadrage budgétaire (contrat presidentielle.cadrage_budgetaire.v1).
 *
 * Cas d'usage : le cadrage d'une prise de parole déjà importée (`--document=ID`), un
 * cadrage dont la source n'a pas de propositions (le document est retrouvé par son URL,
 * sinon créé depuis le bloc `source`), ou la ligne de référence officielle (fichier sans
 * candidat, nature `reference_officielle`).
 *
 * Tout entre en `detecte`, non publié, chaque ligne « à vérifier ».
 */
class PresidentielleImportCadrage extends Command
{
    protected $signature = 'presidentielle:import-cadrage
        {fichier : chemin du fichier JSON (contrat cadrage_budgetaire.v1)}
        {--document= : identifiant de la prise de parole source, déjà importée}
        {--source= : transcription de la source, pour vérifier les citations}
        {--election=2027 : élection}
        {--dry-run : analyse complète, toutes écritures annulées}';

    protected $description = 'Importe un cadrage budgétaire (ou la référence officielle) en file de modération.';

    public function handle(ImportCadrageBudgetaire $import): int
    {
        $fichier = $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }
        $data = json_decode((string) file_get_contents($fichier), true);
        if (! is_array($data)) {
            $this->error('JSON invalide : '.json_last_error_msg());

            return self::FAILURE;
        }

        $document = null;
        if ($id = $this->option('document')) {
            $document = IngestionDocument::find($id);
            if (! $document) {
                $this->error("Prise de parole #{$id} introuvable.");

                return self::FAILURE;
            }
        }

        $sourceNorm = null;
        if ($src = $this->option('source')) {
            if (! is_file($src)) {
                $this->error("Fichier source introuvable : {$src}");

                return self::FAILURE;
            }
            $sourceNorm = VerificationVerbatim::normaliser((string) file_get_contents($src));
        }

        $election = (string) $this->option('election');
        // Sans candidat_slug dans le fichier : le candidat du document, ou celui de toutes
        // ses propositions s'il est unique.
        $candidatConnu = $document?->candidat_id;
        if ($document && ! $candidatConnu) {
            $ids = $document->propositions()->whereNotNull('candidat_id')->distinct()->pluck('candidat_id');
            $candidatConnu = $ids->count() === 1 ? (int) $ids->first() : null;
        }

        $erreurs = $import->valider($data, $document?->porteur_type, $election, $candidatConnu);
        if ($erreurs) {
            $this->error(count($erreurs).' erreur(s), rien n\'est importé :');
            foreach ($erreurs as $e) {
                $this->line("  - {$e}");
            }

            return self::FAILURE;
        }
        foreach ($import->avertissements() as $a) {
            $this->warn("  ! {$a}");
        }

        DB::beginTransaction();
        try {
            $stats = $import->ecrire($data, $document, $sourceNorm, $election, $candidatConnu);
            $this->option('dry-run') ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec import, rien n\'est écrit : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(($this->option('dry-run') ? 'DRY-RUN, tout a été annulé. ' : '')
            ."Cadrages : {$stats['cadrages']} (detecte) · lignes : {$stats['lignes']}, toutes à vérifier"
            ." · verbatim confirmé : {$stats['verbatim_verifie']}, absent de la source : {$stats['verbatim_absent']}"
            ." · reliées à une proposition : {$stats['propositions_liees']}"
            .($stats['document_id'] ? ' · document #'.$stats['document_id'].($stats['document_cree'] ? ' (créé)' : '') : ''));

        return self::SUCCESS;
    }
}
