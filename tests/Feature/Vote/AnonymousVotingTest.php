<?php

use App\Models\BallotToken;
use App\Models\Topic;
use App\Models\TopicBallot;
use App\Models\User;

/**
 * TESTS CRITIQUES POUR L'ANONYMAT DES VOTES
 * 
 * Ces tests vérifient que le système de vote anonyme fonctionne correctement
 * et qu'aucune liaison identité/vote n'est possible.
 */

test('ballot token generates unique tokens', function () {
    $token1 = BallotToken::generateToken();
    $token2 = BallotToken::generateToken();
    
    expect($token1)->toBeString()
        ->and($token1)->toHaveLength(128) // SHA512
        ->and($token2)->toHaveLength(128)
        ->and($token1)->not->toBe($token2);
});

test('ballot token is valid when not consumed and not expired', function () {
    $token = BallotToken::factory()->valid()->create();
    
    expect($token->isValid())->toBeTrue()
        ->and($token->consumed)->toBeFalse()
        ->and($token->expires_at)->toBeGreaterThan(now());
});

test('ballot token is invalid when consumed', function () {
    $token = BallotToken::factory()->consumed()->create();
    
    expect($token->isValid())->toBeFalse()
        ->and($token->consumed)->toBeTrue();
});

test('ballot token is invalid when expired', function () {
    $token = BallotToken::factory()->expired()->create();
    
    expect($token->isValid())->toBeFalse()
        ->and($token->isExpired())->toBeTrue();
});

test('ballot token can be consumed', function () {
    $token = BallotToken::factory()->valid()->create();
    
    $token->consume();
    
    expect($token->fresh()->consumed)->toBeTrue()
        ->and($token->fresh()->consumed_at)->not->toBeNull();
});

test('user can have only one token per topic', function () {
    $topic = Topic::factory()->withBallot()->create();
    $user = User::factory()->create();
    
    BallotToken::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);
    
    // Tentative de créer un deuxième token
    expect(fn() => BallotToken::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]))->toThrow(\Exception::class);
});

/**
 * TEST CRITIQUE : TopicBallot ne doit JAMAIS contenir de user_id
 */
test('topic ballot does NOT have user_id column', function () {
    $ballot = TopicBallot::factory()->create();
    
    expect($ballot)->not->toHaveKey('user_id')
        ->and(array_key_exists('user_id', $ballot->getAttributes()))->toBeFalse();
});

/**
 * TEST CRITIQUE : Impossible de lier un bulletin à un user
 */
test('topic ballot cannot be linked to user', function () {
    $ballot = TopicBallot::factory()->create();
    
    // Vérifier qu'il n'y a pas de méthode user()
    expect(method_exists($ballot, 'user'))->toBeFalse();
});

test('topic ballot encrypts vote', function () {
    $vote = ['choice' => 'yes'];
    $encrypted = TopicBallot::encryptVote($vote);
    
    expect($encrypted)->toBeString()
        ->and($encrypted)->not->toBe(json_encode($vote)); // Chiffré != plaintext
});

test('topic ballot can decrypt vote', function () {
    $originalVote = ['choice' => 'yes'];
    $encrypted = TopicBallot::encryptVote($originalVote);
    
    $ballot = TopicBallot::factory()->create([
        'encrypted_vote' => $encrypted,
    ]);
    
    $decrypted = $ballot->decryptVote();
    
    expect($decrypted)->toBe($originalVote);
});

test('topic ballot has unique vote hash', function () {
    $topic = Topic::factory()->withBallot()->create();
    
    $ballot1 = TopicBallot::factory()->create(['topic_id' => $topic->id]);
    
    // Tentative de créer un bulletin avec le même hash
    expect(fn() => TopicBallot::factory()->create([
        'topic_id' => $topic->id,
        'vote_hash' => $ballot1->vote_hash,
    ]))->toThrow(\Exception::class);
});

/**
 * TEST CRITIQUE : Workflow complet de vote anonyme
 */
