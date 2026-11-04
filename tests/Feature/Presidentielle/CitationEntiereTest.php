<?php

use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Services\Presidentielle\PresidentielleExporter;

/**
 * Une citation de candidat paraît entière, telle qu'elle a été vérifiée mot pour mot.
 * Jusqu'au 04/11/2026, l'export la coupait à 15 mots : une sur deux perdait son sens
 * (« … car la plupart du … »).
 */
it('exporte la citation entière sous la mesure, dans son évolution et dans les prises de parole', function () {
    [$candidat, $theme, $mesure] = candidatPubliePublic();
    $citation = 'quand on dit préférence européenne, on dit en vérité préférence nationale '
        .'car la plupart du temps ce sont des entreprises françaises qui répondent';
    expect(count(preg_split('/\s+/', $citation)))->toBeGreaterThan(15);

    $doc = IngestionDocument::create(['type' => 'video', 'titre' => 'Meeting test', 'statut' => 'extrait',
        'date_publication' => '2026-10-06', 'url' => 'https://www.youtube.com/watch?v=test'.uniqid()]);
    IngestionProposition::create([
        'document_id' => $doc->id, 'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
        'mesure_id' => $mesure->id, 'type' => 'mesure', 'resume_propose' => 'Assume la préférence.',
        'citation_verbatim' => $citation, 'timestamp_ou_paragraphe' => '00:48:16',
        'statut' => 'rattachee', 'confiance' => 0.9, 'verbatim_verifie' => true,
    ]);

    $export = app(PresidentielleExporter::class)->build('2027')['candidats'][$candidat->personnePolitique->slug];
    $m = collect($export['mesures_par_theme'][$theme->slug])->firstWhere('titre', $mesure->titre);

    expect($m['citations'][0]['citation'])->toBe($citation)
        ->and(collect($export['prises_de_parole'])->pluck('citations')->flatten(1)->pluck('citation')->all())
        ->toContain($citation);
});
