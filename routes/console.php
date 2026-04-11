<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tâches Planifiées - Import Données
|--------------------------------------------------------------------------
| Ces commandes s'exécutent automatiquement via le scheduler Laravel.
| Ajouter au crontab : * * * * * php /var/www/artisan schedule:run >> /dev/null 2>&1
|
| ORDRE D'EXÉCUTION (important pour les dépendances) :
| 1. 01h00-02h00 : Données de base (acteurs, organes)
| 2. 02h00-03h00 : Dossiers législatifs et textes
| 3. 03h00-03h30 : Amendements
| 4. 03h30-04h00 : Scrutins et votes
| 5. 04h00-04h30 : Recalcul des statistiques
| 6. 05h00-06h00 : Agenda et calendrier
| 7. 06h00-07h00 : Questions gouvernement
|
| IMPORTANT : Les imports auto sont desactives sur l'environnement dev
| Pour forcer un import en dev : php artisan <commande> manuellement
*/

if (app()->environment('local', 'development', 'testing') && env('DISABLE_SCHEDULED_IMPORTS', false)) {
    return;
}

/*
|--------------------------------------------------------------------------
| 1. DONNÉES DE BASE (01h00 - 02h00)
|--------------------------------------------------------------------------
*/

// Acteurs et organes AN (base pour tout le reste)
// Note: Ces imports sont incrémentaux par défaut (nouveaux fichiers uniquement)
Schedule::command('import:acteurs-an')
    ->dailyAt('01:00')
    ->description('Import quotidien des acteurs AN')
    ->withoutOverlapping();

Schedule::command('import:organes-an')
    ->dailyAt('01:15')
    ->description('Import quotidien des organes AN')
    ->withoutOverlapping();

// Sénateurs (données complémentaires)
Schedule::command('senat:sync --no-confirm')
    ->dailyAt('01:30')
    ->description('Synchronisation données Sénat')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| 2. DOSSIERS LÉGISLATIFS ET TEXTES (02h00 - 03h00)
|--------------------------------------------------------------------------
*/

// Dossiers législatifs AN (projets/propositions de loi)
// Legislature 17 par défaut, import incrémental
Schedule::command('import:dossiers-textes-an')
    ->dailyAt('02:00')
    ->description('Import quotidien des dossiers législatifs AN')
    ->withoutOverlapping();

// Dossiers législatifs Sénat
Schedule::command('import:dossiers-senat')
    ->dailyAt('02:20')
    ->description('Import quotidien des dossiers législatifs Sénat')
    ->withoutOverlapping();

// Journal Officiel (nouvelles lois publiées)
Schedule::command('import:jorf')
    ->dailyAt('02:40')
    ->description('Import des publications JORF')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| 3. AMENDEMENTS (03h00 - 03h30)
|--------------------------------------------------------------------------
*/

// Amendements AN (legislature 17 par défaut)
Schedule::command('import:amendements-an')
    ->dailyAt('03:00')
    ->description('Import quotidien des amendements AN')
    ->withoutOverlapping();

// Amendements Sénat (désactivé : nécessite import SQL AMELI via import:senat-sql)
// Schedule::command('import:amendements-senat')
//     ->dailyAt('03:15')
//     ->description('Import quotidien des amendements Sénat')
//     ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| 4. SCRUTINS ET VOTES (03h30 - 04h00)
|--------------------------------------------------------------------------
*/

// Scrutins AN (votes en séance) - legislature 17 par défaut
Schedule::command('import:scrutins-an')
    ->dailyAt('03:30')
    ->description('Import quotidien des scrutins AN')
    ->withoutOverlapping();

// Extraction des votes individuels AN
Schedule::command('extract:votes-individuels-an')
    ->dailyAt('03:45')
    ->description('Extraction des votes individuels députés')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| 5. RECALCUL DES STATISTIQUES (04h00 - 05h00)
|--------------------------------------------------------------------------
*/

// Recalcul des statistiques du dashboard
Schedule::command('dashboard:calculate-stats --force')
    ->dailyAt('04:00')
    ->description('Recalcul quotidien des statistiques dashboard');

