<?php

use App\Http\Controllers\Web\TopicController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\VoteController;
use App\Http\Controllers\Web\BudgetController;
use App\Http\Controllers\Web\BudgetEtatController;
use App\Http\Controllers\Web\GouvernementController;
use App\Http\Controllers\Web\ModerationController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LegislationController;
use App\Http\Controllers\Web\RepresentantController;
use App\Http\Controllers\Web\ParlementController;
use App\Http\Controllers\Web\FranceStatisticsController;
use App\Http\Controllers\Web\TagController;
use App\Http\Controllers\Web\AdminController;
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

// Pages de suspension/bannissement (sans auth)
Route::get('/account/suspended', function () {
    return Inertia::render('Auth/Suspended', [
        'reason' => session('reason'),
        'suspended_until' => session('suspended_until'),
        'remaining' => session('remaining'),
    ]);
})->name('account.suspended');

Route::get('/account/banned', function () {
    return Inertia::render('Auth/Banned', [
        'reason' => session('reason'),
        'banned_at' => session('banned_at'),
        'appeal_email' => 'bannissement@civis-consilium.eu',
    ]);
})->name('account.banned');

// Page d'accueil - Redirection vers login ou dashboard
Route::get('/', function () {
    // Si l'utilisateur est connecté, rediriger vers le dashboard
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    
    // Sinon, afficher la page de login avec les stats
    $globalStats = \App\Models\DashboardStat::get('global_stats', [
        'nb_deputes' => 577,
        'nb_senateurs' => 348,
        'nb_scrutins' => 0,
        'nb_lois_en_cours' => 0,
        'nb_maires' => 0,
    ]);
    
    $stats = [
        'deputes' => $globalStats['nb_deputes'] ?? 577,
        'senateurs' => $globalStats['nb_senateurs'] ?? 348,
        'lois_en_cours' => $globalStats['nb_lois_en_cours'] ?? \App\Models\Loi::whereNull('loidatjo')->count(),
        'maires' => $globalStats['nb_maires'] ?? \App\Models\Maire::enExercice()->count(),
    ];
    
    return Inertia::render('Welcome', [
        'stats' => $stats,
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

// Ancienne page d'accueil (accessible via /accueil pour les utilisateurs connectés)
Route::get('/accueil', function () {
    $globalStats = \App\Models\DashboardStat::get('global_stats', [
        'nb_deputes' => 577,
        'nb_senateurs' => 348,
        'nb_scrutins' => 0,
        'nb_lois_en_cours' => 0,
        'nb_maires' => 0,
    ]);
    
    $stats = [
        'deputes' => $globalStats['nb_deputes'] ?? 577,
        'senateurs' => $globalStats['nb_senateurs'] ?? 348,
        'lois_en_cours' => $globalStats['nb_lois_en_cours'] ?? \App\Models\Loi::whereNull('loidatjo')->count(),
        'maires' => $globalStats['nb_maires'] ?? \App\Models\Maire::enExercice()->count(),
    ];
    
    return Inertia::render('Home', [
        'stats' => $stats,
    ]);
})->middleware('auth')->name('home.explore');

// Ancienne page démo (pour référence/développement)
Route::get('/demo', function () {
    return Inertia::render('Demo/Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('demo');

// Recherche globale
Route::get('/search', function (Request $request) {
    return Inertia::render('Search/Results', [
        'query' => $request->query('q', ''),
    ]);
})->middleware('auth')->name('search');

/*
|--------------------------------------------------------------------------
| Tags / Thèmes
|--------------------------------------------------------------------------
*/
Route::prefix('tags')->name('tags.')->middleware('auth')->group(function () {
    Route::get('/', [TagController::class, 'index'])->name('index');
    Route::get('/{slug}', [TagController::class, 'show'])->name('show');
});

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
| Questions au Gouvernement (authentifié)
|--------------------------------------------------------------------------
*/
Route::prefix('questions')->name('questions.')->middleware('auth')->group(function () {
    // Questions Assemblée Nationale (par défaut)
    Route::get('/', [\App\Http\Controllers\Web\QuestionController::class, 'index'])->name('index');
    Route::get('/stats', [\App\Http\Controllers\Web\QuestionController::class, 'stats'])->name('stats');
    Route::get('/depute/{uid}', [\App\Http\Controllers\Web\QuestionController::class, 'byDepute'])->name('depute');
    Route::get('/an/{uid}', [\App\Http\Controllers\Web\QuestionController::class, 'show'])->name('show');
    
    // Questions Sénat
    Route::get('/senat', [\App\Http\Controllers\Web\QuestionController::class, 'indexSenat'])->name('senat.index');
    Route::get('/senat/stats', [\App\Http\Controllers\Web\QuestionController::class, 'statsSenat'])->name('senat.stats');
    Route::get('/senat/senateur/{matricule}', [\App\Http\Controllers\Web\QuestionController::class, 'bySenateur'])->name('senateur');
    Route::get('/senat/{numero}', [\App\Http\Controllers\Web\QuestionController::class, 'showSenat'])->name('senat.show');
});

/*
|--------------------------------------------------------------------------
| Législation (Assemblée + Sénat) - Authentifié
|--------------------------------------------------------------------------
*/
Route::prefix('legislation')->name('legislation.')->middleware('auth')->group(function () {
    // Hub unifié
    Route::get('/', [LegislationController::class, 'hub'])->name('hub');
    Route::get('/propositions', [LegislationController::class, 'index'])->name('index');
    
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
    
    // Scrutins AN (NOUVEAU)
    Route::get('/scrutins', [LegislationController::class, 'scrutinsIndex'])->name('scrutins.index');
    Route::get('/scrutins/{uid}', [LegislationController::class, 'showScrutin'])->name('scrutins.show');
    Route::get('/scrutins/{uid}/comparaison', [LegislationController::class, 'comparaisonVote'])->name('scrutins.comparaison');
    
    // Scrutins Sénat
    Route::get('/scrutins-senat', [\App\Http\Controllers\Web\ScrutinSenatController::class, 'index'])->name('scrutins-senat.index');
    Route::get('/scrutins-senat/{id}', [\App\Http\Controllers\Web\ScrutinSenatController::class, 'show'])->name('scrutins-senat.show');
    
    // Amendements (NOUVEAU)
    Route::get('/amendements/{uid}', [LegislationController::class, 'showAmendement'])->name('amendements.show');
    
    // Dossiers législatifs (NOUVEAU)
    Route::get('/dossiers/{uid}', [LegislationController::class, 'showDossier'])->name('dossiers.show');
    
    // Textes législatifs (NOUVEAU)
    Route::get('/textes/{uid}', [LegislationController::class, 'showTexte'])->name('textes.show');
    
    // Redirection /legislation/lois vers /lois (éviter conflit avec route générique)
    Route::get('/lois', function () {
        return redirect()->route('lois.index');
    })->name('lois.redirect');
    
    // Route générique (DOIT être en dernier pour éviter les conflits)
    Route::get('/{proposition}', [LegislationController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Parlement - Calendrier Législatif (Authentifié)
|--------------------------------------------------------------------------
*/
Route::prefix('parlement')->name('parlement.')->middleware('auth')->group(function () {
    // Calendrier des réunions
    Route::get('/calendrier', [\App\Http\Controllers\Web\CalendrierController::class, 'index'])->name('calendrier.index');
    Route::get('/calendrier/semaine', [\App\Http\Controllers\Web\CalendrierController::class, 'semaine'])->name('calendrier.semaine');
    Route::get('/calendrier/reunion/{uid}', [\App\Http\Controllers\Web\CalendrierController::class, 'show'])->name('calendrier.show');
    
    // API pour widgets
    Route::get('/api/reunions/aujourdhui', [\App\Http\Controllers\Web\CalendrierController::class, 'aujourdhui'])->name('api.reunions.aujourdhui');
    Route::get('/api/reunions/prochaines', [\App\Http\Controllers\Web\CalendrierController::class, 'prochaines'])->name('api.reunions.prochaines');
});

// ==========================================
// LOIS - Cycle de vie législatif (Authentifié)
// ==========================================
Route::prefix('lois')->name('lois.')->middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\LoiController::class, 'index'])->name('index');
    Route::get('/statistiques', [\App\Http\Controllers\Web\LoiController::class, 'statistiques'])->name('statistiques');
    Route::get('/recherche', [\App\Http\Controllers\Web\LoiController::class, 'search'])->name('search');
    Route::get('/{loicod}', [\App\Http\Controllers\Web\LoiController::class, 'show'])->name('show');
    Route::get('/{loicod}/timeline', [\App\Http\Controllers\Web\LoiController::class, 'timeline'])->name('timeline');
    Route::get('/{loicod}/amendements', [\App\Http\Controllers\Web\LoiController::class, 'amendementsApi'])->name('amendements');
});

/*
|--------------------------------------------------------------------------
| Topics (Forum Citoyen) - FUSIONNÉ AVEC IDÉES
| Redirige vers /participation/idees
|--------------------------------------------------------------------------
*/
Route::prefix('topics')->name('topics.')->group(function () {
    // Redirection de l'index vers les idées citoyennes
    Route::get('/', function () {
        return redirect()->route('participation.ideas.index');
    })->name('index');
    
    Route::get('/trending', function () {
        return redirect()->route('participation.ideas.index', ['sort' => 'trending']);
    })->name('trending');
    
    // Redirection de création vers nouvelle idée
    Route::get('/create', function () {
        return redirect()->route('participation.ideas.create');
    })->name('create');
    
    // Redirection show vers idées - SUPPORTE SLUG ET ID
    Route::get('/{topic}', function ($topic) {
        // Si c'est un ID numérique, chercher le topic pour obtenir le slug
        if (is_numeric($topic)) {
            $topicModel = \App\Models\Topic::find($topic);
            if ($topicModel) {
                return redirect()->route('participation.ideas.show', $topicModel->slug ?: $topicModel->id);
            }
        }
        // Sinon rediriger directement avec le slug
        return redirect()->route('participation.ideas.show', $topic);
    })->name('show');
    
    // Authenticated routes - Posts sur les topics (bloqué pour comptes démo)
    Route::middleware(['auth', 'not-readonly'])->group(function () {
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
Route::prefix('vote')->name('vote.')->middleware('auth')->group(function () {
    // Public routes (lecture)
    Route::get('/topics/{topic}', [VoteController::class, 'show'])->name('show');
    Route::get('/topics/{topic}/results', [VoteController::class, 'results'])->name('results');
    
    // Authenticated routes (écriture - bloqué pour comptes démo)
    Route::middleware('not-readonly')->group(function () {
        Route::post('/topics/{topic}/token', [VoteController::class, 'requestToken'])->name('token');
        Route::post('/topics/{topic}/cast', [VoteController::class, 'cast'])->name('cast');
    });
});

/*
|--------------------------------------------------------------------------
| Budget Participatif
|--------------------------------------------------------------------------
*/
Route::prefix('budget')->name('budget.')->middleware('auth')->group(function () {
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
| Budget de l'État (PLF/LFI)
|--------------------------------------------------------------------------
*/
Route::prefix('budget-etat')->name('budget-etat.')->group(function () {
    Route::get('/', [BudgetEtatController::class, 'index'])->name('index');
    Route::get('/mission/{code}', [BudgetEtatController::class, 'showMission'])->name('mission');
    Route::get('/api/data', [BudgetEtatController::class, 'apiData'])->name('api.data');
});

/*
|--------------------------------------------------------------------------
| Gouvernement
|--------------------------------------------------------------------------
*/
Route::prefix('gouvernement')->name('gouvernement.')->group(function () {
    Route::get('/', [GouvernementController::class, 'index'])->name('index');
    Route::get('/statistiques', [GouvernementController::class, 'statistiques'])->name('statistiques');
    Route::get('/ministeres', [GouvernementController::class, 'ministeres'])->name('ministeres');
    Route::get('/ministeres/{slug}', [GouvernementController::class, 'showMinistere'])->name('ministere.show');
    Route::get('/president', [GouvernementController::class, 'showPresident'])->name('president');
    Route::get('/president/{slug}', [GouvernementController::class, 'showPresident'])->name('president.show');
    Route::get('/historique', [GouvernementController::class, 'historique'])->name('historique');
    Route::get('/personne/{slug}', [GouvernementController::class, 'showPersonne'])->name('personne');
});

/*
|--------------------------------------------------------------------------
| Votes Citoyens sur Propositions de Loi (Web Routes)
|--------------------------------------------------------------------------
*/
Route::prefix('legislation/propositions')->middleware('auth:web')->group(function () {
    Route::get('/{id}/my-vote', [\App\Http\Controllers\Api\LegislationController::class, 'getMyVote']);
    
    // Écriture - bloqué pour comptes démo
    Route::middleware('not-readonly')->group(function () {
        Route::post('/{id}/vote', [\App\Http\Controllers\Api\LegislationController::class, 'voteProposition']);
        Route::delete('/{id}/vote', [\App\Http\Controllers\Api\LegislationController::class, 'removeVoteProposition']);
    });
});

/*
|--------------------------------------------------------------------------
| Modération
|--------------------------------------------------------------------------
*/
Route::prefix('moderation')->name('moderation.')->middleware(['auth', 'role:moderator|admin', 'two-factor'])->group(function () {
    // Dashboard utilise le controller avec les bonnes props (photoStats, etc.)
    Route::get('/dashboard', [\App\Http\Controllers\ModerationController::class, 'dashboard'])->name('dashboard');
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

// Public report submission (API version in api.php handles this now)
// Route::post('/reports', [ModerationController::class, 'store'])->middleware('auth')->name('report.submit');

/*
|--------------------------------------------------------------------------
| Documents Publics
|--------------------------------------------------------------------------
*/
Route::prefix('documents')->name('documents.')->middleware('auth')->group(function () {
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
| Participation Citoyenne
|--------------------------------------------------------------------------
*/
Route::prefix('participation')->name('participation.')->middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\ParticipationController::class, 'hub'])->name('hub');
    
    // Idées citoyennes (lecture)
    Route::get('/idees', [\App\Http\Controllers\Web\ParticipationController::class, 'ideasIndex'])->name('ideas.index');
    Route::get('/idees/nouvelle', [\App\Http\Controllers\Web\ParticipationController::class, 'ideasCreate'])->name('ideas.create');
    Route::get('/idees/{topic:slug}', [\App\Http\Controllers\Web\ParticipationController::class, 'ideasShow'])->name('ideas.show');
    
    // Idées citoyennes (écriture - bloqué pour comptes démo)
    Route::middleware('not-readonly')->group(function () {
        Route::post('/idees', [\App\Http\Controllers\Web\ParticipationController::class, 'ideasStore'])->name('ideas.store');
        Route::post('/idees/{topic}/vote', [\App\Http\Controllers\Web\ParticipationController::class, 'vote'])->name('ideas.vote');
        Route::delete('/idees/{topic}/vote', [\App\Http\Controllers\Web\ParticipationController::class, 'unvote'])->name('ideas.unvote');
        Route::post('/idees/{topic}/comment', [\App\Http\Controllers\Web\ParticipationController::class, 'addComment'])->name('ideas.comment');
    });
});


// Profil public des élus (accessible à tous les utilisateurs connectés)
Route::get('/elus/{type}/{ref}', [\App\Http\Controllers\Web\EluDashboardController::class, 'publicProfile'])
    ->middleware('auth')
    ->name('elus.public-profile')
    ->where('type', 'depute|senateur|maire');

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
    
    // Élus suivis
    Route::get('/profile/elus-suivis', [App\Http\Controllers\Web\ElusSuivisController::class, 'index'])->name('profile.elus-suivis');
    
    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\NotificationController::class, 'index'])->name('index');
        Route::get('/preferences', [App\Http\Controllers\Web\NotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [App\Http\Controllers\Web\NotificationController::class, 'updatePreferences'])->name('preferences.update');
        Route::post('/read-all', [App\Http\Controllers\Web\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::post('/{notification}/read', [App\Http\Controllers\Web\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/{notification}/acknowledge', [App\Http\Controllers\Web\NotificationController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{notification}/action', [App\Http\Controllers\Web\NotificationController::class, 'action'])->name('action');
        Route::delete('/{notification}', [App\Http\Controllers\Web\NotificationController::class, 'destroy'])->name('destroy');
    });
    
    // API Notifications (pour dropdown via session)
    Route::get('/api/notifications/recent', [App\Http\Controllers\Web\NotificationController::class, 'recent'])->name('api.notifications.recent');
    Route::get('/api/notifications/unread-count', [App\Http\Controllers\Web\NotificationController::class, 'unreadCount'])->name('api.notifications.unread-count');
});

/*
|--------------------------------------------------------------------------
| Statistiques France
|--------------------------------------------------------------------------
*/
Route::prefix('statistiques')->name('statistics.')->middleware('auth')->group(function () {
    Route::get('/france', [FranceStatisticsController::class, 'index'])->name('france');
    Route::get('/france/region/{regionCode}', [FranceStatisticsController::class, 'getRegionData'])->name('france.region');
    Route::get('/france/department/{departmentCode}', [FranceStatisticsController::class, 'getDepartmentData'])->name('france.department');
    
    // Statistiques Villes
    Route::get('/villes', [App\Http\Controllers\Web\StatistiquesVillesController::class, 'index'])->name('villes');
    
    // Statistiques Régions
    Route::get('/regions', [App\Http\Controllers\Web\StatistiquesRegionsController::class, 'index'])->name('regions.index');
    Route::get('/regions/{code}', [App\Http\Controllers\Web\StatistiquesRegionsController::class, 'show'])->name('regions.show');
    
    Route::get('/france/compare', [FranceStatisticsController::class, 'compareYears'])->name('france.compare');
});

/*
|--------------------------------------------------------------------------
| Données (section unifiée)
|--------------------------------------------------------------------------
*/
Route::prefix('donnees')->name('donnees.')->middleware('auth')->group(function () {
    Route::get('/gouvernements', [GouvernementController::class, 'statistiques'])->name('gouvernements');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin', 'two-factor'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/imports', [AdminController::class, 'imports'])->name('imports');
    Route::get('/imports/{import}', [AdminController::class, 'showImport'])->name('imports.show');
    Route::post('/run-command', [AdminController::class, 'runCommand'])->name('run-command');

    // Gestion du Gouvernement
    Route::prefix('gouvernement')->name('gouvernement.')->group(function () {
        // Gouvernements
        Route::get('/', [App\Http\Controllers\Web\AdminGouvernementController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Web\AdminGouvernementController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Web\AdminGouvernementController::class, 'store'])->name('store');
        Route::get('/ministeres', [App\Http\Controllers\Web\AdminGouvernementController::class, 'ministeres'])->name('ministeres');
        Route::get('/personnes', [App\Http\Controllers\Web\AdminGouvernementController::class, 'personnes'])->name('personnes');
        Route::get('/{gouvernement}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'show'])->name('show');
        Route::put('/{gouvernement}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'update'])->name('update');
        Route::delete('/{gouvernement}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'destroy'])->name('destroy');
        Route::get('/{gouvernement}/export', [App\Http\Controllers\Web\AdminGouvernementController::class, 'exportJson'])->name('export');
        
        // Postes ministériels (affectations)
        Route::post('/{gouvernement}/postes', [App\Http\Controllers\Web\AdminGouvernementController::class, 'addPoste'])->name('add-poste');
        
        // Ministères
        Route::post('/ministeres', [App\Http\Controllers\Web\AdminGouvernementController::class, 'storeMinistere'])->name('store-ministere');
        
        // Personnes politiques
        Route::post('/personnes', [App\Http\Controllers\Web\AdminGouvernementController::class, 'storePersonne'])->name('store-personne');
    });
    
    // Actions sur les postes (hors groupe pour le binding)
    Route::put('/postes/{poste}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'updatePoste'])->name('gouvernement.update-poste');
    Route::delete('/postes/{poste}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'deletePoste'])->name('gouvernement.delete-poste');
    Route::post('/postes/{poste}/end', [App\Http\Controllers\Web\AdminGouvernementController::class, 'endPoste'])->name('gouvernement.end-poste');
    
    // Actions sur les personnes
    Route::put('/personnes/{personne}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'updatePersonne'])->name('gouvernement.update-personne');
    
    // Actions sur les ministères
    Route::put('/ministeres/{ministere}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'updateMinistere'])->name('gouvernement.update-ministere');

    // Gestion des Élus
    Route::prefix('elus')->name('elus.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\AdminElusController::class, 'index'])->name('index');
        Route::get('/search', [App\Http\Controllers\Web\AdminElusController::class, 'search'])->name('search');
        
        // Députés
        Route::get('/deputes', [App\Http\Controllers\Web\AdminElusController::class, 'deputes'])->name('deputes.index');
        Route::get('/deputes/{depute}/edit', [App\Http\Controllers\Web\AdminElusController::class, 'editDepute'])->name('deputes.edit');
        Route::put('/deputes/{depute}', [App\Http\Controllers\Web\AdminElusController::class, 'updateDepute'])->name('deputes.update');
        
        // Sénateurs
        Route::get('/senateurs', [App\Http\Controllers\Web\AdminElusController::class, 'senateurs'])->name('senateurs.index');
        Route::get('/senateurs/{senateur}/edit', [App\Http\Controllers\Web\AdminElusController::class, 'editSenateur'])->name('senateurs.edit');
        Route::put('/senateurs/{senateur}', [App\Http\Controllers\Web\AdminElusController::class, 'updateSenateur'])->name('senateurs.update');
        
        // Maires
        Route::get('/maires', [App\Http\Controllers\Web\AdminElusController::class, 'maires'])->name('maires.index');
        Route::get('/maires/{maire}/edit', [App\Http\Controllers\Web\AdminElusController::class, 'editMaire'])->name('maires.edit');
        Route::put('/maires/{maire}', [App\Http\Controllers\Web\AdminElusController::class, 'updateMaire'])->name('maires.update');
        
        // Ministres (Personnes politiques du gouvernement)
        Route::get('/ministres', [App\Http\Controllers\Web\AdminElusController::class, 'ministres'])->name('ministres.index');
        Route::get('/ministres/{personne}/edit', [App\Http\Controllers\Web\AdminElusController::class, 'editMinistre'])->name('ministres.edit');
        Route::put('/ministres/{personne}', [App\Http\Controllers\Web\AdminElusController::class, 'updateMinistre'])->name('ministres.update');
    });
    
    // Gestion des Domaines Ministériels (catégorisation)
    Route::prefix('domaines')->name('domaines.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\AdminDomainesController::class, 'index'])->name('index');
        Route::post('/assigner-fonction', [App\Http\Controllers\Web\AdminDomainesController::class, 'assignerFonction'])->name('assigner-fonction');
        Route::post('/assigner-masse', [App\Http\Controllers\Web\AdminDomainesController::class, 'assignerMasse'])->name('assigner-masse');
        
        Route::get('/gestion', [App\Http\Controllers\Web\AdminDomainesController::class, 'domaines'])->name('gestion');
        Route::post('/gestion', [App\Http\Controllers\Web\AdminDomainesController::class, 'storeDomaine'])->name('store');
        Route::put('/gestion/{domaine}', [App\Http\Controllers\Web\AdminDomainesController::class, 'updateDomaine'])->name('update');
        Route::delete('/gestion/{domaine}', [App\Http\Controllers\Web\AdminDomainesController::class, 'destroyDomaine'])->name('destroy');
        
        Route::get('/suggestions', [App\Http\Controllers\Web\AdminDomainesController::class, 'suggestions'])->name('suggestions');
        Route::post('/suggestions/apply', [App\Http\Controllers\Web\AdminDomainesController::class, 'applySuggestions'])->name('suggestions.apply');
    });

    // Gestion du Budget PLF
    Route::prefix('budget')->name('budget.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\AdminBudgetController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Web\AdminBudgetController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Web\AdminBudgetController::class, 'store'])->name('store');
        Route::get('/{budget}/edit', [App\Http\Controllers\Web\AdminBudgetController::class, 'edit'])->name('edit');
        Route::put('/{budget}', [App\Http\Controllers\Web\AdminBudgetController::class, 'update'])->name('update');
        Route::delete('/{budget}', [App\Http\Controllers\Web\AdminBudgetController::class, 'destroy'])->name('destroy');
        Route::post('/duplicate', [App\Http\Controllers\Web\AdminBudgetController::class, 'duplicate'])->name('duplicate');
        Route::get('/export', [App\Http\Controllers\Web\AdminBudgetController::class, 'export'])->name('export');
    });

    // Modération - Mots bannis & mots gentils
    Route::prefix('moderation')->name('moderation.')->group(function () {
        Route::get('/words', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'index'])->name('words');
        Route::post('/test', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'test'])->name('test');
        Route::post('/seed', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'seed'])->name('seed');
        
        // Mots bannis
        Route::post('/banned', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'storeBanned'])->name('banned.store');
        Route::put('/banned/{bannedWord}', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'updateBanned'])->name('banned.update');
        Route::delete('/banned/{bannedWord}', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'destroyBanned'])->name('banned.destroy');
        
        // Mots gentils
        Route::post('/nice', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'storeNice'])->name('nice.store');
        Route::put('/nice/{niceWord}', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'updateNice'])->name('nice.update');
        Route::delete('/nice/{niceWord}', [App\Http\Controllers\Web\AdminModerationWordsController::class, 'destroyNice'])->name('nice.destroy');
    });

    // Gestion des Utilisateurs
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/change-role', [App\Http\Controllers\Admin\UserManagementController::class, 'changeRole'])->name('change-role');
        Route::post('/{user}/verify-elu', [App\Http\Controllers\Admin\UserManagementController::class, 'verifyElu'])->name('verify-elu');
        Route::post('/{user}/revoke-elu', [App\Http\Controllers\Admin\UserManagementController::class, 'revokeElu'])->name('revoke-elu');
        
        // Sanctions
        Route::get('/{user}/sanctions', [App\Http\Controllers\Admin\UserSanctionController::class, 'history'])->name('sanctions');
        Route::post('/{user}/suspend', [App\Http\Controllers\Admin\UserSanctionController::class, 'suspend'])->name('suspend')->middleware('not-readonly');
        Route::post('/{user}/ban', [App\Http\Controllers\Admin\UserSanctionController::class, 'ban'])->name('ban')->middleware('not-readonly');
        Route::post('/{user}/unban', [App\Http\Controllers\Admin\UserSanctionController::class, 'unban'])->name('unban')->middleware('not-readonly');
        Route::delete('/{user}/force-delete', [App\Http\Controllers\Admin\UserSanctionController::class, 'forceDelete'])->name('force-delete')->middleware('not-readonly');
        Route::post('/{user}/restore', [App\Http\Controllers\Admin\UserSanctionController::class, 'restore'])->name('restore')->middleware('not-readonly');
    });

    // Finances Publiques (Budget, URSSAF, Recettes consolidées)
    Route::prefix('finances')->name('finances.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'index'])->name('index');
        Route::post('/import', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'runImport'])->name('import');
        
        // Budget Annuel
        Route::get('/budget-annuel/create', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'createBudgetAnnuel'])->name('budget-annuel.create');
        Route::post('/budget-annuel', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'storeBudgetAnnuel'])->name('budget-annuel.store');
        Route::get('/budget-annuel/{budgetAnnuel}/edit', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'editBudgetAnnuel'])->name('budget-annuel.edit');
        Route::put('/budget-annuel/{budgetAnnuel}', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'updateBudgetAnnuel'])->name('budget-annuel.update');
        
        // Recettes consolidées
        Route::get('/recettes/create', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'createRecettes'])->name('recettes.create');
        Route::post('/recettes', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'storeRecettes'])->name('recettes.store');
        Route::get('/recettes/{recette}/edit', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'editRecettes'])->name('recettes.edit');
        Route::put('/recettes/{recette}', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'updateRecettes'])->name('recettes.update');
        
        // URSSAF
        Route::get('/urssaf', [App\Http\Controllers\Admin\FinancesPubliquesController::class, 'urssafDetails'])->name('urssaf');
    });

    // Statistiques France (source unique)
    Route::prefix('stats-france')->name('stats-france.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'index'])->name('index');
        Route::post('/create-year', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'createYear'])->name('create-year');
        
        // Démographie
        Route::get('/demographie', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'demographie'])->name('demographie');
        Route::put('/demographie/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateDemographie'])->name('demographie.update');
        
        // Économie
        Route::get('/economie', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'economie'])->name('economie');
        Route::put('/economie/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateEconomie'])->name('economie.update');
        
        // Budget État
        Route::get('/budget', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'budget'])->name('budget');
        Route::put('/budget/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateBudget'])->name('budget.update');
        
        // Recettes consolidées
        Route::get('/recettes', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'recettes'])->name('recettes');
        Route::put('/recettes/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateRecettes'])->name('recettes.update');
        
        // Dépenses publiques
        Route::get('/depenses', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'depenses'])->name('depenses');
        Route::put('/depenses/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateDepenses'])->name('depenses.update');
        
        // Éducation
        Route::get('/education', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'education'])->name('education');
        Route::put('/education/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateEducation'])->name('education.update');
        
        // Santé
        Route::get('/sante', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'sante'])->name('sante');
        Route::put('/sante/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateSante'])->name('sante.update');
        
        // Environnement
        Route::get('/environnement', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'environnement'])->name('environnement');
        Route::put('/environnement/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateEnvironnement'])->name('environnement.update');
        
        // Sécurité
        Route::get('/securite', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'securite'])->name('securite');
        Route::put('/securite/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateSecurite'])->name('securite.update');
        
        // Emploi
        Route::get('/emploi', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'emploi'])->name('emploi');
        Route::put('/emploi/{annee}', [App\Http\Controllers\Admin\StatistiquesFranceController::class, 'updateEmploi'])->name('emploi.update');
    });

    // Test d'emails
    Route::prefix('emails')->name('email.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\EmailTestController::class, 'index'])->name('index');
        Route::post('/send', [App\Http\Controllers\Admin\EmailTestController::class, 'send'])->name('send');
        Route::get('/preview/{template}', [App\Http\Controllers\Admin\EmailTestController::class, 'preview'])->name('preview');
    });

    // Modération des photos de profil
    Route::prefix('moderation/photos')->name('moderation.photos.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PhotoModerationController::class, 'index'])->name('index');
        Route::post('/{user}/approve', [App\Http\Controllers\Admin\PhotoModerationController::class, 'approve'])->name('approve');
        Route::post('/{user}/reject', [App\Http\Controllers\Admin\PhotoModerationController::class, 'reject'])->name('reject');
        Route::get('/history', [App\Http\Controllers\Admin\PhotoModerationController::class, 'history'])->name('history');
    });

    // Membres de l'association Civis-Consilium
    Route::prefix('association')->name('association.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AssociationMembersController::class, 'index'])->name('index');
        Route::post('/{user}/add-member', [App\Http\Controllers\Admin\AssociationMembersController::class, 'addMember'])->name('add-member');
        Route::delete('/{user}/remove-member', [App\Http\Controllers\Admin\AssociationMembersController::class, 'removeMember'])->name('remove-member');
        Route::put('/{user}/member-id', [App\Http\Controllers\Admin\AssociationMembersController::class, 'updateMemberId'])->name('update-member-id');
        Route::get('/search-users', [App\Http\Controllers\Admin\AssociationMembersController::class, 'searchUsers'])->name('search-users');
        Route::get('/export', [App\Http\Controllers\Admin\AssociationMembersController::class, 'export'])->name('export');
    });
});

