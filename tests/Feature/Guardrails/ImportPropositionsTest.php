<?php

use App\Models\IngestionDocument;
use Illuminate\Support\Facades\File;

/**
 * L'import doit enregistrer les métadonnées de la source, pas seulement les propositions.
 *
 * La commande lisait `document_source` alors que le contrat écrit `source` : chaque
 * import créait un document sans titre, sans URL, sans date ni durée, qu'il fallait
 * rattraper en SQL à la main — et la note méthodologique, qui dit quoi réécouter et
 * quelles erreurs de transcription corriger, était jetée.
 */
it('enregistre titre, url, date, durée et note de la source', function () {
    $srt = tempnam(sys_get_temp_dir(), 'srt');
    File::put($srt, "1\n00:00:10,000 --> 00:00:14,000\nune citation parfaitement vérifiable\n");

    $json = tempnam(sys_get_temp_dir(), 'json');
    File::put($json, json_encode([
        'contrat' => 'presidentielle.propositions.v1',
        'source' => [
            'titre' => 'Discours de test',
            'type' => 'meeting',
            'url' => 'https://exemple.fr/video',
            'date' => '2026-09-12',
            'duree_s' => 5044,
            'transcription' => 'sous-titres auto-générés',
            'note_methodologique' => 'À réécouter au timecode avant publication.',
        ],
        'propositions' => [],
    ], JSON_UNESCAPED_UNICODE));

    $this->artisan('presidentielle:import-propositions', [
        'fichier' => $json,
        '--source' => $srt,
        '--election' => '2027',
    ])->assertSuccessful();

    $document = IngestionDocument::latest('id')->first();

    expect($document)->not->toBeNull()
        ->and($document->titre)->toBe('Discours de test')
        ->and($document->type)->toBe('meeting')
        ->and($document->url)->toBe('https://exemple.fr/video')
        ->and($document->duree_s)->toBe(5044)
        ->and($document->date_publication?->toDateString())->toBe('2026-09-12')
        ->and($document->contrat_version)->toBe('v1')
        ->and($document->transcription_note)->toContain('À réécouter au timecode');

    File::delete([$srt, $json]);
});

it('accepte encore l\'ancienne clé document_source', function () {
    $json = tempnam(sys_get_temp_dir(), 'json');
    File::put($json, json_encode([
        'document_source' => ['titre' => 'Ancien format', 'type' => 'article'],
        'propositions' => [],
    ]));

    $this->artisan('presidentielle:import-propositions', ['fichier' => $json])->assertSuccessful();

    expect(IngestionDocument::latest('id')->first()->titre)->toBe('Ancien format');

    File::delete($json);
});
