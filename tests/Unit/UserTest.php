<?php

use App\Models\User;

test('user can have profile', function () {
    $user = User::factory()->withProfile()->create();
    
    expect($user->profile)->not->toBeNull()
        ->and($user->profile->user_id)->toBe($user->id);
});

test('user can have multiple sanctions', function () {
    $user = User::factory()->citizen()->create();
    
    \App\Models\Sanction::factory()->count(3)->create(['user_id' => $user->id]);
    
    expect($user->sanctions)->toHaveCount(3);
});

test('user can create topics', function () {
    $user = User::factory()->legislator()->create();
    
    \App\Models\Topic::factory()->count(2)->create(['author_id' => $user->id]);
    
    expect($user->topics)->toHaveCount(2);
});

test('user can create posts', function () {
    $user = User::factory()->citizen()->create();
    
    \App\Models\Post::factory()->count(5)->create(['user_id' => $user->id]);
    
    expect($user->posts)->toHaveCount(5);
});

test('user can vote on posts', function () {
    $user = User::factory()->citizen()->create();
    
    \App\Models\PostVote::factory()->count(10)->create(['user_id' => $user->id]);
    
    expect($user->postVotes)->toHaveCount(10);
});

test('user can have ballot tokens', function () {
    $user = User::factory()->citizen()->create();
    
    \App\Models\BallotToken::factory()->count(3)->create(['user_id' => $user->id]);
    
    expect($user->ballotTokens)->toHaveCount(3);
});

test('user can have budget allocations', function () {
    $user = User::factory()->citizen()->create();
    
    \App\Models\UserAllocation::factory()->count(5)->create(['user_id' => $user->id]);
    
    expect($user->allocations)->toHaveCount(5);
});

test('user can upload documents', function () {
    $user = User::factory()->legislator()->create();
    
    \App\Models\Document::factory()->count(2)->create(['uploader_id' => $user->id]);
    
    expect($user->documents)->toHaveCount(2);
});

test('user can verify documents', function () {
    $user = User::factory()->journalist()->create();
    
    \App\Models\Verification::factory()->count(4)->create(['verifier_id' => $user->id]);
    
    expect($user->verifications)->toHaveCount(4);
});

test('user can create reports', function () {
    $user = User::factory()->citizen()->create();
    
    \App\Models\Report::factory()->count(3)->create(['reporter_id' => $user->id]);
    
    expect($user->reportsCreated)->toHaveCount(3);
});

test('user can handle reports as moderator', function () {
    $user = User::factory()->moderator()->create();
    
    \App\Models\Report::factory()->count(5)->create(['moderator_id' => $user->id]);
    
    expect($user->reportsHandled)->toHaveCount(5);
});

test('user can give sanctions as moderator', function () {
    $user = User::factory()->moderator()->create();
    
    \App\Models\Sanction::factory()->count(2)->create(['moderator_id' => $user->id]);
    
    expect($user->sanctionsGiven)->toHaveCount(2);
});

test('active user is not muted or banned', function () {
    $user = User::factory()->citizen()->create();
    
    expect($user->isMuted())->toBeFalse()
        ->and($user->isBanned())->toBeFalse()
        ->and($user->canPost())->toBeTrue();
});

