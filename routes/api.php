<?php

use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\DataGouvController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\LegislationController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\PostalCodeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RepresentantsSearchController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\V1\ActeursANController;
use App\Http\Controllers\Api\V1\ScrutinsANController;
use App\Http\Controllers\Api\V1\AmendementsANController;
use App\Http\Controllers\Api\V1\SenateursController;
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

// Codes postaux - routes publiques
Route::get('/postal-codes/search', [PostalCodeController::class, 'search']);
Route::get('/postal-codes/{postalCode}', [PostalCodeController::class, 'show']);
Route::get('/postal-codes/department/{departmentCode}', [PostalCodeController::class, 'byDepartment']);
Route::get('/postal-codes/circonscription/{circonscription}', [PostalCodeController::class, 'byCirconscription']);

// Recherche de représentants (maire, député, sénateur) - routes publiques
Route::get('/representants/search', [RepresentantsSearchController::class, 'search']);

// ============================================================================
// API V1 - DONNÉES PARLEMENTAIRES (AN + SÉNAT)
// ============================================================================

Route::prefix('v1')->name('v1.')->group(function () {
    
    // ========================================================================
    // ACTEURS AN (Députés)
    // ========================================================================
    Route::prefix('acteurs')->name('acteurs.')->group(function () {
        Route::get('/', [ActeursANController::class, 'index'])->name('index');
        Route::get('/{uid}', [ActeursANController::class, 'show'])->name('show');
        Route::get('/{uid}/votes', [ActeursANController::class, 'votes'])->name('votes');
        Route::get('/{uid}/amendements', [ActeursANController::class, 'amendements'])->name('amendements');
        Route::get('/{uid}/stats', [ActeursANController::class, 'stats'])->name('stats');
    });
    
    // ========================================================================
    // SCRUTINS AN
    // ========================================================================
    Route::prefix('scrutins')->name('scrutins.')->group(function () {
        Route::get('/', [ScrutinsANController::class, 'index'])->name('index');
        Route::get('/{uid}', [ScrutinsANController::class, 'show'])->name('show');
        Route::get('/{uid}/votes', [ScrutinsANController::class, 'votes'])->name('votes');
        Route::get('/{uid}/stats-par-groupe', [ScrutinsANController::class, 'statsParGroupe'])->name('stats_par_groupe');
    });
    
    // ========================================================================
    // AMENDEMENTS AN
    // ========================================================================
    Route::prefix('amendements')->name('amendements.')->group(function () {
        Route::get('/', [AmendementsANController::class, 'index'])->name('index');
        Route::get('/stats', [AmendementsANController::class, 'stats'])->name('stats');
        Route::get('/{uid}', [AmendementsANController::class, 'show'])->name('show');
    });
    
    // ========================================================================
    // SÉNATEURS
    // ========================================================================
    Route::prefix('senateurs')->name('senateurs.')->group(function () {
        Route::get('/', [SenateursController::class, 'index'])->name('index');
        Route::get('/stats', [SenateursController::class, 'stats'])->name('stats');
        Route::get('/{matricule}', [SenateursController::class, 'show'])->name('show');
        Route::get('/{matricule}/mandats', [SenateursController::class, 'mandats'])->name('mandats');
        Route::get('/{matricule}/commissions', [SenateursController::class, 'commissions'])->name('commissions');
        Route::get('/{matricule}/groupes', [SenateursController::class, 'groupes'])->name('groupes');
    });
});

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

// ============================================================================
// GROUPES PARLEMENTAIRES
// ============================================================================

// Routes publiques
Route::prefix('groupes-parlementaires')->name('groupes.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\GroupesParlementairesController::class, 'index'])->name('index');
    Route::get('/comparaison', [App\Http\Controllers\Api\GroupesParlementairesController::class, 'comparaison'])->name('comparaison');
    Route::get('/{id}', [App\Http\Controllers\Api\GroupesParlementairesController::class, 'show'])->name('show');
    Route::get('/{id}/statistiques', [App\Http\Controllers\Api\GroupesParlementairesController::class, 'statistiques'])->name('statistiques');
    Route::get('/{id}/membres', [App\Http\Controllers\Api\GroupesParlementairesController::class, 'membres'])->name('membres');
    Route::get('/{id}/votes', [App\Http\Controllers\Api\GroupesParlementairesController::class, 'votes'])->name('votes');
});

