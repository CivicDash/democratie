<?php

namespace App\Console\Commands;

use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeTheme;
use App\Services\Presidentielle\ImportCadrageBudgetaire;
use App\Support\Locuteur;
use App\Support\Porteur;
use App\Support\VerificationVerbatim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Import du contrat JSON de propositions (pipeline §11 / mode fallback "Claude chat").
 * TOUT entre en statut `detecte` — jamais publié. Si un fichier source est fourni
 * (--source), chaque citation_verbatim est vérifiée mot-pour-mot (après normalisation
 * SRT) et rejetée si absente. Sans source, les propositions sont insérées avec
 * verbatim_verifie=false (à vérifier humainement).
 *
 * Qui parle : chaque proposition peut nommer son locuteur réel (`locuteur`,
 * `role_locuteur`, `qualite_locuteur`) ; le document, son porteur (`source.porteur`).
 * Un porteur ou une qualité inconnus font échouer l'import sans rien écrire : classer la
 * parole d'un parti comme celle du candidat serait la pire erreur.
 *
 * `--cadrage` importe dans la même transaction le cadrage budgétaire de la même source
 * (contrat presidentielle.cadrage_budgetaire.v1), relié au document créé.
 *
 * `--dry-run` exécute toutes les écritures puis les annule : les contraintes de la base
 * sont donc éprouvées, et rien ne reste.
 */
class PresidentielleImportPropositions extends Command
{
    protected $signature = 'presidentielle:import-propositions
        {fichier : chemin du fichier JSON de propositions}
        {--source= : fichier texte/transcription source pour vérifier les citations verbatim}
        {--cadrage= : fichier JSON du cadrage budgétaire de la même source (contrat cadrage_budgetaire.v1)}
        {--election=2027 : élection cible pour la résolution des candidats}
        {--dry-run : analyse complète, toutes écritures annulées}';

    protected $description = 'Importe des propositions (contrat JSON §11) en file de modération (statut detecte).';

    public function handle(ImportCadrageBudgetaire $importCadrage): int
    {
        $fichier = $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($fichier), true);
        if (! is_array($data) || ! isset($data['propositions']) || ! is_array($data['propositions'])) {
            $this->error('JSON invalide : clé "propositions" (tableau) attendue.');

            return self::FAILURE;
        }

        // Le contrat écrit `source` ; la commande ne lisait que `document_source`.
        // Résultat : chaque import créait un document sans titre, sans URL, sans date
        // ni durée — il fallait le rattraper en SQL après coup, à la main, à chaque
        // fois. Les deux clés sont acceptées pour ne pas casser les anciens fichiers.
        $ds = $data['source'] ?? $data['document_source'] ?? [];

        try {
            [$porteur, $avertissementsPorteur] = Porteur::depuisJson($ds['porteur'] ?? $data['porteur'] ?? null);
        } catch (InvalidArgumentException $e) {
            $this->error('Import refusé, rien n\'est écrit : '.$e->getMessage());

            return self::FAILURE;
        }

        $cadrage = null;
        if ($cheminCadrage = $this->option('cadrage')) {
            if (! is_file($cheminCadrage)) {
                $this->error("Fichier de cadrage introuvable : {$cheminCadrage}");

                return self::FAILURE;
            }
            $cadrage = json_decode((string) file_get_contents($cheminCadrage), true);
            if (! is_array($cadrage)) {
                $this->error('Cadrage : JSON invalide ('.json_last_error_msg().').');

                return self::FAILURE;
            }
        }

        // Source de vérification verbatim (optionnelle mais recommandée)
        $sourceNorm = null;
        if ($src = $this->option('source')) {
            if (! is_file($src)) {
                $this->error("Fichier source introuvable : {$src}");

                return self::FAILURE;
            }
            $sourceNorm = VerificationVerbatim::normaliser((string) file_get_contents($src));
        } else {
            $this->warn('Aucune source fournie (--source) : les citations ne seront pas vérifiées (verbatim_verifie=false).');
        }

        $election = (string) $this->option('election');
        $dryRun = (bool) $this->option('dry-run');

        foreach ($avertissementsPorteur as $a) {
            $this->warn("Porteur : {$a}");
        }

        $stats = ['insere' => 0, 'a_verifier' => 0, 'rejete_verbatim' => 0, 'candidat_inconnu' => 0, 'theme_inconnu' => 0, 'equipe' => 0, 'entourage' => 0, 'sans_role' => 0];
        $statsCadrage = null;

