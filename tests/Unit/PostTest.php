<?php

use App\Models\Post;
use App\Models\PostVote;
use App\Models\Topic;
use App\Models\User;

test('post can be a reply', function () {
    $parent = Post::factory()->create();
    $reply = Post::factory()->reply($parent)->create();
    
    expect($reply->parent_id)->toBe($parent->id)
        ->and($reply->parent)->toBeInstanceOf(Post::class)
        ->and($reply->topic_id)->toBe($parent->topic_id);
});

test('post can have multiple replies', function () {
    $parent = Post::factory()->create();
    Post::factory()->count(3)->reply($parent)->create();
    
    expect($parent->replies)->toHaveCount(3);
});

test('post score is calculated correctly', function () {
    $post = Post::factory()->create([
        'upvotes' => 50,
        'downvotes' => 20,
    ]);
    
    expect($post->score)->toBe(30);
});

test('post can be hidden', function () {
    $post = Post::factory()->create();
    
    $post->hide('Contenu inapproprié');
    
    expect($post->is_hidden)->toBeTrue()
        ->and($post->hidden_reason)->toBe('Contenu inapproprié');
});

test('post can be unhidden', function () {
    $post = Post::factory()->hidden()->create();
    
    $post->unhide();
    
    expect($post->is_hidden)->toBeFalse()
        ->and($post->hidden_reason)->toBeNull();
});

test('post can be pinned', function () {
    $post = Post::factory()->pinned()->create();
    
    expect($post->is_pinned)->toBeTrue();
});

test('post can be official', function () {
    $legislator = User::factory()->legislator()->create();
    $post = Post::factory()->official()->create(['user_id' => $legislator->id]);
    
    expect($post->is_official)->toBeTrue();
});

test('post increments upvotes', function () {
    $post = Post::factory()->create(['upvotes' => 5]);
    
    $post->incrementUpvotes();
    
    expect($post->fresh()->upvotes)->toBe(6);
});

test('post increments downvotes', function () {
    $post = Post::factory()->create(['downvotes' => 3]);
    
    $post->incrementDownvotes();
    
    expect($post->fresh()->downvotes)->toBe(4);
});

test('post has votes', function () {
    $post = Post::factory()->create();
    PostVote::factory()->count(5)->create(['post_id' => $post->id]);
    
    expect($post->votes)->toHaveCount(5);
});

test('post visible scope filters hidden posts', function () {
    Post::factory()->visible()->count(3)->create();
    Post::factory()->hidden()->count(2)->create();
    
    expect(Post::visible()->count())->toBe(3);
});

test('post official scope filters non-official posts', function () {
    Post::factory()->official()->count(2)->create();
    Post::factory()->count(3)->create();
    
    expect(Post::official()->count())->toBe(2);
});

test('user can vote on post only once', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();
    
    PostVote::factory()->create([
        'post_id' => $post->id,
        'user_id' => $user->id,
        'vote' => 'up',
    ]);
    
    // Tentative de voter à nouveau
    expect(fn() => PostVote::factory()->create([
        'post_id' => $post->id,
        'user_id' => $user->id,
        'vote' => 'down',
    ]))->toThrow(\Exception::class);
});

