<?php

use App\Models\CandidatPresidentielle;

// réutilise le helper candidatPubliePublic() défini dans ApiExportTest.php

it('GET /candidats ne renvoie que les candidats publiés', function () {
    [$candidat] = candidatPubliePublic();
    CandidatPresidentielle::factory()->create(); // non publié

    $resp = $this->getJson('/api/v1/presidentielle/candidats');

    $resp->assertOk()->assertJsonCount(1, 'candidats');
    expect($resp->json('candidats.0.slug'))->toBe($candidat->personnePolitique->slug);
});

it('GET /candidats/{slug} renvoie 200 pour un publié, 404 sinon', function () {
    [$candidat] = candidatPubliePublic();

    $this->getJson("/api/v1/presidentielle/candidats/{$candidat->personnePolitique->slug}")
        ->assertOk()
        ->assertJsonPath('slug', $candidat->personnePolitique->slug)
        ->assertJsonStructure(['mesures_par_theme', 'parcours', 'couverture']);

    $this->getJson('/api/v1/presidentielle/candidats/inconnu')->assertNotFound();
});

it('un candidat non publié renvoie 404 sur sa fiche', function () {
    $candidat = CandidatPresidentielle::factory()->create();

    $this->getJson("/api/v1/presidentielle/candidats/{$candidat->personnePolitique->slug}")
        ->assertNotFound();
});

it('GET /themes renvoie le référentiel et envoie un ETag', function () {
    candidatPubliePublic();

    $resp = $this->getJson('/api/v1/presidentielle/themes');
    $resp->assertOk()->assertJsonStructure(['themes']);
    expect($resp->headers->get('ETag'))->not->toBeNull();
});

it('renvoie 304 quand l’ETag correspond (If-None-Match)', function () {
    candidatPubliePublic();

    $first = $this->getJson('/api/v1/presidentielle/candidats');
    $etag = $first->headers->get('ETag');

    $this->getJson('/api/v1/presidentielle/candidats', ['If-None-Match' => $etag])
        ->assertStatus(304);
});

it('GET /comparateur filtre par candidats et thèmes', function () {
    [$candidat, $theme] = candidatPubliePublic();

    $resp = $this->getJson('/api/v1/presidentielle/comparateur?themes='.$theme->slug);
    $resp->assertOk();
    expect($resp->json('comparateur'))->toHaveKey($theme->slug);
});
