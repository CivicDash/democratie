<?php

use App\Http\Controllers\Web\TopicController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\VoteController;
use App\Http\Controllers\Web\BudgetController;
use App\Http\Controllers\Web\ModerationController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LegislationController;
use App\Http\Controllers\Web\RepresentantController;
use App\Http\Controllers\Web\FranceStatisticsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PolicyController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - CivicDash
|--------------------------------------------------------------------------
|
| Routes Inertia.js pour l'interface utilisateur Vue 3
|
*/

// Page d'accueil - Mode Démo
Route::get('/', function () {
    // Si connecté, rediriger vers le dashboard
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    
    // Sinon, afficher la page démo
    return Inertia::render('Demo/Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

// Recherche
Route::get('/search', function (Request $request) {
    return Inertia::render('Search/Results', [
        'query' => $request->query('q', ''),
    ]);
})->name('search.results');

/*
|--------------------------------------------------------------------------
| Pages Légales (RGPD Art. 13)
|--------------------------------------------------------------------------
*/
Route::get('/privacy', [PolicyController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PolicyController::class, 'terms'])->name('terms');
Route::get('/cookies', [PolicyController::class, 'cookies'])->name('cookies');

/*
|--------------------------------------------------------------------------
| Législation (Assemblée + Sénat)
|--------------------------------------------------------------------------
*/
Route::prefix('legislation')->name('legislation.')->group(function () {
    Route::get('/', [LegislationController::class, 'index'])->name('index');
    
    // Routes spécifiques AVANT la route générique {id}
    // Groupes parlementaires
    Route::get('/groupes', function () {
        return Inertia::render('Groupes/Index', ['source' => 'assemblee']);
    })->name('groupes.index');
    
    Route::get('/groupes/{id}', function ($id) {
        return Inertia::render('Groupes/Show', ['groupeId' => (int)$id]);
    })->name('groupes.show');
    
    // Thématiques
    Route::get('/thematiques', function () {
        return Inertia::render('Thematiques/Index');
    })->name('thematiques.index');
    
    Route::get('/thematiques/{code}', function ($code) {
        return Inertia::render('Thematiques/Show', ['code' => $code]);
    })->name('thematiques.show');
    
    // Route générique (DOIT être en dernier pour éviter les conflits)
    Route::get('/{proposition}', [LegislationController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Topics (Forum Citoyen)
|--------------------------------------------------------------------------
*/
Route::prefix('topics')->name('topics.')->group(function () {
    // Public routes
    Route::get('/', [TopicController::class, 'index'])->name('index');
    Route::get('/trending', [TopicController::class, 'trending'])->name('trending');
    
    // Authenticated routes (AVANT {topic} pour éviter les conflits)
    Route::middleware('auth')->group(function () {
        Route::get('/create', [TopicController::class, 'create'])->name('create');
        Route::post('/', [TopicController::class, 'store'])->name('store');
    });
    
    // Show route (APRÈS create pour éviter que "create" soit interprété comme un ID)
    Route::get('/{topic}', [TopicController::class, 'show'])->name('show');
    
    // Other authenticated routes
    Route::middleware('auth')->group(function () {
        Route::get('/{topic}/edit', [TopicController::class, 'edit'])->name('edit');
        Route::put('/{topic}', [TopicController::class, 'update'])->name('update');
        Route::delete('/{topic}', [TopicController::class, 'destroy'])->name('destroy');
        
        // Posts
        Route::post('/{topic}/posts', [PostController::class, 'store'])->name('posts.store');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/vote', [PostController::class, 'vote'])->name('posts.vote');
    });
});

/*
|--------------------------------------------------------------------------
| Vote (Scrutins Anonymes)
|--------------------------------------------------------------------------
*/
Route::prefix('vote')->name('vote.')->group(function () {
    // Public routes
    Route::get('/topics/{topic}', [VoteController::class, 'show'])->name('show');
    Route::get('/topics/{topic}/results', [VoteController::class, 'results'])->name('results');
    
    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/topics/{topic}/token', [VoteController::class, 'requestToken'])->name('token');
        Route::post('/topics/{topic}/cast', [VoteController::class, 'cast'])->name('cast');
    });
});

/*
|--------------------------------------------------------------------------
| Budget Participatif
|--------------------------------------------------------------------------
*/
Route::prefix('budget')->name('budget.')->group(function () {
    // Public routes
    Route::get('/', [BudgetController::class, 'index'])->name('index');
    Route::get('/stats', [BudgetController::class, 'stats'])->name('stats');
    Route::get('/sectors', [BudgetController::class, 'sectors'])->name('sectors');
    
    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::get('/my-allocations', [BudgetController::class, 'myAllocations'])->name('my-allocations');
        Route::post('/allocate', [BudgetController::class, 'allocate'])->name('allocate');
        Route::post('/bulk-allocate', [BudgetController::class, 'bulkAllocate'])->name('bulk-allocate');
        Route::delete('/reset', [BudgetController::class, 'reset'])->name('reset');
    });
});

/*
|--------------------------------------------------------------------------
| Votes Citoyens sur Propositions de Loi (Web Routes)
|--------------------------------------------------------------------------
*/
Route::prefix('legislation/propositions')->middleware('auth:web')->group(function () {
    Route::post('/{id}/vote', [\App\Http\Controllers\Api\LegislationController::class, 'voteProposition']);
    Route::delete('/{id}/vote', [\App\Http\Controllers\Api\LegislationController::class, 'removeVoteProposition']);
    Route::get('/{id}/my-vote', [\App\Http\Controllers\Api\LegislationController::class, 'getMyVote']);
});

/*
|--------------------------------------------------------------------------
| Modération
|--------------------------------------------------------------------------
*/
Route::prefix('moderation')->name('moderation.')->middleware(['auth', 'role:moderator|admin'])->group(function () {
    Route::get('/dashboard', [ModerationController::class, 'dashboard'])->name('dashboard');
    Route::get('/reports', [ModerationController::class, 'reports'])->name('reports.index');
    Route::get('/reports/priority', [ModerationController::class, 'priorityReports'])->name('reports.priority');
    Route::get('/reports/{report}', [ModerationController::class, 'showReport'])->name('reports.show');
    Route::post('/reports/{report}/assign', [ModerationController::class, 'assignReport'])->name('reports.assign');
    Route::post('/reports/{report}/resolve', [ModerationController::class, 'resolveReport'])->name('reports.resolve');
    Route::post('/reports/{report}/reject', [ModerationController::class, 'rejectReport'])->name('reports.reject');
    
    Route::get('/sanctions', [ModerationController::class, 'sanctions'])->name('sanctions.index');
    Route::get('/sanctions/{sanction}', [ModerationController::class, 'showSanction'])->name('sanctions.show');
    Route::delete('/sanctions/{sanction}', [ModerationController::class, 'revokeSanction'])->name('sanctions.revoke');
    
    Route::get('/stats', [ModerationController::class, 'stats'])->name('stats');
});

// Public report submission
Route::post('/reports', [ModerationController::class, 'store'])->middleware('auth')->name('reports.store');

/*
|--------------------------------------------------------------------------
| Documents Publics
|--------------------------------------------------------------------------
*/
Route::prefix('documents')->name('documents.')->group(function () {
    // Public routes
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
    Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
    Route::get('/stats', [DocumentController::class, 'stats'])->name('stats');
    
    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
    });
    
    // Verification (journalists, ong, admin)
    Route::middleware(['auth', 'role:journalist|ong|admin'])->group(function () {
        Route::get('/pending', [DocumentController::class, 'pending'])->name('pending');
        Route::post('/{document}/verify', [DocumentController::class, 'verify'])->name('verify');
    });
});

/*
|--------------------------------------------------------------------------
| Dashboard & Profile
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['verified'])->name('dashboard');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Gamification Profile
    Route::get('/profile/gamification', function () {
        return Inertia::render('Profile/Gamification');
    })->name('profile.gamification');
});

/*
|--------------------------------------------------------------------------
| Statistiques France
|--------------------------------------------------------------------------
*/
Route::prefix('statistiques')->name('statistics.')->group(function () {
    Route::get('/france', [FranceStatisticsController::class, 'index'])->name('france');
    Route::get('/france/region/{regionCode}', [FranceStatisticsController::class, 'getRegionData'])->name('france.region');
    Route::get('/france/department/{departmentCode}', [FranceStatisticsController::class, 'getDepartmentData'])->name('france.department');
    Route::get('/france/compare', [FranceStatisticsController::class, 'compareYears'])->name('france.compare');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Représentants (Députés & Sénateurs)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('representants')->name('representants.')->group(function () {
    // Mes représentants
    Route::get('/mes-representants', [RepresentantController::class, 'mesRepresentants'])->name('mes-representants');
    
    // Députés
    Route::get('/deputes', [RepresentantController::class, 'deputes'])->name('deputes.index');
    Route::get('/deputes/{depute}', [RepresentantController::class, 'showDepute'])->name('deputes.show');
    
    // Sénateurs
    Route::get('/senateurs', [RepresentantController::class, 'senateurs'])->name('senateurs.index');
    Route::get('/senateurs/{senateur}', [RepresentantController::class, 'showSenateur'])->name('senateurs.show');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