// Routes admin
Route::middleware(['auth:sanctum'])->prefix('groupes-parlementaires')->name('groupes.')->group(function () {
    Route::post('/sync', [App\Http\Controllers\Api\GroupesParlementairesController::class, 'sync'])->name('sync');
});

// ============================================================================
// THÉMATIQUES LÉGISLATIVES
// ============================================================================

// Routes publiques
Route::prefix('thematiques')->name('thematiques.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ThematiquesController::class, 'index'])->name('index');
    Route::get('/populaires', [App\Http\Controllers\Api\ThematiquesController::class, 'populaires'])->name('populaires');
    Route::get('/statistiques', [App\Http\Controllers\Api\ThematiquesController::class, 'statistiques'])->name('statistiques');
    Route::get('/{code}', [App\Http\Controllers\Api\ThematiquesController::class, 'show'])->name('show');
    Route::get('/{code}/propositions', [App\Http\Controllers\Api\ThematiquesController::class, 'propositions'])->name('propositions');
});

// Routes admin/modération
Route::middleware(['auth:sanctum'])->prefix('thematiques')->name('thematiques.')->group(function () {
    Route::post('/detecter', [App\Http\Controllers\Api\ThematiquesController::class, 'detecter'])->name('detecter');
    Route::post('/detecter-batch', [App\Http\Controllers\Api\ThematiquesController::class, 'detecterBatch'])->name('detecter_batch');
    Route::post('/attacher', [App\Http\Controllers\Api\ThematiquesController::class, 'attacher'])->name('attacher');
    Route::delete('/detacher', [App\Http\Controllers\Api\ThematiquesController::class, 'detacher'])->name('detacher');
    Route::post('/recalculer', [App\Http\Controllers\Api\ThematiquesController::class, 'recalculer'])->name('recalculer');
});

// ============================================================================
// NOTIFICATIONS
// ============================================================================

Route::middleware(['auth:sanctum'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\NotificationsController::class, 'index'])->name('index');
    Route::get('/unread-count', [App\Http\Controllers\Api\NotificationsController::class, 'unreadCount'])->name('unread_count');
    Route::get('/stats', [App\Http\Controllers\Api\NotificationsController::class, 'stats'])->name('stats');
    Route::post('/{id}/mark-as-read', [App\Http\Controllers\Api\NotificationsController::class, 'markAsRead'])->name('mark_as_read');
    Route::post('/{id}/mark-as-unread', [App\Http\Controllers\Api\NotificationsController::class, 'markAsUnread'])->name('mark_as_unread');
    Route::post('/mark-all-as-read', [App\Http\Controllers\Api\NotificationsController::class, 'markAllAsRead'])->name('mark_all_as_read');
    Route::delete('/{id}', [App\Http\Controllers\Api\NotificationsController::class, 'destroy'])->name('destroy');
    Route::delete('/clear-read', [App\Http\Controllers\Api\NotificationsController::class, 'clearRead'])->name('clear_read');
    Route::post('/test', [App\Http\Controllers\Api\NotificationsController::class, 'test'])->name('test');
});

// ============================================================================
// USER FOLLOWS (Suivi d'éléments)
// ============================================================================

Route::middleware(['auth:sanctum'])->prefix('follows')->name('follows.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\UserFollowsController::class, 'index'])->name('index');
    Route::post('/follow', [App\Http\Controllers\Api\UserFollowsController::class, 'follow'])->name('follow');
    Route::post('/unfollow', [App\Http\Controllers\Api\UserFollowsController::class, 'unfollow'])->name('unfollow');
    Route::get('/check', [App\Http\Controllers\Api\UserFollowsController::class, 'check'])->name('check');
    Route::get('/stats', [App\Http\Controllers\Api\UserFollowsController::class, 'stats'])->name('stats');
});

// ============================================================================
// NOTIFICATION PREFERENCES
// ============================================================================

Route::middleware(['auth:sanctum'])->prefix('notification-preferences')->name('notification_preferences.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'index'])->name('index');
    Route::put('/', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'update'])->name('update');
    Route::post('/reset', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'reset'])->name('reset');
    Route::post('/toggle-all', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'toggleAll'])->name('toggle_all');
});

// ============================================================================
// EXPORT PDF
// ============================================================================