// Recalcul des statistiques parlementaires (taux présence, amendements, etc.)
Schedule::command('calculate:parlementaires-stats --force')
    ->dailyAt('04:15')
    ->description('Recalcul quotidien des statistiques parlementaires pré-calculées');

// Recalcul des statistiques des lois (amendements, scrutins, durée, etc.)
Schedule::command('calculate:lois-stats --force')
    ->dailyAt('04:30')
    ->description('Recalcul quotidien des statistiques lois pré-calculées');

// Recalcul des statistiques globales des élus (page comparaison)
Schedule::command('calculate:elus-global-stats --force')
    ->dailyAt('04:45')
    ->description('Recalcul quotidien des statistiques globales élus');

/*
|--------------------------------------------------------------------------
| 6. AGENDA ET CALENDRIER (05h00 - 06h00)
|--------------------------------------------------------------------------
*/

// Agenda AN (réunions, commissions)
Schedule::command('import:reunions-an')
    ->dailyAt('05:00')
    ->description('Import des réunions AN')
    ->withoutOverlapping();

// Agenda Sénat (séances, commissions)
Schedule::command('import:agenda-senat')
    ->dailyAt('05:15')
    ->description('Import de l\'agenda Sénat')
    ->withoutOverlapping();

// Agenda Élysée
Schedule::command('import:agenda-elysee')
    ->dailyAt('05:30')
    ->description('Import de l\'agenda Élysée')
    ->withoutOverlapping();

// Synchronisation des débats Sénat vers le calendrier
Schedule::command('sync:debats-calendar')
    ->dailyAt('05:45')
    ->description('Synchronisation débats Sénat → calendrier unifié')
    ->withoutOverlapping();

// Synchronisation globale événements → calendrier unifié
Schedule::command('sync:evenements-an')
    ->dailyAt('05:50')
    ->description('Synchronisation événements AN → calendrier unifié')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| 7. QUESTIONS AU GOUVERNEMENT (06h00 - 07h00)
|--------------------------------------------------------------------------
*/

// Questions écrites/orales AN
Schedule::command('import:questions-an')
    ->dailyAt('06:00')
    ->description('Import quotidien des questions AN')
    ->withoutOverlapping();

// Questions Sénat
Schedule::command('import:questions-senat')
    ->dailyAt('06:15')
    ->description('Import quotidien des questions Sénat')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| 7b. VIDEOS AN (06h30 - 06h50)
|--------------------------------------------------------------------------
*/

Schedule::command('import:video-ids-an')
    ->dailyAt('06:30')
    ->description('Découverte des vidéos AN et rattachement aux réunions')
    ->withoutOverlapping();

Schedule::command('import:video-chapters-an --all')
    ->dailyAt('06:40')
    ->description('Import des chapitres vidéo depuis data.nvs')
    ->withoutOverlapping();

Schedule::command('match:video-chapters-an')
    ->dailyAt('06:50')
    ->description('Matching chapitres vidéo avec QAG et amendements')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| 8. ENRICHISSEMENT (07h00 - 08h00)
|--------------------------------------------------------------------------
*/

// Enrichissement Wikipedia (photos, bios)
Schedule::command('sync:wikipedia-personnes --limit=50')
    ->dailyAt('07:00')
    ->description('Enrichissement Wikipedia (50 personnes/jour)')
    ->withoutOverlapping();

// Enrichissement votes députés
Schedule::command('enrich:deputes-votes')
    ->dailyAt('07:15')
    ->description('Enrichissement votes députés')
    ->withoutOverlapping();

// Enrichissement votes sénateurs
Schedule::command('enrich:senateurs-votes')
    ->dailyAt('07:30')
    ->description('Enrichissement votes sénateurs')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| NOTIFICATIONS ÉLUS SUIVIS
|--------------------------------------------------------------------------
*/

// Traitement des nouvelles activités toutes les heures
Schedule::command('elu:process-activities')
    ->hourly()
    ->description('Détection et notification des nouvelles activités des élus suivis');

// Digest quotidien à 8h du matin
Schedule::command('elu:process-activities --digest=daily')
    ->dailyAt('08:00')
    ->description('Envoi des digests quotidiens des activités élus');

