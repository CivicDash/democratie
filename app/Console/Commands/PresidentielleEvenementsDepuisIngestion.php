<?php

namespace App\Console\Commands;

use App\Models\EvenementCampagne;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use Illuminate\Console\Command;

/**
 * Crée les événements de campagne à partir des sources déjà dépouillées.
 *
 * Ne publie JAMAIS : tout entre en `detecte`, non affiché. La date, le lieu et le type
 * proposés doivent être relus au back-office avant validation — c'est pour cela que la
 * commande est en `--dry-run` par défaut.
 *
 * Quand la date ne vient pas d'une colonne mais d'une lecture du titre, une note
 * méthodologique le dit explicitement et suivra jusqu'à l'affichage public.
 */
class PresidentielleEvenementsDepuisIngestion extends Command
{
    protected $signature = 'presidentielle:evenements-depuis-ingestion
        {--election=2027 : élection cible}
        {--apply : écrire réellement (sans ce drapeau, rien n\'est créé)}';

    protected $description = 'Crée les événements de campagne depuis les sources dépouillées (statut detecte).';

    /** Une page de programme n'est pas une prise de parole datée. */
    private const TYPES_EXCLUS = ['programme_officiel'];

    public function handle(): int
    {
        $election = (string) $this->option('election');
        $apply = (bool) $this->option('apply');

        $docs = IngestionDocument::orderBy('id')->get()
            ->reject(fn ($d) => in_array($d->type, self::TYPES_EXCLUS, true));

        $vus = [];   // dédoublonnage par URL : deux documents peuvent pointer la même vidéo
        $crees = 0;
        $ignores = 0;

        foreach ($docs as $doc) {
            if (EvenementCampagne::where('ingestion_document_id', $doc->id)->exists()) {
                $ignores++;

                continue;
            }
            if ($doc->url && isset($vus[$doc->url])) {
                $this->warn("  doublon d'URL ignoré : document #{$doc->id} (déjà vu en #{$vus[$doc->url]})");
                $ignores++;

                continue;
            }
            $vus[$doc->url] = $doc->id;

            [$date, $origine] = $this->date($doc);
            $type = $this->type($doc->titre ?? '');

            $note = $origine === 'titre'
                ? 'Date lue dans le titre de la source, à confirmer avant publication.'
                : null;

            $this->line(sprintf('  #%-3d %-11s %-9s %s', $doc->id, $date ?? '— sans date —', $type,
                mb_strimwidth($doc->titre ?? '', 0, 58, '…')));

            if (! $apply) {
                $crees++;

                continue;
            }

            $evt = EvenementCampagne::create([
                'election' => $election,
                'type' => $type,
                'titre' => $doc->titre ?? 'Sans titre',
                'date_debut' => $date ? $date.' 00:00:00' : now()->toDateString().' 00:00:00',
                'journee_entiere' => true,
                'precision_date' => 'jour',
                'url_video' => str_contains((string) $doc->url, 'youtube') ? $doc->url : null,
                'url_source' => str_contains((string) $doc->url, 'youtube') ? null : $doc->url,
                'ingestion_document_id' => $doc->id,
                'statut' => 'confirme',
                'statut_validation' => 'detecte',
                'affiche_publiquement' => false,
                'note_methodologique' => $note,
            ]);

            // Intervenants : les candidats effectivement cités dans ce document.
            $ids = IngestionProposition::where('document_id', $doc->id)
                ->whereNotNull('candidat_id')->distinct()->pluck('candidat_id');
            $evt->candidats()->syncWithoutDetaching($ids->all());

            $crees++;
        }

        $this->info(($apply ? '' : 'DRY-RUN : ')."{$crees} événement(s), {$ignores} ignoré(s).");
        if (! $apply) {
            $this->line('Relancer avec --apply pour créer. Tout entre en « detecte », rien n\'est publié.');
        }

        return self::SUCCESS;
    }

    /** @return array{0: ?string, 1: string} date ISO et son origine */
    private function date(IngestionDocument $doc): array
    {
        if ($doc->date_publication) {
            return [$doc->date_publication->toDateString(), 'colonne'];
        }
        // Lecture stricte : une seule date dans le titre, sinon on renonce.
        if (preg_match_all('/\b(\d{2})\/(\d{2})\/(\d{4})\b/', (string) $doc->titre, $m, PREG_SET_ORDER) === 1) {
            return ["{$m[0][3]}-{$m[0][2]}-{$m[0][1]}", 'titre'];
        }

        return [null, 'aucune'];
    }

    private function type(string $titre): string
    {
        $t = mb_strtolower($titre);

        return match (true) {
            str_contains($t, 'débat') || str_contains($t, 'debat') => 'debat',
            str_contains($t, 'meeting') => 'meeting',
            str_contains($t, 'discours') => 'discours',
            str_contains($t, 'interview') || str_contains($t, 'entretien') => 'interview',
            default => 'autre',
        };
    }
}
