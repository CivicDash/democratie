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

// Page d'accueil principale - Deux parcours utilisateurs
Route::get('/', function () {
    // Statistiques pré-calculées (table dashboard_stats)
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
})->name('home');

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
    Route::get('/', [\App\Http\Controllers\Web\QuestionController::class, 'index'])->name('index');
    Route::get('/stats', [\App\Http\Controllers\Web\QuestionController::class, 'stats'])->name('stats');
    Route::get('/depute/{uid}', [\App\Http\Controllers\Web\QuestionController::class, 'byDepute'])->name('depute');
    Route::get('/{uid}', [\App\Http\Controllers\Web\QuestionController::class, 'show'])->name('show');
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
    
    // Authenticated routes - Posts sur les topics
    Route::middleware('auth')->group(function () {
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
    Route::get('/ministre/{id}', [GouvernementController::class, 'showMinistre'])->name('ministre');
    Route::get('/historique', [GouvernementController::class, 'historique'])->name('historique');
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
    
    // Idées citoyennes
    Route::get('/idees', [\App\Http\Controllers\Web\ParticipationController::class, 'ideasIndex'])->name('ideas.index');
    Route::get('/idees/nouvelle', [\App\Http\Controllers\Web\ParticipationController::class, 'ideasCreate'])->name('ideas.create');
    Route::get('/idees/{topic:slug}', [\App\Http\Controllers\Web\ParticipationController::class, 'ideasShow'])->name('ideas.show');
    Route::post('/idees', [\App\Http\Controllers\Web\ParticipationController::class, 'ideasStore'])->name('ideas.store');
    Route::post('/idees/{topic}/vote', [\App\Http\Controllers\Web\ParticipationController::class, 'vote'])->name('ideas.vote');
    Route::delete('/idees/{topic}/vote', [\App\Http\Controllers\Web\ParticipationController::class, 'unvote'])->name('ideas.unvote');
    Route::post('/idees/{topic}/comment', [\App\Http\Controllers\Web\ParticipationController::class, 'addComment'])->name('ideas.comment');
});

/*
|--------------------------------------------------------------------------
| Espace Élu (réservé aux élus vérifiés)
|--------------------------------------------------------------------------
*/
Route::prefix('elu')->name('elu.')->middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\EluDashboardController::class, 'index'])->name('dashboard');
    Route::get('/interpellations', [\App\Http\Controllers\Web\EluDashboardController::class, 'interpellations'])->name('interpellations');
    Route::get('/interpellations/{interpellation}', [\App\Http\Controllers\Web\EluDashboardController::class, 'showInterpellation'])->name('interpellations.show');
    Route::post('/interpellations/{interpellation}/respond', [\App\Http\Controllers\Web\EluDashboardController::class, 'respond'])->name('interpellations.respond');
    Route::post('/interpellations/{interpellation}/decline', [\App\Http\Controllers\Web\EluDashboardController::class, 'decline'])->name('interpellations.decline');
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
    Route::get('/france/compare', [FranceStatisticsController::class, 'compareYears'])->name('france.compare');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/imports', [AdminController::class, 'imports'])->name('imports');
    Route::get('/imports/{import}', [AdminController::class, 'showImport'])->name('imports.show');
    Route::post('/run-command', [AdminController::class, 'runCommand'])->name('run-command');

    // Gestion du Gouvernement
    Route::prefix('gouvernement')->name('gouvernement.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\AdminGouvernementController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Web\AdminGouvernementController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Web\AdminGouvernementController::class, 'store'])->name('store');
        Route::get('/{gouvernement}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'show'])->name('show');
        Route::put('/{gouvernement}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'update'])->name('update');
        Route::delete('/{gouvernement}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'destroy'])->name('destroy');
        Route::get('/{gouvernement}/export', [App\Http\Controllers\Web\AdminGouvernementController::class, 'exportJson'])->name('export');
        Route::post('/{gouvernement}/ministres', [App\Http\Controllers\Web\AdminGouvernementController::class, 'addMinistre'])->name('add-ministre');
        Route::post('/ministeres', [App\Http\Controllers\Web\AdminGouvernementController::class, 'createMinistere'])->name('create-ministere');
    });
    Route::put('/ministres/{ministre}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'updateMinistre'])->name('gouvernement.update-ministre');
    Route::delete('/ministres/{ministre}', [App\Http\Controllers\Web\AdminGouvernementController::class, 'deleteMinistre'])->name('gouvernement.delete-ministre');

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
| Parlement (Comparaisons & Stats globales)
|--------------------------------------------------------------------------
*/
// Routes parlement (authentifié)
Route::middleware('auth')->prefix('parlement')->name('parlement.')->group(function () {
    Route::get('/comparaison', [ParlementController::class, 'comparaison'])->name('comparaison');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
