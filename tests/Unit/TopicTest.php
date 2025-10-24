<?php

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;

test('topic can have ballot', function () {
    $topic = Topic::factory()->withBallot('yes_no')->create();
    
    expect($topic->has_ballot)->toBeTrue()
        ->and($topic->ballot_type)->toBe('yes_no')
        ->and($topic->voting_opens_at)->not->toBeNull()
        ->and($topic->voting_deadline_at)->not->toBeNull();
});

test('topic without ballot has null values', function () {
    $topic = Topic::factory()->withoutBallot()->create();
    
    expect($topic->has_ballot)->toBeFalse()
        ->and($topic->ballot_type)->toBeNull()
        ->and($topic->voting_opens_at)->toBeNull()
        ->and($topic->voting_deadline_at)->toBeNull();
});

test('topic voting is open checks dates correctly', function () {
    // Scrutin ouvert
    $openTopic = Topic::factory()->create([
        'has_ballot' => true,
        'voting_opens_at' => now()->subDay(),
        'voting_deadline_at' => now()->addDay(),
    ]);
    
    // Scrutin pas encore ouvert
    $futureTopic = Topic::factory()->create([
        'has_ballot' => true,
        'voting_opens_at' => now()->addDay(),
        'voting_deadline_at' => now()->addDays(2),
    ]);
    
    // Scrutin fermé
    $closedTopic = Topic::factory()->create([
        'has_ballot' => true,
        'voting_opens_at' => now()->subDays(2),
        'voting_deadline_at' => now()->subDay(),
    ]);
    
    expect($openTopic->isVotingOpen())->toBeTrue()
        ->and($futureTopic->isVotingOpen())->toBeFalse()
        ->and($closedTopic->isVotingOpen())->toBeFalse();
});

test('topic can reveal results only after deadline', function () {
    $beforeDeadline = Topic::factory()->create([
        'has_ballot' => true,
        'voting_deadline_at' => now()->addDay(),
    ]);
    
    $afterDeadline = Topic::factory()->create([
        'has_ballot' => true,
        'voting_deadline_at' => now()->subDay(),
    ]);
    
    expect($beforeDeadline->canRevealResults())->toBeFalse()
        ->and($afterDeadline->canRevealResults())->toBeTrue();
});

test('topic has posts', function () {
    $topic = Topic::factory()->create();
    $posts = Post::factory()->count(3)->create(['topic_id' => $topic->id]);
    
    expect($topic->posts)->toHaveCount(3)
        ->and($topic->posts->first())->toBeInstanceOf(Post::class);
});

test('topic belongs to author', function () {
    $author = User::factory()->legislator()->create();
    $topic = Topic::factory()->create(['author_id' => $author->id]);
    
    expect($topic->author)->toBeInstanceOf(User::class)
        ->and($topic->author->id)->toBe($author->id);
});

test('topic types are correct', function () {
    $debate = Topic::factory()->debate()->create();
    $bill = Topic::factory()->bill()->create();
    $referendum = Topic::factory()->referendum()->create();
    
    expect($debate->type)->toBe('debate')
        ->and($bill->type)->toBe('bill')
        ->and($referendum->type)->toBe('referendum');
});

test('topic scopes work correctly', function () {
    Topic::factory()->national()->create();
    Topic::factory()->regional()->create();
    Topic::factory()->departmental()->create();
    
    expect(Topic::national()->count())->toBe(1)
        ->and(Topic::regional()->count())->toBe(1)
        ->and(Topic::departmental()->count())->toBe(1);
});