/*
|--------------------------------------------------------------------------
| Représentants (Députés & Sénateurs)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('representants')->name('representants.')->group(function () {
    // Mes représentants - Accessible uniquement aux utilisateurs connectés
    Route::get('/mes-representants', [RepresentantController::class, 'mesRepresentants'])->name('mes-representants');
    
    // Députés (nouveaux - ActeurAN + Wikipedia)
    Route::get('/deputes', [App\Http\Controllers\Web\RepresentantANController::class, 'deputes'])->name('deputes.index');
    Route::get('/deputes/{uid}', [App\Http\Controllers\Web\RepresentantANController::class, 'showDepute'])->name('deputes.show');
    Route::get('/deputes/{uid}/votes', [App\Http\Controllers\Web\RepresentantANController::class, 'deputeVotes'])->name('deputes.votes');
    Route::get('/deputes/{uid}/amendements', [App\Http\Controllers\Web\RepresentantANController::class, 'deputeAmendements'])->name('deputes.amendements');
    Route::get('/deputes/{uid}/activite', [App\Http\Controllers\Web\RepresentantANController::class, 'deputeActivite'])->name('deputes.activite');
    
    // Sénateurs (nouveaux - Senateur)
    Route::get('/senateurs', [App\Http\Controllers\Web\RepresentantANController::class, 'senateurs'])->name('senateurs.index');
    Route::get('/senateurs/{matricule}', [App\Http\Controllers\Web\RepresentantANController::class, 'showSenateur'])->name('senateurs.show');
    Route::get('/senateurs/{matricule}/votes', [App\Http\Controllers\Web\RepresentantANController::class, 'senateurVotes'])->name('senateurs.votes');
    Route::get('/senateurs/{matricule}/amendements', [App\Http\Controllers\Web\RepresentantANController::class, 'senateurAmendements'])->name('senateurs.amendements');
    Route::get('/senateurs/{matricule}/activite', [App\Http\Controllers\Web\RepresentantANController::class, 'senateurActivite'])->name('senateurs.activite');
});

/*
|--------------------------------------------------------------------------
| Débats Sénat (Comptes-rendus des séances publiques)
|--------------------------------------------------------------------------
*/
Route::prefix('debats/senat')->name('debats.senat.')->group(function () {
    Route::get('/', [App\Http\Controllers\Web\DebatSenatController::class, 'index'])->name('index');
    Route::get('/section/{id}', [App\Http\Controllers\Web\DebatSenatController::class, 'section'])->name('section');
    Route::get('/senateur/{matricule}', [App\Http\Controllers\Web\DebatSenatController::class, 'parSenateur'])->name('senateur');
    Route::get('/{dateSeance}', [App\Http\Controllers\Web\DebatSenatController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Parlement (Comparaisons & Stats globales)
|--------------------------------------------------------------------------
*/
// Routes parlement (authentifié)
Route::middleware('auth')->prefix('parlement')->name('parlement.')->group(function () {
    Route::get('/comparaison', [ParlementController::class, 'comparaison'])->name('comparaison');
});

/*
|--------------------------------------------------------------------------
| Espace Élu (Dashboard pour élus vérifiés)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'two-factor'])->prefix('elu')->name('elu.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Web\EluDashboardController::class, 'index'])->name('dashboard');
    Route::get('/interpellations', [App\Http\Controllers\Web\EluDashboardController::class, 'interpellations'])->name('interpellations');
    Route::get('/interpellations/{interpellation}', [App\Http\Controllers\Web\EluDashboardController::class, 'showInterpellation'])->name('interpellations.show');
    Route::post('/interpellations/{interpellation}/respond', [App\Http\Controllers\Web\EluDashboardController::class, 'respond'])->name('interpellations.respond');
    Route::post('/interpellations/{interpellation}/decline', [App\Http\Controllers\Web\EluDashboardController::class, 'decline'])->name('interpellations.decline');
    Route::get('/ma-fiche', [App\Http\Controllers\Web\EluDashboardController::class, 'maFiche'])->name('ma-fiche');
    Route::get('/stats', [App\Http\Controllers\Web\EluDashboardController::class, 'stats'])->name('stats');
});

// Profil public d'un élu
Route::get('/elu/{type}/{ref}', [App\Http\Controllers\Web\EluDashboardController::class, 'publicProfile'])->name('elu.public');

/*
|--------------------------------------------------------------------------
| Communes (Villes)
|--------------------------------------------------------------------------
*/
Route::prefix('communes')->name('communes.')->group(function () {
    Route::get('/', [App\Http\Controllers\Web\CommuneController::class, 'index'])->name('index');
    Route::get('/search', [App\Http\Controllers\Web\CommuneController::class, 'search'])->name('search');
    Route::get('/{inseeCode}', [App\Http\Controllers\Web\CommuneController::class, 'show'])->name('show');
});

// Villes (entité enrichie avec historique maires et stats)
Route::prefix('villes')->name('villes.')->group(function () {
    Route::get('/', [App\Http\Controllers\Web\VilleController::class, 'index'])->name('index');
    Route::get('/search', [App\Http\Controllers\Web\VilleController::class, 'search'])->name('search');
    Route::get('/{slug}', [App\Http\Controllers\Web\VilleController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