Route::prefix('export')->name('export.')->group(function () {
    Route::get('/groupe/{id}', [App\Http\Controllers\Api\ExportController::class, 'groupe'])->name('groupe');
    Route::get('/thematique/{code}', [App\Http\Controllers\Api\ExportController::class, 'thematique'])->name('thematique');
    Route::get('/proposition/{id}', [App\Http\Controllers\Api\ExportController::class, 'proposition'])->name('proposition');
    Route::get('/statistiques', [App\Http\Controllers\Api\ExportController::class, 'statistiques'])->name('statistiques');
    Route::post('/comparaison', [App\Http\Controllers\Api\ExportController::class, 'comparaison'])->name('comparaison');
});

// ═══════════════════════════════════════════════════════════════════════════════════
// GAMIFICATION & ACHIEVEMENTS
// ═══════════════════════════════════════════════════════════════════════════════════
Route::middleware('web')->prefix('gamification')->name('gamification.')->group(function () {
    // Routes publiques
    Route::get('/achievements', [App\Http\Controllers\Api\GamificationController::class, 'allAchievements'])->name('achievements.all');
    Route::get('/global-stats', [App\Http\Controllers\Api\GamificationController::class, 'globalStats'])->name('global_stats');
    Route::get('/leaderboard', [App\Http\Controllers\Api\GamificationController::class, 'leaderboard'])->name('leaderboard');
    Route::get('/users/{userId}/stats', [App\Http\Controllers\Api\GamificationController::class, 'userStats'])->name('user.stats');
    Route::get('/users/{userId}/achievements', [App\Http\Controllers\Api\GamificationController::class, 'userAchievements'])->name('user.achievements');
    
    // Routes authentifiées (avec session web)
    Route::middleware('auth')->group(function () {
        Route::post('/initialize', [App\Http\Controllers\Api\GamificationController::class, 'initialize'])->name('initialize');
        Route::get('/my-stats', [App\Http\Controllers\Api\GamificationController::class, 'myStats'])->name('my_stats');
        Route::get('/my-achievements', [App\Http\Controllers\Api\GamificationController::class, 'myAchievements'])->name('my_achievements');
        Route::get('/recent-achievements', [App\Http\Controllers\Api\GamificationController::class, 'recentAchievements'])->name('recent_achievements');
        Route::get('/almost-unlocked', [App\Http\Controllers\Api\GamificationController::class, 'almostUnlocked'])->name('almost_unlocked');
        Route::post('/achievements/{achievementId}/share', [App\Http\Controllers\Api\GamificationController::class, 'shareAchievement'])->name('achievements.share');
        Route::post('/test', [App\Http\Controllers\Api\GamificationController::class, 'test'])->name('test');
    });
});

// ═══════════════════════════════════════════════════════════════════════════════════
// CONTEXTE JURIDIQUE (Légifrance)
// ═══════════════════════════════════════════════════════════════════════════════════
Route::prefix('legal-context')->name('legal_context.')->group(function () {
    // Routes publiques
    Route::get('/propositions/{propositionId}', [App\Http\Controllers\Api\LegalContextController::class, 'show'])->name('show');
    Route::get('/references/{referenceId}', [App\Http\Controllers\Api\LegalContextController::class, 'showReference'])->name('reference');
    Route::get('/stats', [App\Http\Controllers\Api\LegalContextController::class, 'stats'])->name('stats');
    
    // Routes authentifiées (admin)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/propositions/{propositionId}/sync', [App\Http\Controllers\Api\LegalContextController::class, 'sync'])->name('sync');
    });
});

/*
|--------------------------------------------------------------------------
| Hashtags
|--------------------------------------------------------------------------
*/
Route::prefix('hashtags')->name('hashtags.')->group(function () {
    Route::get('/trending', [App\Http\Controllers\Api\HashtagController::class, 'trending'])->name('trending');
    Route::get('/popular', [App\Http\Controllers\Api\HashtagController::class, 'popular'])->name('popular');
    Route::get('/official', [App\Http\Controllers\Api\HashtagController::class, 'official'])->name('official');
    Route::get('/search', [App\Http\Controllers\Api\HashtagController::class, 'search'])->name('search');
    Route::get('/{slug}', [App\Http\Controllers\Api\HashtagController::class, 'show'])->name('show');
});

Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint introuvable. Vérifiez l\'URL et la méthode HTTP.',
    ], 404);
});

