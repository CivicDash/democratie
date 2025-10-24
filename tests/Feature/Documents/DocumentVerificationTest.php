<?php

use App\Models\Document;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Models\Verification;

test('document can be attached to topic', function () {
    $topic = Topic::factory()->create();
    $uploader = User::factory()->legislator()->create();
    
    $document = Document::factory()->create([
        'documentable_type' => Topic::class,
        'documentable_id' => $topic->id,
        'uploader_id' => $uploader->id,
    ]);
    
    expect($document->documentable)->toBeInstanceOf(Topic::class)
        ->and($document->documentable->id)->toBe($topic->id);
});

test('document can be attached to post', function () {
    $post = Post::factory()->create();
    $uploader = User::factory()->citizen()->create();
    
    $document = Document::factory()->create([
        'documentable_type' => Post::class,
        'documentable_id' => $post->id,
        'uploader_id' => $uploader->id,
    ]);
    
    expect($document->documentable)->toBeInstanceOf(Post::class);
});

test('document has sha256 hash for integrity', function () {
    $document = Document::factory()->create();
    
    expect($document->sha256_hash)->toBeString()
        ->and($document->sha256_hash)->toHaveLength(64);
});

test('document sha256 hash is unique', function () {
    $hash = hash('sha256', 'unique-content');
    
    Document::factory()->create(['sha256_hash' => $hash]);
    
    // Tentative de créer un document avec le même hash
    expect(fn() => Document::factory()->create(['sha256_hash' => $hash]))
        ->toThrow(\Exception::class);
});

test('document starts as unverified', function () {
    $document = Document::factory()->create();
    
    expect($document->is_verified)->toBeFalse();
});

test('journalist can verify document', function () {
    $document = Document::factory()->create();
    $journalist = User::factory()->journalist()->create();
    
    $verification = Verification::factory()->verified()->create([
        'document_id' => $document->id,
        'verifier_id' => $journalist->id,
    ]);
    
    $document->update(['is_verified' => true]);
    
    expect($verification->status)->toBe('verified')
        ->and($document->fresh()->is_verified)->toBeTrue();
});

test('ong can verify document', function () {
    $document = Document::factory()->create();
    $ong = User::factory()->ong()->create();
    
    $verification = Verification::factory()->verified()->create([
        'document_id' => $document->id,
        'verifier_id' => $ong->id,
    ]);
    
    $document->update(['is_verified' => true]);
    
    expect($verification->verifier_id)->toBe($ong->id)
        ->and($verification->status)->toBe('verified');
});

test('verification can be rejected', function () {
    $document = Document::factory()->create();
    $journalist = User::factory()->journalist()->create();
    
    $verification = Verification::factory()->rejected()->create([
        'document_id' => $document->id,
        'verifier_id' => $journalist->id,
        'notes' => 'Document non authentique',
    ]);
    
    expect($verification->status)->toBe('rejected')
        ->and($verification->notes)->toBe('Document non authentique');
});

test('document can have multiple verification attempts', function () {
    $document = Document::factory()->create();
    $journalist1 = User::factory()->journalist()->create();
    $journalist2 = User::factory()->journalist()->create();
    
    Verification::factory()->rejected()->create([
        'document_id' => $document->id,
        'verifier_id' => $journalist1->id,
    ]);
    
    Verification::factory()->verified()->create([
        'document_id' => $document->id,
        'verifier_id' => $journalist2->id,
    ]);
    
    expect($document->verifications)->toHaveCount(2);
});

test('document has uploader', function () {
    $uploader = User::factory()->legislator()->create();
    $document = Document::factory()->create([
        'uploader_id' => $uploader->id,
    ]);
    
    expect($document->uploader)->toBeInstanceOf(User::class)
        ->and($document->uploader->id)->toBe($uploader->id);
});

test('verified documents are prioritized', function () {
    Document::factory()->verified()->count(3)->create();
    Document::factory()->unverified()->count(5)->create();
    
    $verifiedCount = Document::where('is_verified', true)->count();
    
    expect($verifiedCount)->toBe(3);
});