// Digest hebdomadaire le lundi à 8h
Schedule::command('elu:process-activities --digest=weekly')
    ->weeklyOn(1, '08:00')
    ->description('Envoi des digests hebdomadaires des activités élus');

/*
|--------------------------------------------------------------------------
| MAINTENANCE HEBDOMADAIRE (Dimanche nuit)
|--------------------------------------------------------------------------
*/

// Import complet hebdomadaire (au cas où des données auraient été manquées)
Schedule::command('sync:all')
    ->weeklyOn(0, '02:00') // Dimanche 2h
    ->description('Synchronisation complète hebdomadaire de toutes les sources')
    ->withoutOverlapping();

// Recalcul complet des scrutins
Schedule::command('scrutins:recalculate-totals')
    ->weeklyOn(0, '03:30')
    ->description('Recalcul hebdomadaire des totaux scrutins')
    ->withoutOverlapping();

// Import des débats Sénat (historique complet)
Schedule::command('senat:import-debats --download')
    ->weeklyOn(0, '04:00')
    ->description('Mise à jour hebdomadaire des débats Sénat')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| ÉLECTIONS MUNICIPALES 2026
|--------------------------------------------------------------------------
*/

// Rappels candidatures (J-7, J-3, J-1 avant date limite dépôt)
Schedule::command('candidatures:send-reminders')
    ->dailyAt('09:00')
    ->description('Envoi rappels candidatures municipales')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| AFFAIRES JUDICIAIRES (détection + stats + notifications)
|--------------------------------------------------------------------------
*/

Schedule::command('affaires:detect-wikidata')
    ->weekly()
    ->description('Détection Wikidata des affaires judiciaires (P1399)')
    ->withoutOverlapping();

Schedule::command('affaires:detect-wikipedia')
    ->twiceMonthly(1, 15)
    ->description('Détection Wikipedia NLP des affaires judiciaires')
    ->withoutOverlapping();

Schedule::command('affaires:detect-hatvp')
    ->monthly()
    ->description('Détection HATVP des manquements/signalements')
    ->withoutOverlapping();

Schedule::command('affaires:calculate-stats')
    ->dailyAt('04:55')
    ->description('Recalcul des statistiques affaires judiciaires');

Schedule::command('affaires:notify-moderators')
    ->dailyAt('09:00')
    ->description('Notification modérateurs pour affaires en attente');

/*
|--------------------------------------------------------------------------
| ENRICHISSEMENT WIKIDATA - PersonnePolitique
|--------------------------------------------------------------------------
*/

Schedule::command('enrich:personnes-wikidata')
    ->monthly()
    ->description('Enrichissement Wikidata ID des PersonnePolitique')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| COMMUNES - Hub Citoyen v2.0
|--------------------------------------------------------------------------
*/

// Import emails mairies via API service-public.fr (mensuel)
Schedule::command('communes:import-emails-service-public')
    ->monthlyOn(1, '03:00')
    ->description('Mise a jour mensuelle des coordonnees mairies')
    ->withoutOverlapping();

// Synchronisation des stats communes (hebdomadaire)
Schedule::command('communes:sync-stats')
    ->weeklyOn(0, '05:00')
    ->description('Synchronisation hebdomadaire des stats pages communes')
    ->withoutOverlapping();

// Notifications aux abonnes des communes (quotidien)
Schedule::command('communes:notify-abonnes')
    ->dailyAt('10:00')
    ->description('Notifications quotidiennes aux abonnes communes')
    ->withoutOverlapping();

// Digest hebdomadaire communes (lundi 8h30)
Schedule::job(new \App\Jobs\SendCommuneDigest)
    ->weeklyOn(1, '08:30')
    ->description('Envoi des digests hebdomadaires communes aux abonnes')
    ->withoutOverlapping();

// Import images Wikimedia Commons (mensuel)
Schedule::command('communes:fetch-wikimedia-images --limit=100')
    ->monthlyOn(1, '03:00')
    ->description('Import mensuel des images Wikimedia Commons')
    ->withoutOverlapping();

// Regeneration du sitemap (quotidien a 4h)
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::forget('sitemap_xml');
    app(\App\Http\Controllers\SitemapController::class)->index();
})
    ->dailyAt('04:00')
    ->description('Regeneration du sitemap XML');
