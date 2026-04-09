<?php

use App\Http\Controllers\Web\Commune\CommunePageController;
use App\Http\Controllers\Web\Commune\CommuneArticleController;
use App\Http\Controllers\Web\Commune\CommuneEvenementController;
use App\Http\Controllers\Web\Commune\CommuneForumController;
use App\Http\Controllers\Web\Commune\CommuneAdminController;
use App\Http\Controllers\Web\Commune\CommuneReclamationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Commune Hub Routes (sous-domaine *.civicdash.fr)
|--------------------------------------------------------------------------
|
| Routes pour les pages communes - hub citoyen local
| Accessibles via slug.civicdash.fr ou /commune-hub/{codeInsee}
|
*/

// Routes publiques des pages communes (accessibles sans sous-domaine aussi)
Route::prefix('commune-hub/{codeInsee}')->name('commune.')->group(function () {

    // Pages publiques
    Route::get('/', [CommunePageController::class, 'index'])->name('index');
    Route::get('/actualites', [CommuneArticleController::class, 'index'])->name('actualites');
    Route::get('/actualites/{slug}', [CommuneArticleController::class, 'show'])->name('actualites.show');
    Route::get('/evenements', [CommuneEvenementController::class, 'index'])->name('evenements');
    Route::get('/evenements/calendrier', [CommuneEvenementController::class, 'calendrier'])->name('evenements.calendrier');
    Route::get('/evenements/{slug}', [CommuneEvenementController::class, 'show'])->name('evenements.show');
    Route::get('/forum', [CommuneForumController::class, 'index'])->name('forum');
    Route::get('/budget', [CommunePageController::class, 'budget'])->name('budget');
    Route::get('/elus', [CommunePageController::class, 'elus'])->name('elus');
    Route::get('/elections', [CommunePageController::class, 'elections'])->name('elections');

    // Actions authentifiées
    Route::middleware('auth')->group(function () {
        // Abonnement
        Route::post('/abonner', [CommunePageController::class, 'abonner'])->name('abonner');
        Route::delete('/desabonner', [CommunePageController::class, 'desabonner'])->name('desabonner');
        Route::put('/abonnement/preferences', [CommunePageController::class, 'updatePreferencesAbonnement'])->name('abonnement.preferences');

        // Inscription événements
        Route::post('/evenements/{slug}/inscription', [CommuneEvenementController::class, 'inscrire'])->name('evenements.inscrire');
        Route::delete('/evenements/{slug}/inscription', [CommuneEvenementController::class, 'desinscrire'])->name('evenements.desinscrire');

        // Réclamation
        Route::get('/reclamer', [CommuneReclamationController::class, 'index'])->name('reclamer');
        Route::post('/reclamer', [CommuneReclamationController::class, 'initier'])->name('reclamer.initier');
        Route::post('/reclamer/verifier', [CommuneReclamationController::class, 'verifierCode'])->name('reclamer.verifier');
        Route::post('/reclamer/document', [CommuneReclamationController::class, 'soumettreDocument'])->name('reclamer.document');
    });

    // Administration commune (maire/délégués)
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [CommuneAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/parametres', [CommuneAdminController::class, 'parametres'])->name('parametres');
        Route::put('/parametres', [CommuneAdminController::class, 'updateParametres'])->name('parametres.update');
        Route::post('/logo', [CommuneAdminController::class, 'uploadLogo'])->name('logo');
        Route::post('/couverture', [CommuneAdminController::class, 'uploadCouverture'])->name('couverture');

        // CRUD Articles
        Route::get('/articles', [CommuneArticleController::class, 'adminIndex'])->name('articles');
        Route::get('/articles/create', [CommuneArticleController::class, 'create'])->name('articles.create');
        Route::post('/articles', [CommuneArticleController::class, 'store'])->name('articles.store');
        Route::get('/articles/{slug}/edit', [CommuneArticleController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{slug}', [CommuneArticleController::class, 'update'])->name('articles.update');
        Route::delete('/articles/{slug}', [CommuneArticleController::class, 'destroy'])->name('articles.destroy');
        Route::post('/articles/{slug}/publier', [CommuneArticleController::class, 'publier'])->name('articles.publier');

        // CRUD Événements
        Route::get('/evenements', [CommuneEvenementController::class, 'adminIndex'])->name('evenements');
        Route::get('/evenements/create', [CommuneEvenementController::class, 'create'])->name('evenements.create');
        Route::post('/evenements', [CommuneEvenementController::class, 'store'])->name('evenements.store');
        Route::get('/evenements/{slug}/edit', [CommuneEvenementController::class, 'edit'])->name('evenements.edit');
        Route::put('/evenements/{slug}', [CommuneEvenementController::class, 'update'])->name('evenements.update');
        Route::delete('/evenements/{slug}', [CommuneEvenementController::class, 'destroy'])->name('evenements.destroy');
        Route::post('/evenements/{slug}/annuler', [CommuneEvenementController::class, 'annulerEvenement'])->name('evenements.annuler');
        Route::get('/evenements/{slug}/inscriptions', [CommuneEvenementController::class, 'inscriptions'])->name('evenements.inscriptions');
        Route::get('/evenements/{slug}/inscriptions/export', [CommuneEvenementController::class, 'exportInscriptions'])->name('evenements.inscriptions.export');
        Route::delete('/evenements/{slug}/inscriptions/{id}', [CommuneEvenementController::class, 'annulerInscription'])->name('evenements.inscriptions.annuler');

        // Délégués
        Route::get('/delegues', [CommuneAdminController::class, 'delegues'])->name('delegues');
        Route::post('/delegues', [CommuneAdminController::class, 'ajouterDelegue'])->name('delegues.ajouter');
        Route::put('/delegues/{id}', [CommuneAdminController::class, 'updateDelegue'])->name('delegues.update');
        Route::delete('/delegues/{id}', [CommuneAdminController::class, 'supprimerDelegue'])->name('delegues.supprimer');

        // Notifications
        Route::get('/notifications', [CommuneAdminController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/envoyer', [CommuneAdminController::class, 'envoyerNotification'])->name('notifications.envoyer');

        // Galerie
        Route::get('/galerie', [CommuneAdminController::class, 'galerie'])->name('galerie');
        Route::post('/galerie', [CommuneAdminController::class, 'uploadImage'])->name('galerie.upload');
        Route::put('/galerie/{id}', [CommuneAdminController::class, 'updateImage'])->name('galerie.update');
        Route::post('/galerie/reorder', [CommuneAdminController::class, 'reorderImages'])->name('galerie.reorder');
        Route::delete('/galerie/{id}', [CommuneAdminController::class, 'deleteImage'])->name('galerie.delete');

        // Analytics
        Route::get('/analytics', [CommuneAdminController::class, 'analytics'])->name('analytics');
    });
});

// API endpoint pour Caddy on_demand TLS
Route::get('/api/verify-subdomain', function (\Illuminate\Http\Request $request) {
    $domain = $request->query('domain', '');
    $baseDomain = config('app.commune_domain', 'civicdash.fr');

    if (! str_ends_with($domain, '.'.$baseDomain)) {
        return response('Invalid domain', 404);
    }

    $subdomain = str_replace('.'.$baseDomain, '', $domain);

    if (in_array($subdomain, ['www', 'api', 'admin', 'mail', 'demo'])) {
        return response('Reserved subdomain', 404);
    }

    $exists = \App\Models\Ville::where('slug', $subdomain)->exists();

    return $exists ? response('OK', 200) : response('Not found', 404);
});
