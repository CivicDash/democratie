<?php

use App\Models\Post;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\Topic;
use App\Models\User;

/**
 * TESTS POUR LE SYSTÈME DE MODÉRATION
 * 
 * Workflow : Signalement → Révision → Sanction (optionnelle)
 */

test('user can report a post', function () {
    $post = Post::factory()->create();
    $reporter = User::factory()->citizen()->create();
    
    $report = Report::factory()->create([
        'reportable_type' => Post::class,
        'reportable_id' => $post->id,
        'reporter_id' => $reporter->id,
        'reason' => 'Contenu inapproprié',
    ]);
    
    expect($report->reportable)->toBeInstanceOf(Post::class)
        ->and($report->reporter)->toBeInstanceOf(User::class)
        ->and($report->status)->toBe('pending');
});

test('user can report a topic', function () {
    $topic = Topic::factory()->create();
    $reporter = User::factory()->citizen()->create();
    
    $report = Report::factory()->create([
        'reportable_type' => Topic::class,
        'reportable_id' => $topic->id,
        'reporter_id' => $reporter->id,
        'reason' => 'Spam',
    ]);
    
    expect($report->reportable)->toBeInstanceOf(Topic::class)
        ->and($report->status)->toBe('pending');
});

test('report starts as pending', function () {
    $report = Report::factory()->create();
    
    expect($report->status)->toBe('pending')
        ->and($report->moderator_id)->toBeNull()
        ->and($report->moderator_notes)->toBeNull();
});

test('moderator can review report', function () {
    $report = Report::factory()->pending()->create();
    $moderator = User::factory()->moderator()->create();
    
    $report->update([
        'status' => 'reviewing',
        'moderator_id' => $moderator->id,
    ]);
    
    expect($report->fresh()->status)->toBe('reviewing')
        ->and($report->fresh()->moderator_id)->toBe($moderator->id);
});

test('moderator can resolve report', function () {
    $report = Report::factory()->reviewing()->create();
    
    $report->resolve('Aucune violation constatée');
    
    expect($report->fresh()->status)->toBe('resolved')
        ->and($report->fresh()->moderator_notes)->toBe('Aucune violation constatée');
});

test('moderator can reject report', function () {
    $report = Report::factory()->reviewing()->create();
    
    $report->reject('Signalement non fondé');
    
    expect($report->fresh()->status)->toBe('rejected')
        ->and($report->fresh()->moderator_notes)->toBe('Signalement non fondé');
});

test('post can be hidden after report', function () {
    $post = Post::factory()->create();
    $report = Report::factory()->create([
        'reportable_type' => Post::class,
        'reportable_id' => $post->id,
    ]);
    
    $post->hide('Violation de la charte');
    
    expect($post->fresh()->is_hidden)->toBeTrue()
        ->and($post->fresh()->hidden_reason)->toBe('Violation de la charte');
});

test('moderator can issue warning to user', function () {
    $user = User::factory()->citizen()->create();
    $moderator = User::factory()->moderator()->create();
    
    $sanction = Sanction::factory()->warning()->create([
        'user_id' => $user->id,
        'moderator_id' => $moderator->id,
        'reason' => 'Premier avertissement pour comportement inapproprié',
    ]);
    
    expect($sanction->type)->toBe('warning')
        ->and($sanction->user_id)->toBe($user->id)
        ->and($sanction->moderator_id)->toBe($moderator->id)
        ->and($sanction->is_active)->toBeTrue();
});

test('moderator can mute user temporarily', function () {
    $user = User::factory()->citizen()->create();
    $moderator = User::factory()->moderator()->create();
    
    $sanction = Sanction::factory()->mute()->create([
        'user_id' => $user->id,
        'moderator_id' => $moderator->id,
        'expires_at' => now()->addDays(7),
        'reason' => 'Mute 7 jours pour spam',
    ]);
    
    expect($sanction->type)->toBe('mute')
        ->and($sanction->isExpired())->toBeFalse()
        ->and($sanction->is_active)->toBeTrue();
});

test('moderator can ban user permanently', function () {
    $user = User::factory()->citizen()->create();
    $moderator = User::factory()->moderator()->create();
    
    $sanction = Sanction::factory()->ban()->create([
        'user_id' => $user->id,
        'moderator_id' => $moderator->id,
        'expires_at' => null, // Permanent
        'reason' => 'Ban permanent pour violations répétées',
    ]);
    
    expect($sanction->type)->toBe('ban')
        ->and($sanction->expires_at)->toBeNull()
        ->and($sanction->is_active)->toBeTrue();
});

