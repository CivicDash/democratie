<?php

use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\DataGouvController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\LegislationController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\VoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - CivicDash
|--------------------------------------------------------------------------
|
| Routes API pour la plateforme démocratique CivicDash.
| Toutes les routes sont préfixées par /api et retournent du JSON.
|
*/

// ============================================================================
// ROUTES PUBLIQUES (sans authentification)
// ============================================================================

// Topics
Route::get('/topics', [TopicController::class, 'index']);
Route::get('/topics/trending', [TopicController::class, 'trending']);
Route::get('/topics/{topic}', [TopicController::class, 'show']);
Route::get('/topics/{topic}/stats', [TopicController::class, 'stats']);

// Posts
Route::get('/topics/{topic}/posts', [PostController::class, 'index']);
Route::get('/topics/{topic}/posts/top', [PostController::class, 'top']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/posts/{post}/replies', [PostController::class, 'replies']);

// Vote - routes publiques
Route::get('/topics/{topic}/vote/results', [VoteController::class, 'results']);
Route::get('/topics/{topic}/vote/count', [VoteController::class, 'count']);

// Budget - routes publiques
Route::get('/budget/sectors', [BudgetController::class, 'sectors']);
Route::get('/budget/averages', [BudgetController::class, 'averages']);
Route::get('/budget/ranking', [BudgetController::class, 'ranking']);
Route::get('/budget/stats', [BudgetController::class, 'stats']);
Route::post('/budget/simulate', [BudgetController::class, 'simulate']);
Route::post('/budget/compare', [BudgetController::class, 'compare']);

// Documents - routes publiques
Route::get('/documents', [DocumentController::class, 'index']);
Route::get('/documents/{document}', [DocumentController::class, 'show']);
Route::get('/documents/{document}/verifications', [DocumentController::class, 'verifications']);
Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
Route::get('/documents/stats', [DocumentController::class, 'stats']);
Route::get('/documents/top-verifiers', [DocumentController::class, 'topVerifiers']);

// ============================================================================
// RECHERCHE MEILISEARCH - Routes publiques
// ============================================================================

Route::prefix('search')->group(function () {
    // Recherche globale
    Route::get('/', [SearchController::class, 'search']);
    
    // Autocomplete / Suggestions
    Route::get('/autocomplete', [SearchController::class, 'autocomplete']);
    
    // Statistiques
    Route::get('/stats', [SearchController::class, 'stats']);
});

// ============================================================================
// DATA.GOUV.FR - Routes publiques
// ============================================================================

Route::prefix('datagouv')->group(function () {
    // Budget territorial
    Route::get('/commune/{codeInsee}/budget/{annee?}', [DataGouvController::class, 'getCommuneBudget']);
    Route::get('/communes/compare', [DataGouvController::class, 'compareBudgets']);
    Route::get('/communes/search', [DataGouvController::class, 'searchCommunes']);
    Route::get('/project/context', [DataGouvController::class, 'getProjectContext']);
    
    // Statistiques
    Route::get('/stats', [DataGouvController::class, 'getStats']);
});

// ============================================================================
// LÉGISLATION - Routes publiques (Assemblée + Sénat)
// ============================================================================

Route::prefix('legislation')->group(function () {
    // Propositions de loi
    Route::get('/propositions', [LegislationController::class, 'getPropositions']);
    Route::get('/propositions/local', [LegislationController::class, 'getPropositionsLocales']);
    Route::get('/propositions/trending', [LegislationController::class, 'getTrendingPropositions']);
    Route::get('/propositions/{source}/{numero}', [LegislationController::class, 'getPropositionDetail']);
    Route::get('/propositions/{source}/{numero}/amendements', [LegislationController::class, 'getAmendements']);
    Route::get('/propositions/{source}/{numero}/votes', [LegislationController::class, 'getVotes']);
    
    // 🔥 KILLER FEATURE: Comparaison avec propositions citoyennes
    Route::post('/find-similar', [LegislationController::class, 'findSimilar']);
    
    // 👍👎 VOTES CITOYENS (routes publiques pour consultation)
    Route::get('/propositions/{id}/votes/stats', [LegislationController::class, 'getVoteStats']);
    
    // Agenda législatif
    Route::get('/agenda', [LegislationController::class, 'getAgenda']);
    
    // Statistiques
    Route::get('/stats', [LegislationController::class, 'getStatistiques']);
    
    // Élus (députés & sénateurs)
    Route::get('/elus/search', [LegislationController::class, 'searchElus']);
    Route::get('/elus/{uid}', [LegislationController::class, 'getEluDetail']);
});

// ============================================================================
// ROUTES AUTHENTIFIÉES
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {
    
    // ========================================================================
    // TOPICS
    // ========================================================================
    Route::post('/topics', [TopicController::class, 'store']);
    Route::put('/topics/{topic}', [TopicController::class, 'update']);
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy']);
    Route::post('/topics/{topic}/close', [TopicController::class, 'close']);
    Route::post('/topics/{topic}/archive', [TopicController::class, 'archive']);
    Route::post('/topics/{topic}/ballot', [TopicController::class, 'createBallot']);
    
    // ========================================================================
    // POSTS
    // ========================================================================
    Route::post('/topics/{topic}/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/vote', [PostController::class, 'vote']);
    
    // ========================================================================
    // VOTE ANONYME
    // ========================================================================
    Route::prefix('topics/{topic}/vote')->group(function () {
        Route::post('/token', [VoteController::class, 'requestToken']);
        Route::post('/cast', [VoteController::class, 'castVote']);
        Route::get('/has-voted', [VoteController::class, 'hasVoted']);
    });
    
    // Vote - routes admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/topics/{topic}/vote/integrity', [VoteController::class, 'verifyIntegrity']);
    });
    
    // Vote - routes admin/state
    Route::middleware('role:admin|state')->group(function () {
        Route::get('/topics/{topic}/vote/export', [VoteController::class, 'export']);
    });
    
    // ========================================================================
    // BUDGET PARTICIPATIF
    // ========================================================================
    Route::prefix('budget')->group(function () {
        Route::get('/allocations', [BudgetController::class, 'index']);
        Route::post('/allocate', [BudgetController::class, 'allocate']);
        Route::post('/bulk-allocate', [BudgetController::class, 'bulkAllocate']);
        Route::delete('/reset', [BudgetController::class, 'reset']);
    });
    
    // Budget - routes admin/state
    Route::middleware('role:admin|state')->group(function () {
        Route::get('/budget/export', [BudgetController::class, 'export']);
    });
    
    // ========================================================================
    // MODÉRATION
    // ========================================================================
    
    // Signalements - tous les utilisateurs authentifiés
    Route::post('/moderation/reports', [ModerationController::class, 'storeReport']);
    
    // Modération - routes modérateurs/admins
    Route::middleware('role:moderator|admin')->prefix('moderation')->group(function () {
        // Reports
        Route::get('/reports', [ModerationController::class, 'reports']);
        Route::get('/reports/priority', [ModerationController::class, 'priorityReports']);
        Route::post('/reports/{report}/assign', [ModerationController::class, 'assignReport']);
        Route::post('/reports/{report}/resolve', [ModerationController::class, 'resolveReport']);
        Route::post('/reports/{report}/reject', [ModerationController::class, 'rejectReport']);
        
        // Sanctions
        Route::get('/users/{user}/sanctions', [ModerationController::class, 'userSanctions']);
        Route::post('/users/{user}/sanctions', [ModerationController::class, 'storeSanction']);
        Route::delete('/sanctions/{sanction}', [ModerationController::class, 'revokeSanction']);
        
        // Stats
        Route::get('/stats', [ModerationController::class, 'stats']);
        Route::get('/top-moderators', [ModerationController::class, 'topModerators']);
    });
    
    // ========================================================================
    // DOCUMENTS
    // ========================================================================
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::put('/documents/{document}', [DocumentController::class, 'update']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
    
    // Vérification - journalistes/ONGs
    Route::middleware('role:journalist|ong|admin')->group(function () {
        Route::post('/documents/{document}/verify', [DocumentController::class, 'verify']);
        Route::get('/documents/pending', [DocumentController::class, 'pending']);
    });
    
    // ========================================================================
    // LÉGISLATION - Routes authentifiées (votes citoyens)
    // ========================================================================
    Route::prefix('legislation/propositions')->group(function () {
        // 👍👎 Voter sur une proposition
        Route::post('/{id}/vote', [LegislationController::class, 'voteProposition']);
        Route::delete('/{id}/vote', [LegislationController::class, 'removeVoteProposition']);
        Route::get('/{id}/my-vote', [LegislationController::class, 'getMyVote']);
    });
    
    // ========================================================================
    // DATA.GOUV.FR - Routes admin (invalidation cache)
    // ========================================================================
    Route::middleware('role:admin')->prefix('datagouv')->group(function () {
        Route::delete('/cache/commune/{codeInsee}', [DataGouvController::class, 'invalidateCommuneCache']);
    });
});

/*
|--------------------------------------------------------------------------
| Routes de secours pour les erreurs
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint introuvable. Vérifiez l\'URL et la méthode HTTP.',
    ], 404);
});

