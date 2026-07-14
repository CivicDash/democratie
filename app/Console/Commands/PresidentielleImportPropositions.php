<?php

namespace App\Console\Commands;

use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeTheme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import du contrat JSON de propositions (pipeline §11 / mode fallback "Claude chat").
 * TOUT entre en statut `detecte` — jamais publié. Si un fichier source est fourni
 * (--source), chaque citation_verbatim est vérifiée mot-pour-mot (après normalisation
 * SRT) et rejetée si absente. Sans source, les propositions sont insérées avec
 * verbatim_verifie=false (à vérifier humainement).
 */
class PresidentielleImportPropositions extends Command
{
    protected $signature = 'presidentielle:import-propositions
        {fichier : chemin du fichier JSON de propositions}
        {--source= : fichier texte/transcription source pour vérifier les citations verbatim}
        {--election=2027 : élection cible pour la résolution des candidats}
        {--dry-run : analyse sans écrire en base}';

    protected $description = 'Importe des propositions (contrat JSON §11) en file de modération (statut detecte).';

    public function handle(): int
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

        // Source de vérification verbatim (optionnelle mais recommandée)
        $sourceNorm = null;
        if ($src = $this->option('source')) {
            if (! is_file($src)) {
                $this->error("Fichier source introuvable : {$src}");

                return self::FAILURE;
            }
            $sourceNorm = $this->normalize((string) file_get_contents($src));
        } else {
            $this->warn('Aucune source fournie (--source) : les citations ne seront pas vérifiées (verbatim_verifie=false).');
        }

        $election = (string) $this->option('election');
        $dryRun = (bool) $this->option('dry-run');

        $stats = ['insere' => 0, 'rejete_verbatim' => 0, 'candidat_inconnu' => 0, 'theme_inconnu' => 0];

        DB::beginTransaction();
        try {
            $ds = $data['document_source'] ?? [];
            $document = null;
            if (! $dryRun) {
                $document = IngestionDocument::create([
                    'type' => $ds['type'] ?? 'article',
                    'titre' => $ds['titre'] ?? basename($fichier),
                    'url' => $ds['url'] ?? null,
                    'transcription_note' => $ds['transcription'] ?? ($ds['avertissement'] ?? null),
                    'contrat_version' => $data['contrat_version'] ?? null,
                    'generateur' => $data['generateur'] ?? null,
                    'statut' => 'extrait',
                ]);
            }

            foreach ($data['propositions'] as $i => $p) {
                $citation = (string) ($p['citation_verbatim'] ?? '');

                // Garde-fou anti-hallucination : rejet si la citation n'est pas dans la source
                $verbatimOk = false;
                if ($sourceNorm !== null) {
                    $verbatimOk = $citation !== '' && str_contains($sourceNorm, $this->normalize($citation));
                    if (! $verbatimOk) {
                        $stats['rejete_verbatim']++;
                        $this->line("  <fg=red>✗</> [{$i}] citation absente de la source : \"".mb_substr($citation, 0, 60).'…"');

                        continue;
                    }
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

                if (! $dryRun) {
                    IngestionProposition::create([
                        'document_id' => $document->id,
                        'candidat_slug' => $p['candidat_slug'] ?? null,
                        'candidat_id' => $candidatId,
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
                }
                $stats['insere']++;
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('DRY-RUN : aucune écriture.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec import : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Propositions insérées (detecte) : {$stats['insere']}");
        $this->line("  Rejetées (verbatim absent) : {$stats['rejete_verbatim']}");
        $this->line("  Candidat non résolu : {$stats['candidat_inconnu']} · Thème non résolu : {$stats['theme_inconnu']}");

        return self::SUCCESS;
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

    /**
     * Normalise une transcription pour la comparaison verbatim :
     * retire timecodes SRT/VTT, numéros de blocs, uniformise espaces et casse.
     */
    private function normalize(string $s): string
    {
        // Timecodes SRT/VTT : 00:00:00,000 --> 00:00:00,000
        $s = preg_replace('/\d{2}:\d{2}:\d{2}[,.]\d{3}\s*-->\s*\d{2}:\d{2}:\d{2}[,.]\d{3}/u', ' ', $s);
        // Numéros de bloc SRT (ligne seule)
        $s = preg_replace('/^\s*\d+\s*$/m', ' ', $s);
        // Timecodes courts résiduels (01:10, 00:43:21)
        $s = preg_replace('/\b\d{1,2}:\d{2}(:\d{2})?\b/u', ' ', $s);
        $s = mb_strtolower($s, 'UTF-8');
        // Uniformise apostrophes/espaces
        $s = str_replace(['’', "\u{2019}", '«', '»'], ["'", "'", ' ', ' '], $s);
        $s = preg_replace('/\s+/u', ' ', $s);

        return trim((string) $s);
    }
}