test('sanction expires after deadline', function () {
    $sanction = Sanction::factory()->create([
        'expires_at' => now()->addHour(),
    ]);
    
    expect($sanction->isExpired())->toBeFalse();
    
    // Simulate time passing
    $sanction->update(['expires_at' => now()->subHour()]);
    
    expect($sanction->fresh()->isExpired())->toBeTrue();
});

test('sanction can be revoked', function () {
    $sanction = Sanction::factory()->active()->create();
    
    $sanction->revoke();
    
    expect($sanction->fresh()->revoked_at)->not->toBeNull()
        ->and($sanction->fresh()->is_active)->toBeFalse();
});

test('user model knows if user is muted', function () {
    $user = User::factory()->citizen()->create();
    
    expect($user->isMuted())->toBeFalse();
    
    Sanction::factory()->mute()->create([
        'user_id' => $user->id,
        'expires_at' => now()->addHour(),
    ]);
    
    expect($user->fresh()->isMuted())->toBeTrue();
});

test('user model knows if user is banned', function () {
    $user = User::factory()->citizen()->create();
    
    expect($user->isBanned())->toBeFalse();
    
    Sanction::factory()->ban()->create([
        'user_id' => $user->id,
    ]);
    
    expect($user->fresh()->isBanned())->toBeTrue();
});

test('muted user cannot post', function () {
    $user = User::factory()->citizen()->create();
    
    Sanction::factory()->mute()->create([
        'user_id' => $user->id,
        'expires_at' => now()->addHour(),
    ]);
    
    expect($user->fresh()->canPost())->toBeFalse();
});

test('banned user cannot post', function () {
    $user = User::factory()->citizen()->create();
    
    Sanction::factory()->ban()->create([
        'user_id' => $user->id,
    ]);
    
    expect($user->fresh()->canPost())->toBeFalse();
});

test('user with expired sanction can post again', function () {
    $user = User::factory()->citizen()->create();
    
    $sanction = Sanction::factory()->mute()->create([
        'user_id' => $user->id,
        'expires_at' => now()->subHour(), // Expiré
    ]);
    
    expect($user->fresh()->canPost())->toBeTrue();
});

test('user can have multiple sanctions over time', function () {
    $user = User::factory()->citizen()->create();
    $moderator = User::factory()->moderator()->create();
    
    // Premier avertissement
    Sanction::factory()->warning()->create([
        'user_id' => $user->id,
        'moderator_id' => $moderator->id,
    ]);
    
    // Second avertissement
    Sanction::factory()->warning()->create([
        'user_id' => $user->id,
        'moderator_id' => $moderator->id,
    ]);
    
    // Mute
    Sanction::factory()->mute()->create([
        'user_id' => $user->id,
        'moderator_id' => $moderator->id,
    ]);
    
    expect($user->sanctions)->toHaveCount(3);
});

test('user model counts active reports', function () {
    $user = User::factory()->citizen()->create();
    
    expect($user->activeReportsCount())->toBe(0);
    
    Report::factory()->pending()->create(['reporter_id' => $user->id]);
    Report::factory()->reviewing()->create(['reporter_id' => $user->id]);
    Report::factory()->resolved()->create(['reporter_id' => $user->id]);
    
    expect($user->fresh()->activeReportsCount())->toBe(2); // pending + reviewing
});

test('post with multiple reports is prioritized', function () {
    $post = Post::factory()->create();
    
    Report::factory()->count(5)->create([
        'reportable_type' => Post::class,
        'reportable_id' => $post->id,
    ]);
    
    $reportsCount = Report::where('reportable_type', Post::class)
        ->where('reportable_id', $post->id)
        ->count();
    
    expect($reportsCount)->toBe(5);
});

test('moderator can see reports they are handling', function () {
    $moderator = User::factory()->moderator()->create();
    
    Report::factory()->count(3)->create([
        'moderator_id' => $moderator->id,
        'status' => 'reviewing',
    ]);
    
    Report::factory()->count(2)->create([
        'status' => 'pending',
    ]);
    
    expect($moderator->reportsHandled)->toHaveCount(3);
});

test('user can see reports they created', function () {
    $user = User::factory()->citizen()->create();
    
    Report::factory()->count(2)->create(['reporter_id' => $user->id]);
    
    expect($user->reportsCreated)->toHaveCount(2);
});

