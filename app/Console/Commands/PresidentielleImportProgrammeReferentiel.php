<?php

namespace App\Console\Commands;

use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeDocument;
use App\Models\ProgrammeDocumentItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importe un référentiel de programme officiel (squelette ou complet — plan §11.5).
 * Contrat JSON type_import=programme_referentiel_squelette : document_source +
 * structure (chapitres) + items (sous-pages/mesures avec URLs d'ancre).
 * Upsert par (candidat, url) : ré-import = remplacement des items (programme vivant).
 * Entre en statut detecte — validation humaine avant le bandeau public.
 */
class PresidentielleImportProgrammeReferentiel extends Command
{
    protected $signature = 'presidentielle:import-programme-referentiel {fichier} {--election=2027}';

    protected $description = 'Importe un référentiel de programme officiel (squelette §11.5) en statut detecte.';

    public function handle(): int
    {
        $fichier = $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }
        $data = json_decode((string) file_get_contents($fichier), true);
        $ds = $data['document_source'] ?? null;
        if (! is_array($ds) || empty($ds['candidat_slug']) || empty($ds['url'])) {
            $this->error('JSON invalide : document_source.candidat_slug et .url requis.');

            return self::FAILURE;
        }

        $personne = PersonnePolitique::where('slug', $ds['candidat_slug'])->first();
        $candidat = $personne ? CandidatPresidentielle::where('personne_politique_id', $personne->id)
            ->where('election', $this->option('election'))->first() : null;
        if (! $candidat) {
            $this->error('Candidat introuvable : '.$ds['candidat_slug']);

            return self::FAILURE;
        }

        DB::beginTransaction();
        try {
            $document = ProgrammeDocument::updateOrCreate(
                ['candidat_id' => $candidat->id, 'url' => $ds['url']],
                [
                    'titre' => $ds['titre'] ?? 'Programme officiel',
                    'version' => $data['genere_le'] ?? null,
                    'structure' => $data['structure'] ?? null,
                    'hash_contenu' => hash('sha256', json_encode($data['items'] ?? [])),
                    // ré-import = retour en modération (le contenu a changé)
                    'statut_validation' => 'detecte',
                    'affiche_publiquement' => false,
                ]
            );

            // Remplacement complet des items (référentiel = miroir du document officiel)
            $document->items()->delete();
            $ordre = 0;
            foreach ($data['items'] ?? [] as $item) {
                ProgrammeDocumentItem::create([
                    'document_id' => $document->id,
                    'chapitre_numero' => $item['chapitre'] ?? $item['chapitre_numero'] ?? null,
                    'chapitre_titre' => $item['chapitre_titre'] ?? null,
                    'type' => $item['type'] ?? 'sous_page',
                    'numero' => $item['numero'] ?? null,
                    'titre' => mb_substr((string) ($item['titre'] ?? ''), 0, 500),
                    'texte_court' => isset($item['texte_court']) ? mb_substr($item['texte_court'], 0, 300) : null,
                    'url_ancre' => $item['url'] ?? $item['url_ancre'] ?? null,
                    'ordre' => $ordre++,
                ]);
            }

            DB::commit();
            $nbChapitres = count($data['structure'] ?? []);
            $this->info("Référentiel importé (detecte) : « {$document->titre} » — {$nbChapitres} chapitres, {$ordre} entrées.");
            $this->warn('À valider au back-office avant affichage du bandeau « programme complet » sur la fiche.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec import : '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