        DB::beginTransaction();
        try {
            // La note méthodologique est la partie la plus utile du fichier : elle dit
            // ce qu'il faut réécouter, quelles erreurs de transcription corriger et
            // quels doublons attendre. Elle était purement et simplement jetée.
            $note = trim(implode("\n\n", array_filter([
                $ds['transcription'] ?? ($ds['avertissement'] ?? null),
                $ds['note_methodologique'] ?? null,
            ])));

            $document = IngestionDocument::create([
                'candidat_id' => $this->candidatUnique($data['propositions'], $election),
                'type' => $ds['type'] ?? 'article',
                'titre' => $ds['titre'] ?? basename($fichier),
                'url' => $ds['url'] ?? null,
                'date_publication' => $ds['date'] ?? ($ds['date_publication'] ?? null),
                'duree_s' => $ds['duree_s'] ?? null,
                'transcription_note' => $note !== '' ? $note : null,
                // La colonne fait 20 caractères : on n'y met pas le nom complet du
                // contrat (« presidentielle.propositions.v1 ») mais sa seule version.
                'contrat_version' => $this->versionContrat($data),
                'generateur' => $data['generateur'] ?? null,
                'statut' => 'extrait',
                'porteur_type' => $porteur['porteur_type'],
                'porteur_libelle' => $porteur['porteur_libelle'],
                'endossement_url' => $porteur['endossement_url'],
                'endossement_date' => $porteur['endossement_date'],
                'endossement_note' => $porteur['endossement_note'],
            ]);

            foreach ($data['propositions'] as $i => $p) {
                $citation = (string) ($p['citation_verbatim'] ?? '');

                // Garde-fou anti-hallucination. Correspondance EXACTE d'abord ; sinon
                // score de similarité par mots (tolère la ponctuation et les micro-écarts
                // des sous-titres automatiques). Trois issues :
                //   - exact ou score ≥ 0.85 -> vérifié (verbatim_verifie = true)
                //   - 0.55 ≤ score < 0.85    -> inséré mais À VÉRIFIER par un humain (false)
                //   - score < 0.55           -> rejet (citation probablement hallucinée)
                $verbatimOk = false;
                $aVerifier = false;
                if ($sourceNorm !== null) {
                    $verdict = VerificationVerbatim::verifier($citation, $sourceNorm);
                    $score = round($verdict['score'] * 100);
                    if ($verdict['issue'] === 'verifiee') {
                        $verbatimOk = true;
                        if (! $verdict['exacte']) {
                            $this->line("  <fg=yellow>≈</> [{$i}] citation approchée ({$score}% — sous-titres auto) : \"".mb_substr($citation, 0, 50).'…"');
                        }
                    } elseif ($verdict['issue'] === 'a_verifier') {
                        $aVerifier = true;
                        $this->line("  <fg=yellow>?</> [{$i}] à vérifier ({$score}%) — insérée non vérifiée");
                    } else {
                        $stats['rejete_verbatim']++;
                        $this->line("  <fg=red>✗</> [{$i}] citation absente de la source ({$score}%) : \"".mb_substr($citation, 0, 50).'…"');

                        continue;
                    }
                }

                // Qui parle : une qualité inconnue lève, et toute l'importation est annulée.
                try {
                    $locuteur = Locuteur::normaliser($p, $p['candidat_slug'] ?? null, $porteur['porteur_type']);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException("proposition [{$i}] : ".$e->getMessage());
                }
                foreach ($locuteur['avertissements'] as $a) {
                    $this->line("  <fg=yellow>!</> [{$i}] {$a}");
                }
                if ($locuteur['locuteur'] && ! $locuteur['role_locuteur']) {
                    $stats['sans_role']++;
                }
                if ($locuteur['qualite_locuteur'] === Locuteur::PARTI || Porteur::estEntourage($porteur['porteur_type'])) {
                    $stats['entourage']++;
                } elseif ($locuteur['qualite_locuteur'] === Locuteur::EQUIPE) {
                    $stats['equipe']++;
                }

                // Résolution candidat (par slug de personne) et thème
                $candidatId = $this->resolveCandidat($p['candidat_slug'] ?? null, $election);
                if (($p['candidat_slug'] ?? null) && ! $candidatId) {
                    $stats['candidat_inconnu']++;
                }
                $themeId = $this->resolveTheme($p['theme_slug'] ?? null);
                if (($p['theme_slug'] ?? null) && ! $themeId) {
                    $stats['theme_inconnu']++;
                }

                IngestionProposition::create([
                    'document_id' => $document->id,
                    'candidat_slug' => $p['candidat_slug'] ?? null,
                    'candidat_id' => $candidatId,
                    'locuteur' => $locuteur['locuteur'],
                    'role_locuteur' => $locuteur['role_locuteur'],
                    'qualite_locuteur' => $locuteur['qualite_locuteur'],
                    'theme_slug' => $p['theme_slug'] ?? null,
                    'theme_id' => $themeId,
                    'type' => $p['type'] ?? 'declaration',
                    'resume_propose' => $p['resume_propose'] ?? '',
                    'citation_verbatim' => $citation,
                    'timestamp_ou_paragraphe' => $p['timestamp_ou_paragraphe'] ?? null,
                    'source_url' => $p['source_url'] ?? null,
                    'confiance' => $p['confiance'] ?? null,
                    'verbatim_verifie' => $verbatimOk,
                    'statut' => 'detecte',
                    'raw_llm_output' => $p,
                ]);
                $stats['insere']++;
                if ($aVerifier) {
                    $stats['a_verifier']++;
                }
            }

            if ($cadrage !== null) {
                $erreurs = $importCadrage->valider($cadrage, $porteur['porteur_type'], $election, $document->candidat_id);
                if ($erreurs) {
                    throw new InvalidArgumentException('cadrage refusé : '.implode(' ; ', array_slice($erreurs, 0, 10))
                        .(count($erreurs) > 10 ? ' ; et '.(count($erreurs) - 10).' autre(s)' : ''));
                }
                foreach ($importCadrage->avertissements() as $a) {
                    $this->line("  <fg=yellow>!</> cadrage — {$a}");
                }
                $statsCadrage = $importCadrage->ecrire($cadrage, $document, $sourceNorm, $election, $document->candidat_id);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('DRY-RUN : tout a été lu et écrit, puis annulé — rien ne reste en base.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec import, rien n\'est écrit : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Propositions insérées (detecte) : {$stats['insere']}");
        $this->line("  dont à vérifier manuellement (verbatim non confirmé) : {$stats['a_verifier']}");
        $this->line("  dont {$stats['equipe']} de l'équipe de campagne (attribuées à leur locuteur) et {$stats['entourage']} de l'entourage (section « Autour du candidat »)");
        if ($stats['sans_role']) {
            $this->line("  <fg=yellow>{$stats['sans_role']} locuteur(s) sans rôle : non publiables tant que le rôle manque</>");
        }
        $this->line("  Rejetées (verbatim absent) : {$stats['rejete_verbatim']}");
        $this->line("  Candidat non résolu : {$stats['candidat_inconnu']} · Thème non résolu : {$stats['theme_inconnu']}");
        if ($statsCadrage) {
            $this->line("  Cadrage budgétaire : {$statsCadrage['cadrages']} cadrage(s), {$statsCadrage['lignes']} ligne(s) à vérifier"
                ." (verbatim confirmé : {$statsCadrage['verbatim_verifie']}, absent : {$statsCadrage['verbatim_absent']},"
                ." reliées à une proposition : {$statsCadrage['propositions_liees']})");
        }

        return self::SUCCESS;
    }

    /** Le candidat de toutes les propositions résolues, s'il est unique. */
    private function candidatUnique(array $propositions, string $election): ?int
    {
        $slugs = array_values(array_unique(array_filter(array_map(fn ($p) => is_array($p) ? ($p['candidat_slug'] ?? null) : null, $propositions))));

        return count($slugs) === 1 ? $this->resolveCandidat($slugs[0], $election) : null;
    }

    /** Résout le candidat de l'élection via le slug de sa personne politique. */
    private function resolveCandidat(?string $slug, string $election): ?int
    {
        if (! $slug) {
            return null;
        }
        $personne = PersonnePolitique::where('slug', $slug)->first();
        if (! $personne) {
            return null;
        }

        return CandidatPresidentielle::where('personne_politique_id', $personne->id)
            ->where('election', $election)
            ->value('id');
    }

    private function resolveTheme(?string $slug): ?int
    {
        if (! $slug) {
            return null;
        }

        return ProgrammeTheme::where('slug', $slug)->value('id');
    }

    /** Version courte du contrat, tenant dans les 20 caractères de la colonne. */
    private function versionContrat(array $data): ?string
    {
        if (! empty($data['contrat_version'])) {
            return mb_substr((string) $data['contrat_version'], 0, 20);
        }

        $contrat = (string) ($data['contrat'] ?? '');
        if ($contrat === '') {
            return null;
        }

        // « presidentielle.propositions.v1 » -> « v1 »
        $morceaux = explode('.', $contrat);

        return mb_substr(end($morceaux) ?: $contrat, 0, 20);
    }
}