test('complete anonymous voting workflow', function () {
    // 1. Setup
    $topic = Topic::factory()->withBallot('yes_no')->create([
        'voting_opens_at' => now()->subHour(),
        'voting_deadline_at' => now()->addHour(),
    ]);
    $user = User::factory()->citizen()->create();
    
    // 2. User obtient un token
    $token = BallotToken::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
        'expires_at' => $topic->voting_deadline_at,
    ]);
    
    expect($token->isValid())->toBeTrue();
    
    // 3. User vote (bulletin SANS user_id)
    $vote = ['choice' => 'yes'];
    $ballot = TopicBallot::cast($topic->id, $vote);
    
    expect($ballot)->toBeInstanceOf(TopicBallot::class)
        ->and($ballot->topic_id)->toBe($topic->id)
        ->and($ballot)->not->toHaveKey('user_id') // CRITIQUE
        ->and($ballot->encrypted_vote)->not->toBeNull()
        ->and($ballot->vote_hash)->not->toBeNull();
    
    // 4. Token consommé
    $token->consume();
    
    expect($token->fresh()->consumed)->toBeTrue()
        ->and($token->fresh()->isValid())->toBeFalse();
    
    // 5. Vérifier que le vote est déchiffrable
    $decrypted = $ballot->decryptVote();
    expect($decrypted)->toBe($vote);
    
    // 6. CRITIQUE : Impossible de retrouver qui a voté
    expect($ballot)->not->toHaveKey('user_id');
});

/**
 * TEST CRITIQUE : User ne peut pas voter deux fois
 */
test('user cannot vote twice on same topic', function () {
    $topic = Topic::factory()->withBallot()->create([
        'voting_opens_at' => now()->subHour(),
        'voting_deadline_at' => now()->addHour(),
    ]);
    $user = User::factory()->citizen()->create();
    
    // Premier vote
    $token1 = BallotToken::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);
    $ballot1 = TopicBallot::cast($topic->id, ['choice' => 'yes']);
    $token1->consume();
    
    // Tentative de second vote (token unique par user/topic)
    expect(fn() => BallotToken::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]))->toThrow(\Exception::class);
});

test('user can vote on different topics', function () {
    $topic1 = Topic::factory()->withBallot()->create();
    $topic2 = Topic::factory()->withBallot()->create();
    $user = User::factory()->citizen()->create();
    
    $token1 = BallotToken::factory()->create([
        'topic_id' => $topic1->id,
        'user_id' => $user->id,
    ]);
    
    $token2 = BallotToken::factory()->create([
        'topic_id' => $topic2->id,
        'user_id' => $user->id,
    ]);
    
    expect($token1->id)->not->toBe($token2->id);
});

test('ballot results can only be revealed after deadline', function () {
    $beforeDeadline = Topic::factory()->withBallot()->create([
        'voting_deadline_at' => now()->addHour(),
    ]);
    
    $afterDeadline = Topic::factory()->withBallot()->create([
        'voting_deadline_at' => now()->subHour(),
    ]);
    
    expect($beforeDeadline->canRevealResults())->toBeFalse()
        ->and($afterDeadline->canRevealResults())->toBeTrue();
});

test('user model has helper methods for voting', function () {
    $topic = Topic::factory()->withBallot()->create([
        'voting_opens_at' => now()->subHour(),
        'voting_deadline_at' => now()->addHour(),
    ]);
    $user = User::factory()->citizen()->create();
    
    // Peut voter (pas encore de token)
    expect($user->canVoteOn($topic))->toBeTrue()
        ->and($user->hasVotedOn($topic))->toBeFalse();
    
    // Crée token et vote
    $token = BallotToken::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);
    TopicBallot::cast($topic->id, ['choice' => 'yes']);
    $token->consume();
    
    // A voté
    expect($user->fresh()->hasVotedOn($topic))->toBeTrue()
        ->and($user->fresh()->canVoteOn($topic))->toBeFalse();
});

