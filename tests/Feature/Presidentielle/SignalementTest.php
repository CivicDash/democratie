<?php

use App\Mail\SignalementPresidentielleMail;
use App\Models\PresidentielleModerationLog;
use App\Models\PresidentielleSignalement;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;

function moderateurSignalement(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

// ---- Endpoint public (création) ----

it('crée un signalement citoyen depuis l\'endpoint public (sans auth)', function () {
    $res = $this->postJson('/api/v1/presidentielle/signalements', [
        'type_incident' => 'inexactitude_proposition',
        'description' => 'La mesure X est mal attribuée à ce candidat.',
        'candidat_slug' => 'jean-luc-melenchon',
        'contexte_url' => 'https://objectif2027.fr/candidats/jean-luc-melenchon',
    ]);

    $res->assertCreated()->assertJson(['ok' => true]);
    expect(PresidentielleSignalement::count())->toBe(1);
    $s = PresidentielleSignalement::first();
    expect($s->statut)->toBe('nouveau')
        ->and($s->candidat_slug)->toBe('jean-luc-melenchon')
        ->and($s->email)->toBeNull();
});

it('notifie le staff par email (en file) à la réception', function () {
    Mail::fake();
    config()->set('presidentielle.signalement_notify', ['secretaire@civis-consilium.eu', 'president@civis-consilium.eu']);

    $this->postJson('/api/v1/presidentielle/signalements', [
        'type_incident' => 'affaire_judiciaire',
        'description' => 'Le statut judiciaire affiché ne correspond pas à la décision.',
    ])->assertCreated();

    Mail::assertQueued(SignalementPresidentielleMail::class, function ($mail) {
        return $mail->hasTo('secretaire@civis-consilium.eu') && $mail->hasTo('president@civis-consilium.eu');
    });
});

it('n\'envoie aucune notification si la liste de destinataires est vide', function () {
    Mail::fake();
    config()->set('presidentielle.signalement_notify', []);

    $this->postJson('/api/v1/presidentielle/signalements', [
        'type_incident' => 'source_erronee', 'description' => 'Un lien de source est cassé sur cette fiche.',
    ])->assertCreated();

    Mail::assertNothingQueued();
});

it('accepte un email facultatif mais rejette un email invalide', function () {
    $this->postJson('/api/v1/presidentielle/signalements', [
        'type_incident' => 'source_erronee', 'description' => 'Le lien de la source est mort.',
        'email' => 'citoyen@example.org',
    ])->assertCreated();

    $this->postJson('/api/v1/presidentielle/signalements', [
        'type_incident' => 'source_erronee', 'description' => 'Encore un lien mort ici.',
        'email' => 'pas-un-email',
    ])->assertStatus(422);

    expect(PresidentielleSignalement::count())->toBe(1);
});

it('exige un type valide et une description', function () {
    $this->postJson('/api/v1/presidentielle/signalements', [
        'type_incident' => 'inconnu', 'description' => 'assez long pour passer',
    ])->assertStatus(422);

    $this->postJson('/api/v1/presidentielle/signalements', [
        'type_incident' => 'affaire_judiciaire', 'description' => 'court',
    ])->assertStatus(422);

    expect(PresidentielleSignalement::count())->toBe(0);
});

it('ignore silencieusement les bots via le honeypot (aucune écriture)', function () {
    $this->postJson('/api/v1/presidentielle/signalements', [
        'type_incident' => 'inexactitude_proposition',
        'description' => 'Ceci est un spam automatisé quelconque.',
        'site_web' => 'http://spam.example',   // honeypot rempli
    ])->assertCreated()->assertJson(['ok' => true]);

    expect(PresidentielleSignalement::count())->toBe(0);
});

// ---- Back-office (traitement) ----

it('liste les signalements et les traite (prise en charge → résolution), journalisé', function () {
    $mod = moderateurSignalement();
    $s = PresidentielleSignalement::factory()->create(['statut' => 'nouveau']);

    $this->actingAs($mod)->withHeader('X-Inertia', 'true')
        ->get('/admin/presidentielle/signalements')->assertOk();

    $this->actingAs($mod)->post(route('admin.presidentielle.signalements.action'), [
        'id' => $s->id, 'action' => 'prendre_en_charge',
    ])->assertSessionHasNoErrors();
    expect($s->fresh()->statut)->toBe('en_cours')->and($s->fresh()->moderator_id)->toBe($mod->id);

    $this->actingAs($mod)->post(route('admin.presidentielle.signalements.action'), [
        'id' => $s->id, 'action' => 'resoudre', 'note' => 'Corrigé et republié.',
    ])->assertSessionHasNoErrors();
    $s->refresh();
    expect($s->statut)->toBe('resolu')
        ->and($s->resolved_at)->not->toBeNull()
        ->and($s->resolution_note)->toBe('Corrigé et republié.');

    expect(PresidentielleModerationLog::where('entite_id', $s->id)
        ->where('entite_type', $s->getMorphClass())->count())->toBe(2);
});

it('refuse l\'accès BO sans la permission (403)', function () {
    PresidentielleSignalement::factory()->create();
    $this->actingAs(User::factory()->create())
        ->get('/admin/presidentielle/signalements')->assertForbidden();
});
